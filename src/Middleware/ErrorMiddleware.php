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

class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactory $response_factory,
        private TwigEnvironment $twig,
        private LoggerInterface $logger,
    )
    {}

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

            // Log error if not a normal HTTP exception
            if($this->logger && !($ex instanceof HttpSpecializedException)){
                $this->logger->error($ex->getMessage());
            }

            // Check if HTTP code has a unique error page
            $template_file = \sprintf('error/%d.html.twig', $ex->getCode());
            if(\file_exists(APP_ROOT . '/views/' . $template_file)){
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

            return $response;
        }
    }
}
