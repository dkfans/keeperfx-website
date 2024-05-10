<?php

namespace App\Middleware;

use App\Account;
use App\FlashMessage;
use Compwright\PhpSession\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Stores the IP in our session and if it changes we try logging it again.
 */
class UserIpChangeLoggerMiddleware implements MiddlewareInterface {

    /** @var ResponseFactory $response_factory */
    public $response_factory;

    /** @var Account $account */
    public $account;

    /** @var Session $session */
    public $session;

    /** @var FlashMessage $flash */
    public $flash;

    /**
     * Constructor
     *
     * @param ResponseFactory $response_factory
     * @param Account $account
     * @param Session $session
     * @param FlashMessage $flash
     */
    public function __construct(ResponseFactory $response_factory, Account $account, Session $session, FlashMessage $flash)
    {
        $this->response_factory = $response_factory;
        $this->account          = $account;
        $this->session          = $session;
        $this->flash            = $flash;
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
        // Get IP
        $ip = $request->getAttribute('ip_address');

        // Only for logged in users (with a valid IP)
        if($this->account->isLoggedIn() && $ip !== null){

            // Check if the IP is not yet stored in the session
            if(empty($this->session['ip']) || \is_null($this->session['ip'])){

                // Remember IP address for this session
                $this->session['ip'] = $ip;

            } else {

                // Check if the IP has changed
                if($this->session['ip'] !== $ip){

                    // Log the IP
                    $this->account->logIp($ip);

                    // Store the new IP in our session
                    $this->session['ip'] = $ip;
                }
            }
        }

        // Continue handling the request
        $response = $handler->handle($request);
        return $response;
    }
}
