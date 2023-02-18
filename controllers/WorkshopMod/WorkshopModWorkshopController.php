<?php

namespace App\Controller\WorkshopMod;

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

class WorkshopModWorkshopController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('cp/workshop-mod/workshop/workshop.workshop-mod.cp.html.twig', [
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
            $twig->render('cp/workshop-mod/workshop/workshop.item.workshop-mod.cp.html.twig', [
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

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

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

        // Update original author
        if(\is_string($original_author) && !empty($original_author)){
            $workshop_item->setOriginalAuthor($original_author);
        } else {
            $workshop_item->setOriginalAuthor(null);
        }

        // Update original creation date
        if(\is_string($original_creation_date) && !empty($original_creation_date)){
            try {
                $datetime = new \DateTime($original_creation_date);
                if($datetime){
                    $workshop_item->setOriginalCreationDate($datetime);
                }
            } catch (\Exception $ex){}
        } else {
            $workshop_item->setOriginalCreationDate(null);
        }

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

        // Update or set thumbnail
        if(!empty($uploaded_files['thumbnail']) && $uploaded_files['thumbnail']->getError() !== UPLOAD_ERR_NO_FILE){

            $thumbnail_file     = $uploaded_files['thumbnail'];
            $thumbnail_filename = $thumbnail_file->getClientFilename();
            $thumbnail_path     = $workshop_item_dir . '/' . $thumbnail_filename;

            // Remove already existing thumbnail
            if($workshop_item->getThumbnail() !== null){
                $current_thumbnail_path = $workshop_item_dir . '/' . $workshop_item->getThumbnail();
                if(\file_exists($current_thumbnail_path)){
                    \unlink($current_thumbnail_path);
                }
            }

            $thumbnail_file->moveTo($thumbnail_path);

            if(\file_exists($thumbnail_path)){
                $workshop_item->setThumbnail($thumbnail_filename);
            }
        }

        // Write changes to DB
        $em->flush();

        $flash->success('Workshop item updated!');
        $response = $response->withHeader('Location', '/workshop-mod/workshop/' . $workshop_item->getId())->withStatus(302);
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
                    $response = $response->withHeader('Location', '/workshop-mod/workshop/' . $workshop_item->getId())->withStatus(302);
                    return $response;
                }
            }
        }


        $flash->warning('Failed to remove screenshot.');
        $response = $response->withHeader('Location', '/workshop-mod/workshop/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
