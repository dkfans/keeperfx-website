<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HistoryController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('history.html.twig', ['history_entries' => [
                [
                    'title'       => 'Dungeon Keeper is released',
                    'date'        => new \DateTime('1997-06-26'),
                    'date_format' => 'Y-m-d',
                    'description' => null,
                ],
                [
                    'title'       => 'The Deeper Dungeons expansion pack is released',
                    'date'        => new \DateTime('1997-11-30'),
                    'date_format' => 'Y-m-d',
                    'description' => 'An expansion pack is released and comes with 30 new maps. It also features a much better AI and some bugfixes.',
                ],
                [
                    'title'       => 'Mefistotelis starts working on rewriting the game',
                    'date'        => new \DateTime('2008-04'),
                    'date_format' => 'Y-m',
                    'description' => "Tomasz Lis, also known as Mefistotelis or Mefisto, started working on rewriting Dungeon Keeper because of his interest in rewriting Syndicate Wars. \n\n" .
                                     "He was looking for _debug info_ in the Syndicate Wars files, but didn\'t find any. " .
                                     "So he started asking around forums, and the Keeper Klan community pointed him towards Dungeon Keeper because they both used the same code base. " .
                                     "He then found the _debug info_ he was after and started working on the project. " .
                                     "\n\nThe goal was to rewrite the code that was used in Syndicate Wars, but the Keeper Klan community got Mefisto\'s into Dungeon Keeper. " .
                                     "Mefisto\'s idea was to convert the original Dungeon Keeper executable into a DLL file, so other programs can execute its code.",
                ],
                [
                    'title'       => 'Mefisto shares the first of his work',
                    'date'        => new \DateTime('2008-06-10'),
                    'date_format' => 'Y-m-d',
                    'description' => 'Mefisto shares the first version of his work. It has no name and is just an executable that runs the DLL file. The DLL file is a converted Dungeon Keeper Gold executable.',
                ],

                [
                    'title'       => 'First "KeeperFX" version is released',
                    'date'        => new \DateTime('2008-09-07'),
                    'date_format' => 'Y-m-d',
                    'description' => 'Mefisto releases version 0.1.1 of what he now calls "KeeperFX".',
                ],

                [
                    'title'       => 'Loobinex releases an unofficial version of KeeperFX',
                    'date'        => new \DateTime('2016-08-06'),
                    'date_format' => 'Y-m-d',
                    'description' => 'Because Mefisto has been on a break, Loobinex (a.k.a. YourMaster) decided to release an unofficial version of KeeperFX that contains new fixes.',
                ],
                [
                    'title'       => 'Mefistotelis stops working on KeeperFX',
                    'date'        => new \DateTime('2016-09'),
                    'date_format' => 'Y-m',
                    'description' => 'Mefistotelis stated that his break is final and that he will stop working on KeeperFX.',
                ],
                [
                    'title'       => 'Launch of Keeper Klan Discord server',
                    'date'        => new \DateTime('2018-08-19'),
                    'date_format' => 'Y-m-d',
                    'description' => 'The Keeper Klan discord server was created, which eventually brought a lot of KeeperFX developers together.',
                ],
                [
                    'title'       => 'Loobinex becomes the new maintainer',
                    'date'        => new \DateTime('2020-04-25'),
                    'date_format' => 'Y-m-d',
                    'description' => 'Loobinex took the task of maintaining the project on himself with the blessing of Mefistotelis.',
                ],
                [
                    'title'       => 'Delta Time is implemented',
                    'date'        => new \DateTime('2022-08-15'),
                    'date_format' => 'Y-m-d',
                    'description' => 'rainlizard implemented Delta Time which decouples the game logic from the graphics loop, making the game always run at the same speed regardless of what FPS a player has.',
                ],
                [
                    'title'       => 'ENET multiplayer protocol added',
                    'date'        => new \DateTime('2022-10-30'),
                    'date_format' => 'Y-m-d',
                    'description' => 'TheSim finished adding ENET as a new Multiplayer protocol, which is stable enough to allow 2 Keepers to play over the internet. It still requires a very fast connection.',
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
                    'description' => 'KeeperFX.net goes live. Yani created the website which now serves as the main homepage for KeeperFX.',
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
