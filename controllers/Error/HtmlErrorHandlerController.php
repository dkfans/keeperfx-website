<?php

namespace App\Controller\Error;

use Twig\Environment;
use Slim\Psr7\Factory\ResponseFactory;

use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpException;
use Slim\Exception\HttpNotFoundException;

class HtmlErrorHandlerController implements ErrorHandlerInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke(
        ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface
    {
        // Create a response
        $response_factory = new ResponseFactory();
        $response = $response_factory->createResponse($exception->getCode());

        // Check if HTTP code has a unique error page
        $template_file = \sprintf('error/%d.html.twig', $exception->getCode());
        if(\file_exists(APP_ROOT . '/views/' . $template_file)){
            // Show template
            $response->getBody()->write(
                $this->twig->render($template_file)
            );
        } else {
            // Show default template (500 - Server error)
            $response->getBody()->write(
                $this->twig->render('error/500.html.twig')
            );
        }

        return $response;
    }
}
