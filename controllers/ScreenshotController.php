<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ScreenshotController {

    public function screenshotsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('screenshots.html.twig')
        );

        return $response;
    }
}
