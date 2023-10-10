<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoreController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('lore.html.twig', ['lore_entries' => [
                [
                    'title'       => 'Dungeon Keeper is released',
                    'date'        => new \DateTime('1997-06-26'),
                    'date_format' => 'Y-m-d',
                    'description' => null,
                ],
                [
                    'title'       => 'KeeperFX becomes playable',
                    'date'        => new \DateTime('2008-09-07'),
                    'date_format' => 'Y-m-d',
                    'description' => 'KeeperFX is now playable for the first time. It uses the original DK executable as a DLL. This means the original DK code is loaded if those parts are not rewritten for KeeperFX yet.',
                ],
                [
                    'title'       => 'Launch of Keeper Klan Discord server',
                    'date'        => new \DateTime('2018-08-19'),
                    'date_format' => 'Y-m-d',
                    'description' => 'The Keeper Klan discord server is created, which brought a lot of KeeperFX developers together.',
                ],
                [
                    'title'       => 'Delta Time has been implemented',
                    'date'        => new \DateTime('2022-08-15'),
                    'date_format' => 'Y-m-d',
                    'description' => 'rainlizard implemented Delta Time which decouples the game logic from the FPS, making the game frame rate independent.',
                ],
                [
                    'title'       => 'The ENET multiplayer protocol has been added',
                    'date'        => new \DateTime('2022-10-30'),
                    'date_format' => 'Y-m-d',
                    'description' => 'TheSim finished adding ENET as a new Multiplayer protocol, which is stable enough to allow 2 Keepers to play over the internet. It still required a very fast connection.',
                ],
                [
                    'title'       => 'Dungeon Keeper completely rewritten',
                    'date'        => new \DateTime('2022-12-28'),
                    'date_format' => 'Y-m-d',
                    'description' => 'qqluqq rewrote the last parts of the DK code, making it so the DLL is no longer required and KeeperFX exists as its own program.',
                ],
                [
                    'title'       => 'KeeperFX.net launched',
                    'date'        => new \DateTime('2023-01-08'),
                    'date_format' => 'Y-m-d',
                    'description' => 'KeeperFX.net goes live. Many kisses to Yani who coded the whole website.',
                ],
                [
                    'title'       => 'First KeeperFX tournament',
                    'date'        => new \DateTime('2023-08-24'),
                    'date_format' => 'Y-m-d',
                    'description' => 'The first KeeperFX tournament has started. Featuring 11 players from Europe and 1 from Russia.',
                ],
            ]])
        );

        return $response;
    }

}
