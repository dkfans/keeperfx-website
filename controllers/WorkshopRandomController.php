<?php

namespace App\Controller;

use App\Enum\WorkshopType;

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

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getWorkshopOptions(): array
    {
        // TODO: improve the name of this function
        return [
            'types'  => WorkshopType::cases(),
            'tags'   => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
            'builds' => $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
        ];
    }

    public function navRandomItem(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $item_type
    ){
        $type = match($item_type){
            default    => WorkshopType::Map,
            'map'      => WorkshopType::Map,
            'campaign' => WorkshopType::Campaign,
        };

        $workshop_items = $em->getRepository(WorkshopItem::class)->findBy(
            ['is_accepted' => true, 'type' => $type->value]
        );

        if(empty($workshop_items)){
            $flash->warning('Random workshop item not found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        $item = $workshop_items[\random_int(0, \count($workshop_items) - 1)];

        $response = $response->withHeader('Location',
            '/workshop/item/' . $item->getId() . '/' . URLify::slug($item->getName())
        )->withStatus(302);

        return $response;
    }

}
