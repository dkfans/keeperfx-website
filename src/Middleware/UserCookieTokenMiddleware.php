<?php

namespace App\Middleware;

use App\Enum\OAuthProviderType;

use App\Entity\UserOAuthToken;
use App\Entity\UserCookieToken;

use App\Account;
use App\BanChecker;
use App\Enum\UserRole;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;
use App\OAuth\OAuthProviderService;

use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class UserCookieTokenMiddleware implements MiddlewareInterface {

    public function __construct(
        private EntityManager $em,
        private Account $account,
        private Session $session,
        private FlashMessage $flash,
        private BanChecker $ban_checker,
        private OAuthProviderService $provider_service,
        private ResponseFactory $response_factory,
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
                    /** @var UserOAuthToken $oauth_token */
                    $oauth_token = $cookie_token->getOAuthToken();
                    if($oauth_token){

                        // Handle invalidated tokens
                        if($oauth_token->getToken() === null || $oauth_token->getRefreshToken() === null){

                            // Remove cookie token from DB
                            // **NOT** the OAuth token as this belongs to an account and not to a login session
                            $this->em->remove($cookie_token);
                            $this->em->flush();

                            // Continue request without logging in
                            $response = $handler->handle($request);
                            return $response;
                        }

                        // Refresh expired OAuth Token
                        if($oauth_token->getExpiresTimestamp()->getTimestamp() < time()){

                            // Get OAuth provider
                            $provider = $this->provider_service->getProvider($oauth_token->getProviderType());

                            // Get new OAuth Token from provider
                            try {
                                $new_access_token = $provider->getAccessToken('refresh_token', [
                                    'refresh_token' => $oauth_token->getRefreshToken()
                                ]);
                            } catch (\Exception $ex) {

                                // Invalidate OAuth token in DB
                                $oauth_token->setToken(null);
                                $oauth_token->setRefreshToken(null);
                                $oauth_token->setExpiresTimestamp(null);

                                // Remove cookie token
                                $this->em->remove($cookie_token);

                                // Flush changes to DB
                                $this->em->flush();

                                // Continue request without logging in
                                $response = $handler->handle($request);
                                return $response;
                            }

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

                    // Check if banned
                    if($cookie_token->getUser()->getRole() == UserRole::Banned){

                        // Show message
                        $this->flash->error("You have been banned.");

                        // Invalidate cookie token
                        $this->em->remove($cookie_token);
                        $this->em->flush();

                        // Continue request
                        $response = $handler->handle($request);
                        return $response;
                    }

                    // Login the user
                    $this->account->setCurrentLoggedInUser($cookie_token->getUser());

                    // Get the IP
                    $ip = $request->getAttribute('ip_address');
                    $hostname = \gethostbyaddr($ip);

                    // Log IP
                    if($ip){
                        $this->account->logIp($ip);
                    }

                    // Check if this IP or hostname is banned
                    if($this->ban_checker->checkAll($ip, $hostname)){

                        // Make them wait :)
                        \sleep(1 + \random_int(0, 3));

                        // Ambiguous message
                        $this->flash->error("Something went wrong.");

                        // Logout user
                        $this->account->clearCurrentLoggedInUser();

                        // Invalidate cookie token
                        $this->em->remove($cookie_token);
                        $this->em->flush();

                        // TODO: ban the user account too
                    }
                }
            }
        }

        $response = $handler->handle($request);

        return $response;
    }
}
