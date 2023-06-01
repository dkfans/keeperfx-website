<?php

namespace App\Controller\ModCP\Workshop;

use App\Entity\WorkshopItem;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModerateWorkshopController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('modcp/workshop/workshop.modcp.html.twig', [
                'workshop_items'   => $em->getRepository(WorkshopItem::class)->findBy([],['id' => 'DESC'])
            ])
        );

        return $response;
    }
}
