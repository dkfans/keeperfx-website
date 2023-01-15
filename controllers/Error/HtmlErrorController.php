<?php

namespace App\Controller\Error;

use Twig\Environment;

use Slim\Exception\HttpException;
use Slim\Exception\HttpNotFoundException;

use Slim\Interfaces\ErrorRendererInterface;

class HtmlErrorController implements ErrorRendererInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        // 404 - Not Found
        if($exception instanceof HttpNotFoundException){
            return $this->twig->render('error/404.html.twig');
        }

        // 500 - Server error
        return $this->twig->render('error/500.html.twig');
    }
}
