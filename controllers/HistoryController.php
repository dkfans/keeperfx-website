<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HistoryController
{

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ) {
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
                        "\n\nThe goal was to rewrite the code that was used in Syndicate Wars, but the Keeper Klan community got Mefisto into Dungeon Keeper. " .
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
                    'title'       => 'KeeperFX gets its own subforum',
                    'date'        => new \DateTime('2009-09-04'),
                    'date_format' => 'Y-m-d',
                    'description' => "Dotted created the new Keeper Klan forum and the community moved from the old InvisionFree forum to the new home. " .
                        "During this move he created a KeeperFX subforum and made Mefisto its moderator.",
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
                    'title'       => 'The game logic gets decoupled from the FPS',
                    'date'        => new \DateTime('2022-08-15'),
                    'date_format' => 'Y-m-d',
                    'description' => 'rainlizard implemented Delta Time which decouples the game logic from the graphics loop, making the game always run at the same speed regardless of what FPS a player has.',
                ],
                [
                    'title'       => 'First decent online Multiplayer implemented',
                    'date'        => new \DateTime('2022-10-30'),
                    'date_format' => 'Y-m-d',
                    'description' => 'TheSim finished adding ENET as a new Multiplayer protocol, which is stable enough to allow 2 Keepers to play over the internet over short distances. It still requires a very fast connection, but is much better than the earlier implemented protocols.',
                ],
                [
                    'title'       => 'Dungeon Keeper completely rewritten',
                    'date'        => new \DateTime('2022-12-28'),
                    'date_format' => 'Y-m-d',
                    'description' => 'qqluqq rewrote the last parts of the DK code, making it so the DLL is no longer required and KeeperFX exists as its own program. This change makes it so the next big KeeperFX version will start at 1.0.0.',
                ],
                [
                    'title'       => 'KeeperFX.net launched',
                    'date'        => new \DateTime('2023-01-08'),
                    'date_format' => 'Y-m-d',
                    'description' => 'KeeperFX.net goes live. Yani created the website which now serves as the main homepage for KeeperFX.',
                ],
                [
                    'title'       => 'The Workshop is released on the KeeperFX.net website',
                    'date'        => new \DateTime('2023-03-20'),
                    'date_format' => 'Y-m-d',
                    'description' => 'The official Workshop gets released and allows users to easily share their creations and find other players\' content.',
                ],
                [
                    'title'       => 'First KeeperFX tournament',
                    'date'        => new \DateTime('2023-08-24'),
                    'date_format' => 'Y-m-d',
                    'description' => 'The first KeeperFX tournament has started. Featuring 11 players from Europe and 1 from Russia.',
                ],
                [
                    'title'       => 'KeeperFX 1.0.0 is released!',
                    'date'        => new \DateTime('2023-11-10'),
                    'date_format' => 'Y-m-d',
                    'description' => 'The long awaited 1.0.0 has been released! This is the first full version that makes no more use of the original converted DLL.',
                ],
                [
                    'title'       => 'AncientWay won the first KeeperFX tournament',
                    'date'        => new \DateTime('2023-12-29'),
                    'date_format' => 'Y-m-d',
                    'description' => "AncientWay won in the finals against Spatulade during the first official KeeperFX tournament. **Congratulations!**  \nThird and fourth place go to Biervampir and Loobinex.",
                ],
                [
                    'title'       => 'New Keeper colors! Orange, Pink and Black',
                    'date'        => new \DateTime('2024-05-10'),
                    'date_format' => 'Y-m-d',
                    'description' => "qqluqq implemented 3 new Keepers which can be played as and fought against. These Keepers use the colored icons that are made by Spatulade.",
                ],
                [
                    'title'       => 'First Linux compilation possible',
                    'date'        => new \DateTime('2024-09-24'),
                    'date_format' => 'Y-m-d',
                    'description' => "xtremeqg shared his PR that allows KeeperFX to be natively compiled on Linux. It still needs a lot of work, but a Linux release is getting very close!",
                ],
                [
                    'title'       => 'LUA support has been added',
                    'date'        => new \DateTime('2025-05-03'),
                    'date_format' => 'Y-m-d',
                    'description' => "qqluqq has added support for the LUA scripting language to KeeperFX, opening the door to powerful new gameplay features, custom logic, and modding capabilities.",
                ],
                [
                    'title'       => 'New Launcher and Web Installer',
                    'date'        => new \DateTime('2025-07-20'),
                    'date_format' => 'Y-m-d',
                    'description' => "The new KeeperFX launcher made by Yani has come out of beta testing and has been made available on the website. It comes with a web installer that downloads and installs KeeperFX which should streamline the process for the less tech-savvy users. It also has a lot of new exiting features such as automatic updating, crash reports, an online port checker, and much more.",
                ],
            ]])
        );

        return $response;
    }
}
