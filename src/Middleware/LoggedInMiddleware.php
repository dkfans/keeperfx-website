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

class LoggedInMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactory $response_factory,
        private Account $account,
        private Session $session,
        private FlashMessage $flash
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

        if (!$this->account->isLoggedIn()) {

            if (
                $request->getHeaderLine('Content-Type') === "application/json" ||
                $request->getHeaderLine('X-Requested-With') === "XMLHttpRequest"
            ) {

                $response = $this->response_factory->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);

                $response->getBody()->write(\json_encode([
                    'success' => false,
                    'error'   => 'NOT_LOGGED_IN',
                ]));

                return $response;
            } else {

                $this->flash->info('You need to be logged in to access this resource.');

                $location = '/login';

                // Remember path for redirection after login
                $redirect = $request->getUri()->getPath();
                if ($redirect) {
                    $location .= '?redirect=' . $redirect;
                }

                return $this->response_factory->createResponse()
                    ->withHeader('Location', $location)
                    ->withStatus(302);
            }
        }

        $response = $handler->handle($request);

        return $response;
    }
}
