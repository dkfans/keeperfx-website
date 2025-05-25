<?php

namespace App\Controller\Workshop\Tools;

use App\FlashMessage;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;
use Xenokore\Utility\Helper\StringHelper;

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
