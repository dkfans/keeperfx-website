<?php

namespace App\Middleware;

use Slim\Psr7\Factory\ResponseFactory;
use Twig\Environment as TwigEnvironment;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Slim\Exception\HttpSpecializedException;

use Doctrine\DBAL\Driver\PDO\Exception as DbalPdoException;
use Doctrine\DBAL\Exception\ConnectionException as DbalConnectionException;

class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactory $response_factory,
        private TwigEnvironment $twig,
        private LoggerInterface $logger,
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
        try {

            return $handler->handle($request);
        } catch (\Throwable $ex) {

            $json_response = false;

            // Check if we should return a JSON response
            if (
                \str_contains($request->getHeaderLine('Accept') ?? '', 'application/json') ||
                \str_contains($request->getHeaderLine('Content-Type') ?? '', 'application/json') ||
                \str_starts_with($request->getUri()->getPath(), '/api/')
            ) {
                $json_response = true;
            }

            // Get database connection exception
            if (
                $ex instanceof DbalConnectionException ||
                ($ex instanceof DbalPdoException && $ex->getCode() == 2002) // Connection not found
            ) {
                $response = $this->response_factory->createResponse(500); // Server error

                if ($json_response == true) {
                    // Write JSON response
                    $response->getBody()->write(\json_encode([
                        'success'    => false,
                        'error_code' => 500,
                        'error'      => 'DATABASE_CONNECTION_ERROR'
                    ]));
                    $response = $response->withHeader('Content-Type', 'application/json');
                } else {
                    // Write hardcoded HTML response
                    // We can not use Twig here because it uses a database connection (which is probably kind of wrong)
                    $response->getBody()->write(
                        \file_get_contents(APP_ROOT . '/public/database-connection-error.html')
                    );
                }

                return $response;
            }

            // Log error if not a normal HTTP exception
            if ($this->logger && !($ex instanceof HttpSpecializedException)) {
                $this->logger->error($ex->getMessage());
            }

            if ($json_response == true) {

                if (\in_array($ex->getCode(), [403, 404, 405])) {
                    $response = $this->response_factory->createResponse($ex->getCode());
                    $response->getBody()->write(\json_encode([
                        'success'    => false,
                        'error_code' => $ex->getCode(),
                        'error'      => match ($ex->getCode()) {
                            403 => 'FORBIDDEN',
                            404 => 'NOT_FOUND',
                            405 => 'METHOD_NOT_ALLOWED',
                            default => 'UNKNOWN_ERROR',
                        }
                    ]));
                } else {
                    $response = $this->response_factory->createResponse(500);
                    $response->getBody()->write(\json_encode([
                        'success'    => false,
                        'error_code' => 500,
                        'error'      => 'INTERNAL_SERVER_ERROR'
                    ]));
                }
            } else {

                // Check if HTTP code has a unique error page
                $template_file = \sprintf('error/%d.html.twig', $ex->getCode());
                if (\file_exists(APP_ROOT . '/views/' . $template_file)) {
                    // Show template
                    $response = $this->response_factory->createResponse($ex->getCode());
                    $response->getBody()->write(
                        $this->twig->render($template_file)
                    );
                } else {
                    // Show default template (500 - Server error)
                    $response = $this->response_factory->createResponse(500);
                    $response->getBody()->write(
                        $this->twig->render('error/500.html.twig')
                    );
                }
            }

            return $response;
        }
    }
}
