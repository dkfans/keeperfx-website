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

class WorkshopRandomController {

    public function navRandomItem(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $item_category
    ){
        $category = match($item_category){
            default    => WorkshopCategory::Map,
            'map'      => WorkshopCategory::Map,
            'campaign' => WorkshopCategory::Campaign,
        };

        $workshop_items = $em->getRepository(WorkshopItem::class)->findBy(
            ['is_published' => true, 'category' => $category->value]
        );

        if(empty($workshop_items)){
            $flash->warning('Random workshop item not found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        $item = $workshop_items[\random_int(0, \count($workshop_items) - 1)];

        $response = $response->withHeader('Location',
            '/workshop/item/' . $item->getId() . '/' . URLify::slug($item->getName()) . '#nav-top'
        )->withStatus(302);

        return $response;
    }

}
