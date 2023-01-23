<?php

namespace App\Controller;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopType;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopController {

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getTypesAndTags(): array
    {
        $types = $this->em->getRepository(WorkshopType::class)->findBy([], ['name' => 'ASC']);
        $tags  = $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']);

        return [
            'types' => $types,
            'tags'  => $tags,
        ];
    }

    public function workshopIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('workshop/workshop.html.twig', $this->getTypesAndTags())
        );

        return $response;
    }

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', $this->getTypesAndTags())
        );

        return $response;
    }

    public function uploadIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('workshop/upload.workshop.html.twig', $this->getTypesAndTags())
        );

        return $response;
    }

    public function upload(
        Request $request,
        Response $response
    ){
        $response = $response->withHeader('Location', '/workshop/upload')->withStatus(302);
        return $response;
    }

}
