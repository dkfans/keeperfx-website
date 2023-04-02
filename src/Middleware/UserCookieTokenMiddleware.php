<?php

namespace App\Middleware;

use App\Enum\OAuthProviderType;

use App\Entity\UserOAuthToken;
use App\Entity\UserCookieToken;

use App\Account;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;
use App\OAuth\OAuthProviderService;

use League\OAuth2\Client\Token\AccessToken;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserCookieTokenMiddleware implements MiddlewareInterface {

    public function __construct(
        private EntityManager $em,
        private Account $account,
        private Session $session,
        private OAuthProviderService $provider_service
    ) {}

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
                    $oauth_token = $cookie_token->getOAuthToken();
                    if($oauth_token){

                        // Get OAuth provider
                        $provider = $this->provider_service->getProvider($oauth_token_entity->getProviderType());

                        // Refresh expired OAuth Token
                        if($oauth_token->getExpiresTimestamp()->getTimestamp() < time()){

                            // TODO: handle errors
                            // TODO: remove invalid cookies

                            // Get new OAuth Token from provider
                            $new_access_token = $provider->getAccessToken('refresh_token', [
                                'refresh_token' => $oauth_token->getRefreshToken()
                            ]);

                            // Update OAuth Token in DB
                            $oauth_token->setToken($new_access_token->getToken());
                            $oauth_token->setRefreshToken($new_access_token->getRefreshToken());
                            $oauth_token->setExpiresTimestamp(
                                \DateTime::createFromFormat('U', (int) $new_access_token->getExpires())
                            );
                            $this->em->persist($oauth_token);
                            $this->em->flush();
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
