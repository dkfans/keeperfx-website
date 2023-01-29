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
use App\Account;
use Slim\Csrf\Guard;
use Slim\Exception\HttpNotFoundException;

class AdminWorkshopController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('cp/admin/workshop/workshop.admin.cp.html.twig', [
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
            $twig->render('cp/admin/workshop/workshop.item.admin.cp.html.twig', [
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
        $workshop_item->setIsAccepted(isset($post['is_accepted']));

        // Set workshop item type
        $type = WorkshopType::tryFrom((int) ($post['type'] ?? null));
        $workshop_item->setType($type);

        // Set minimum game build
        $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
        $workshop_item->setMinGameBuild($min_game_build ?? null);

        // Set directories for files
        $workshop_item_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_screenshots_dir = $workshop_item_dir . '/screenshots';

        // Update workshop file
        if(!empty($uploaded_files['file']) && $uploaded_files['file']->getError() !== UPLOAD_ERR_NO_FILE){

            $file     = $uploaded_files['file'];
            $filename = $file->getClientFilename();
            $path     = $workshop_item_dir . '/' . $filename;

            $workshop_item->setFilename($filename);

            $file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item file');
            }
        }

        // Store any uploaded screenshots
        foreach($uploaded_files['screenshots'] as $screenshot_file){
            // NO screenshots were added
            if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Generate screenshot output path
            $ext = \strtolower(\pathinfo($screenshot_file->getClientFilename(), \PATHINFO_EXTENSION));
            $str = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
            $screenshot_filename = $str . '.' . $ext;
            $path = $workshop_item_screenshots_dir . '/' . $screenshot_filename;

            // Move screenshot
            $screenshot_file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item screenshot');
            }
        }

        // Write changes to DB
        $em->flush();

        $flash->success('Workshop item updated!');
        $response = $response->withHeader('Location', '/admin/workshop/' . $workshop_item->getId())->withStatus(302);
        return $response;

    }

    public function deleteScreenshot(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        Guard $csrf_guard,
        $id,
        $filename,
        $token_name,
        $token_value
    ){
        // Validate CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            return $response;
        }

        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Get screenshots
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(\is_dir($screenshot_dir)){
            foreach(\glob($screenshot_dir . '/*') as $screenshot_file){

                // Check if filename matches
                if(\basename($screenshot_file) === $filename){

                    // Remove screenshot
                    if(!\unlink($screenshot_file)){
                        // TODO: notice on file delete error
                    }

                    $flash->success('Screenshot removed!');
                    $response = $response->withHeader('Location', '/admin/workshop/' . $workshop_item->getId())->withStatus(302);
                    return $response;
                }
            }
        }


        $flash->warning('Failed to remove screenshot.');
        $response = $response->withHeader('Location', '/admin/workshop/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
