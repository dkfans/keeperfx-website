<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use App\Enum\WorkshopType;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\FlashMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminWorkshopController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('control-panel/admin/workshop/workshop.admin.cp.html.twig', [
                'workshop_items'   => $em->getRepository(WorkshopItem::class)->findBy(['is_accepted' => true]),
                'open_submissions' => $em->getRepository(WorkshopItem::class)->findBy(['is_accepted' => false]),
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

    public function itemUpdate(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            return $response;
        }

        $uploaded_files = $request->getUploadedFiles();
        $post           = $request->getParsedBody();

        $name                  = (string) ($post['name'] ?? null);
        $description           = (string) ($post['description'] ?? null);
        $install_instructions  = (string) ($post['install_instructions'] ?? null);

        $workshop_item->setName($name);
        $workshop_item->setDescription($description);
        $workshop_item->setInstallInstructions($install_instructions);

        $type = WorkshopType::tryFrom((int) ($post['type'] ?? null));
        $workshop_item->setType($type);

        $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
        $workshop_item->setMinGameBuild($min_game_build ?? null);

        $workshop_item->setIsAccepted(isset($post['is_accepted']));

        if(!empty($uploaded_files['file']) && $uploaded_files['file']->getError() !== UPLOAD_ERR_NO_FILE){
            die('updating file');
        }

        $em->flush();

        $flash->success('Workshop item updated!');

        $response = $response->withHeader('Location', '/admin/workshop/list')->withStatus(302);
        return $response;

    }

}
