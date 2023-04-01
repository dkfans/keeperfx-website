<?php

namespace App\Middleware;

use App\Enum\UserOAuthTokenType;

use App\Entity\UserOAuthToken;
use App\Entity\UserCookieToken;

use App\Account;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;

use League\OAuth2\Client\Token\AccessToken;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserCookieTokenMiddleware implements MiddlewareInterface {

    /** @var EntityManager $em */
    public $em;

    /** @var Account $account */
    public $account;

    /** @var Session $session */
    public $session;

    /**
     * Constructor
     *
     * @param Account $account
     * @param Session $session
     */
    public function __construct(
        EntityManager $em,
        Account $account,
        Session $session
    ) {
        $this->em      = $em;
        $this->account = $account;
        $this->session = $session;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if(!$this->account->isLoggedIn()){

            // Check if 'remember me' token is set (and valid)
            $cookies = $request->getCookieParams();
            $token = (string) ($cookies['user_cookie_token'] ?? '');
            if($token && \preg_match('~^[a-zA-Z0-9]+$~', $token)){

                // Check if token exists in DB
                $cookie_token = $this->em->getRepository(UserCookieToken::class)->findOneBy(['token' => $token]);
                if($cookie_token){

                    // Check if cookie is linked to an OAuth Token
                    /** @var UserOAuthToken $oauth_token_entity */
                    $oauth_token_entity = $cookie_token->getOAuthToken();
                    if($oauth_token_entity){

                        if($oauth_token_entity->getType() !== UserOAuthTokenType::Discord){
                            die('invalid oauth token type');
                        }

                        $provider = new \Wohali\OAuth2\Client\Provider\Discord([
                            'clientId'     => $_ENV['APP_DISCORD_OAUTH_CLIENT_ID'],
                            'clientSecret' => $_ENV['APP_DISCORD_OAUTH_CLIENT_SECRET'],
                        ]);

                        // Set a HTTP client that does not verify SSL certs
                        $provider->setHttpClient(new \GuzzleHttp\Client([
                            'defaults' => [
                                \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 5,
                                \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => true
                            ],
                            \GuzzleHttp\RequestOptions::VERIFY => false,
                        ]));

                        $oauth_token = new AccessToken([
                            'access_token'      => $oauth_token_entity->getToken(),
                            'refresh_token'     => $oauth_token_entity->getRefreshToken(),
                            'resource_owner_id' => $oauth_token_entity->getUid(),
                            'expires'           => $oauth_token_entity->getExpiresTimestamp(),
                        ]);

                        // Refresh expired OAuth Token
                        if($oauth_token->hasExpired()){

                            // TODO: handle errors
                            // TODO: remove invalid cookies

                            // Get new OAuth Token from provider
                            $new_access_token = $provider->getAccessToken('refresh_token', [
                                'refresh_token' => $oauth_token->getRefreshToken()
                            ]);

                            // Update OAuth Token in DB
                            $oauth_token_entity->setToken($new_access_token->getToken());
                            $oauth_token_entity->setRefreshToken($new_access_token->getRefreshToken());
                            $oauth_token_entity->setExpiresTimestamp(
                                \DateTime::createFromFormat('U', (int) $new_access_token->getExpires())
                            );
                            $this->em->persist($oauth_token_entity);
                        }
                    }

                    // Login the user
                    $this->account->setUser($cookie_token->getUser());
                    $this->session['uid'] = $cookie_token->getUser()->getId();
                }
            }
        }

        $response = $handler->handle($request);

        return $response;
    }
}
