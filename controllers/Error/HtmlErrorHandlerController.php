<?php

namespace App\Controller\Error;

use DI\Bridge\Slim\CallableResolver;
use Psr\Container\ContainerInterface;
use Twig\Environment as TwigEnvironment;
use Slim\Psr7\Factory\ResponseFactory;

use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\CallableResolverInterface;

class HtmlErrorHandlerController extends ErrorHandler
{
    private TwigEnvironment $twig;

    public function __construct(
        ResponseFactory $response_factory,
        CallableResolver $callable_resolver,
        TwigEnvironment $twig,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($callable_resolver, $response_factory, $logger);
        $this->twig = $twig;
    }

    protected function respond(): ResponseInterface
    {
        // Create a response

        // Log error if not 404
        if($this->logger && !\in_array($this->exception->getCode(), [404])){
            $this->logger->error($this->exception->getMessage());
        }

        // Check if HTTP code has a unique error page
        $template_file = \sprintf('error/%d.html.twig', $this->exception->getCode());
        if(\file_exists(APP_ROOT . '/views/' . $template_file)){
            // Show template
            $response = $this->responseFactory->createResponse($this->exception->getCode());
            $response->getBody()->write(
                $this->twig->render($template_file)
            );
        } else {
            // Show default template (500 - Server error)
            $response = $this->responseFactory->createResponse(500);
            $response->getBody()->write(
                $this->twig->render('error/500.html.twig')
            );
        }

        return $response;
    }
}
