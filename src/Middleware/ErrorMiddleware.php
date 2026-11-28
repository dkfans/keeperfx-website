<?php

namespace App\Middleware;

use Doctrine\DBAL\Driver\PDO\Exception as DbalPdoException;
use Doctrine\DBAL\Exception\ConnectionException as DbalConnectionException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Psr7\Factory\ResponseFactory;
use Twig\Environment as TwigEnvironment;

class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactory $response_factory,
        private TwigEnvironment $twig,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $ex) {

            $json_response = false;

            // Check if we should return a JSON response
            if (
                \str_contains($request->getHeaderLine('Accept'), 'application/json')
                || \str_contains($request->getHeaderLine('Content-Type'), 'application/json')
                || \str_starts_with($request->getUri()->getPath(), '/api/')
            ) {
                $json_response = true;
            }

            // Log non-HTTP exceptions and 5xx HTTP exceptions (skip 4xx like 404)
            $is_http_exception = $ex instanceof HttpException;
            $status_code       = $is_http_exception ? (int) $ex->getCode() : 500;

            if (!$is_http_exception || $status_code >= 500) {
                $this->logger->error(
                    \sprintf(
                        "%s: %s in %s:%d\n%s",
                        $ex::class,
                        $ex->getMessage(),
                        $ex->getFile(),
                        $ex->getLine(),
                        $ex->getTraceAsString()
                    )
                );
            }

            // Get database connection exception
            if (
                $ex instanceof DbalConnectionException
                || ($ex instanceof DbalPdoException && $ex->getCode() == 2002)
            ) {
                $response = $this->response_factory->createResponse(500);

                if ($json_response === true) {
                    $response->getBody()->write(\json_encode([
                        'success'    => false,
                        'error_code' => 500,
                        'error'      => 'DATABASE_CONNECTION_ERROR',
                    ]));

                    return $response->withHeader('Content-Type', 'application/json');
                }

                // Avoid Twig here because it may touch the database
                $response->getBody()->write(
                    \file_get_contents(APP_ROOT . '/views/database-connection-error.html')
                );

                return $response;
            }

            if ($json_response === true) {
                $error_label = $is_http_exception ? match ($status_code) {
                    403     => 'FORBIDDEN',
                    404     => 'NOT_FOUND',
                    405     => 'METHOD_NOT_ALLOWED',
                    default => null,
                } : null;

                $response_code = $error_label !== null ? $status_code : 500;
                $response      = $this->response_factory->createResponse($response_code);
                $response->getBody()->write(\json_encode([
                    'success'    => false,
                    'error_code' => $response_code,
                    'error'      => $error_label ?? 'INTERNAL_SERVER_ERROR',
                ]));

                return $response->withHeader('Content-Type', 'application/json');
            }

            // Check if HTTP code has a unique error page
            $template_file = \sprintf('error/%d.html.twig', $status_code);
            if ($is_http_exception && \file_exists(APP_ROOT . '/views/' . $template_file)) {
                $response = $this->response_factory->createResponse($status_code);
                $response->getBody()->write(
                    $this->twig->render($template_file)
                );
            } else {
                $response = $this->response_factory->createResponse(500);
                $response->getBody()->write(
                    $this->twig->render('error/500.html.twig')
                );
            }

            return $response;
        }
    }
}
