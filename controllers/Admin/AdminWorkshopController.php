<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use App\Enum\WorkshopType;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminWorkshopController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $open_submissions = $em->getRepository(WorkshopItem::class)->findBy(['is_accepted' => false]);

        $response->getBody()->write(
            $twig->render('control-panel/admin/workshop/workshop.admin.cp.html.twig', [
                'open_submissions' => $open_submissions
            ])
        );

        return $response;
    }

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $id
    ){
        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            // TODO: flashmessage & redirect
            return $response;
        }

        // Get screenshots
        $screenshots = [];
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(\is_dir($screenshot_dir)){
            foreach(\glob($screenshot_dir . '/*') as $screenshot_file){

                $size = \getimagesize($screenshot_file);

                $screenshots[] = [
                    'filename' => \basename($screenshot_file),
                    'width'    => $size[0],
                    'height'   => $size[1],
                ];
            }
        }

        $response->getBody()->write(
            $twig->render('control-panel/admin/workshop/workshop.item.admin.cp.html.twig', [
                'workshop_item' => $workshop_item,
                'types'         => WorkshopType::cases(),
                'tags'          => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'builds'        => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
                'screenshots'   => $screenshots,
            ])
        );

        return $response;
    }

}
