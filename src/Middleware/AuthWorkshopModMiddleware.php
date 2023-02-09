<?php

namespace App\Middleware;

use App\Account;
use App\Enum\UserRole;
use App\FlashMessage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class AuthWorkshopModMiddleware implements MiddlewareInterface {

    /** @var ResponseFactory $response_factory */
    public $response_factory;

    /** @var Account $account */
    public $account;

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
    public function __construct(ResponseFactory $response_factory, Account $account, FlashMessage $flash)
    {
        $this->response_factory = $response_factory;
        $this->account          = $account;
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
        if(!$this->account->isLoggedIn() || $this->account->getUser()->getRole()->value < UserRole::WorkshopModerator->value){

            $this->flash->warning('You do not have the rights to access this resource.');

            return $this->response_factory->createResponse()
                ->withHeader('Location', '/dashboard')
                ->withStatus(302);
        }

        $response = $handler->handle($request);

        return $response;
    }
}
