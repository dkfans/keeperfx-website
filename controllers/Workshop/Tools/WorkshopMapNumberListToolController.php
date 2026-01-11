<?php

namespace App\Controller\Workshop\Tools;

use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use URLify;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopMapNumberListToolController
{

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $items = $em->getRepository(WorkshopItem::class)->findBy(['category' => WorkshopCategory::Map], ['map_number' => 'ASC']);
        foreach ($items as $item) {
            if ($item->getMapNumber() === null) {
                continue;
            }

            $map_numbers[$item->getMapNumber()] = [
                'workshop_item' => $item,
            ];
        }

        $response->getBody()->write(
            $twig->render('workshop/tools/mapnumber_list_tool.html.twig', ['map_numbers' => $map_numbers])
        );

        return $response;
    }
}
