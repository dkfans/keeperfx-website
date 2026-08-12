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

class AuthModCPMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactory */
    public $response_factory;

    /** @var Account */
    public $account;

    /** @var FlashMessage */
    public $flash;

    /**
     * Constructor.
     */
    public function __construct(ResponseFactory $response_factory, Account $account, FlashMessage $flash)
    {
        $this->response_factory = $response_factory;
        $this->account          = $account;
        $this->flash            = $flash;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->account->isLoggedIn() || $this->account->getUser()->getRole()->value < UserRole::Moderator->value) {

            $this->flash->warning('You do not have the rights to access this resource.');

            return $this->response_factory->createResponse()
                ->withHeader('Location', '/')
                // ->withHeader('Location', '/dashboard')
                ->withStatus(302);
        }

        $response = $handler->handle($request);

        return $response;
    }
}
