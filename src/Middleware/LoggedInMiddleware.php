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

class LoggedInMiddleware implements MiddlewareInterface {

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
        if(!$this->account->isLoggedIn()){

            $this->flash->info('You need to be logged in to access this resource.');

            $location = '/login';

            // Remember path for redirection after login
            $redirect = $request->getUri()->getPath();
            if($redirect){
                $location .= '?redirect=' . $redirect;
            }

            return $this->response_factory->createResponse()
                ->withHeader('Location', $location)
                ->withStatus(302);

        }

        $response = $handler->handle($request);

        return $response;
    }
}
