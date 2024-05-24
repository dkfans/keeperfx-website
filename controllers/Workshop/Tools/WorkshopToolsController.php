<?php

namespace App\Controller\Workshop\Tools;

use App\Entity\User;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopToolsController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
    ){

        $response->getBody()->write(
            $twig->render('workshop/tools/tools.list.workshop.html.twig', ['tools' => [
                [
                    'title'       => 'KeeperFX ENET Multiplayer Lobby Host Checker',
                    'url'         => '/workshop/tools/kfx-host-checker',
                    'description' => 'A tool to check if a KeeperFX ENET multiplayer lobby can be joined by other players.',
                    'user' => $em->getRepository(User::class)->findOneBy(['username' => 'Yani'])
                ],
                [
                    'title'       => 'KeeperFX CFG Diff Tool',
                    'url'         => '/workshop/tools/kfx-cfg-diff',
                    'description' => 'A tool to get the changes between two KeeperFX configuration files.',
                    'user'        => $em->getRepository(User::class)->findOneBy(['username' => 'Yani'])
                ],
            ]])
        );

        return $response;
    }
}
