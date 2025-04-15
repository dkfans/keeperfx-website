<?php

namespace App\Controller\DevCP;

use App\Entity\CrashReport;

use App\FlashMessage;
use App\Config\Config;
use App\Entity\GameFileIndex;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Enum\ReleaseType;

class ModerateGameFilesController
{
    // index

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('devcp/game-files/game-files.list.devcp.html.twig', [
                'stable_game_file_indexes' => $em->getRepository(GameFileIndex::class)->findBy(['release_type' => 'STABLE'],['id' => 'DESC']),
                'alpha_game_file_indexes'  => $em->getRepository(GameFileIndex::class)->findBy(['release_type' => 'ALPHA'],['id' => 'DESC']),
            ])
        );

        return $response;
    }

    public function view(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $type,
        $version
    ){

        // Try and get the release type
        try {
            $release_type = ReleaseType::from($type);
        } catch (\ValueError $ex){
            throw new HttpNotFoundException($request);
        }

        // Find game file index
        $game_file_index = $em->getRepository(GameFileIndex::class)->findOneBy(['release_type' => $release_type, 'version' => $version]);
        if(!$game_file_index){
            throw new HttpNotFoundException($request);
        }

        // Show output
        $response->getBody()->write(
            $twig->render('devcp/game-files/game-files.devcp.html.twig', [
                'game_file_index' => $game_file_index
            ])
        );
        return $response;
    }
}
