<?php

namespace App\Controller\Workshop\Tools;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;

/**
 * A tool to compare CFGs and show the differences.
 * This is useful for getting only updated properties from KeeperFX configs.
 */
class WorkshopTrainingLevelToolController
{
    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ) {
        $response->getBody()->write(
            $twig->render('workshop/tools/training_level_tool.html.twig')
        );

        return $response;
    }
}
