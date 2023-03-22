<?php

namespace App\Middleware;

use App\Account;
use App\Entity\UserCookieToken;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;

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
                $cookieToken = $this->em->getRepository(UserCookieToken::class)->findOneBy(['token' => $token]);
                if($cookieToken){

                    // Login the user
                    $this->account->setUser($cookieToken->getUser());
                    $this->session['uid'] = $cookieToken->getUser()->getId();
                }
            }
        }

        $response = $handler->handle($request);

        return $response;
    }
}
