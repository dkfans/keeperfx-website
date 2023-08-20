<?php

namespace App\Controller\Workshop;

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

class WorkshopMapNumberListController {

    public function mapListIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){

        $map_numbers = [];
        for($i = 202; $i < 32767; $i++)
        {
            $map_numbers[$i] = [
                'available'     => true,
                'workshop_item' => null,
            ];
        }

        $items = $em->getRepository(WorkshopItem::class)->findBy(['category' => WorkshopCategory::Map], ['map_number' => 'ASC']);
        foreach($items as $item){
            if($item->getMapNumber() === null)
            {
                continue;
            }

            $map_numbers[$item->getMapNumber()] = [
                'available'     => false,
                'workshop_item' => $item,
            ];
        }

        $response->getBody()->write(
            $twig->render('workshop/mapnumber.list.map.html.twig', ['map_numbers' => $map_numbers])
        );
        return $response;

    }

}
