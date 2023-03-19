<?php

namespace App\Controller\ControlPanel;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;

class DashboardController {

    public function dashboardIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){

        $response->getBody()->write(
            $twig->render('cp/dashboard.cp.html.twig')
        );

        return $response;
    }

}
