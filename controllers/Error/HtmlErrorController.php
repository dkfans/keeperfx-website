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
        // Check if HTTP code has a unique error page
        $template_file = \sprintf('error/%d.html.twig', $exception->getCode());
        if(\file_exists(APP_ROOT . '/views/' . $template_file)){

            // Show template
            return $this->twig->render($template_file);
        }

        // Show default template (500 - Server error)
        return $this->twig->render('error/500.html.twig');
    }
}
