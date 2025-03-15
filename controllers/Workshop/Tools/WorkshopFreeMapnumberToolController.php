<?php

namespace App\Controller\Workshop\Tools;

use App\FlashMessage;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * A tool to compare CFGs and show the differences.
 * This is useful for getting only updated properties from KeeperFX configs.
 */
class WorkshopFreeMapnumberToolController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ){
        // Response
        $response->getBody()->write(
            $twig->render('workshop/tools/free_mapnumber_tool.html.twig')
        );
        return $response;
    }
}
