<?php

namespace App\Controller\ModCP\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use App\Account;
use Slim\Csrf\Guard;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;
use App\Workshop\Exception\WorkshopException;
use App\Entity\WorkshopImage;
use App\UploadSizeHelper;

class ModerateWorkshopAddController {


    public function itemAddIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){

        $response->getBody()->write(
            $twig->render('modcp/workshop/add.workshop.modcp.html.twig', [
                'categories' => WorkshopCategory::cases(),
                'tags'       => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'builds'     => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
            ])
        );

        return $response;

    }

    public function itemAdd(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        Account $account,
        FlashMessage $flash
    ){
        $success = true;

        $uploaded_files        = $request->getUploadedFiles();
        $post                  = $request->getParsedBody();

        $name                  = \trim((string) ($post['name'] ?? null));
        $description           = \trim((string) ($post['description'] ?? null));
        $install_instructions  = \trim((string) ($post['install_instructions'] ?? null));

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        // Check if name is valid
        if(!$name){
            $success = false;
            $flash->warning('Please enter a name for this workshop item');
        }

        // Check if category is valid
        $category = WorkshopCategory::tryFrom((int) ($post['category'] ?? null));
        if($category === null){
            $flash->warning('Invalid workshop category');
            $success = false;
        }

        // Check if a file was uploaded
        if(empty($uploaded_files['file']) || !($uploaded_files['file'] instanceof UploadedFileInterface) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('You did not submit a file');
            $success = false;
        }

        // Check valid screenshot files
        if(!empty($uploaded_files['screenshots'])){
            /** @var UploadedFile $screenshot_file */
            foreach($uploaded_files['screenshots'] as $screenshot_file){

                // NO screenshots were added
                if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $filename = $screenshot_file->getClientFilename();
                $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
                if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                    $success = false;
                    $flash->warning('One or more screenshots are invalid. Allowed file types: jpg, jpeg, png, gif, webp');
                }
            }
        }

        // Check valid thumbnail file
        if(!empty($uploaded_files['thumbnail']) && $uploaded_files['thumbnail']->getError() !== UPLOAD_ERR_NO_FILE){
            /** @var UploadedFile $thumbnail_file */
            $thumbnail_file = $uploaded_files['thumbnail'];

            $filename = $thumbnail_file->getClientFilename();
            $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
            if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                $success = false;
                $flash->warning('Invalid thumbnail. Allowed file types: jpg, jpeg, png, gif, webp');
            }
        }


        $submitter          = null;
        $submitter_value    = $post['submitter'] ?? null;

        // Handle submitter
        if(!\in_array($submitter_value, ['current_user', 'kfx', 'username'])){
            $flash->error('Invalid submitter.');
            $success = false;
        } else {

            // Current logged in user
            if($submitter_value === 'current_user'){
                $submitter = $account->getUser();

            // KeeperFX Team
            } elseif($submitter_value === 'kfx') {
                $submitter = null;

            // Custom user
            } elseif($submitter_value === 'username') {

                $submitter_username = (string) ($post['submitter_username'] ?? '');

                // Check valid username for custom user
                if(empty($submitter_username)){
                    $success = false;
                    $flash->warning('No username given for custom submitter.');
                } else {

                    // Search user
                    $submitter_user = $em->getRepository(User::class)->findOneBy(['username' => $submitter_username]);
                    if(!$submitter_user){
                        $success = false;
                        $flash->warning("User '{$submitter_username}' not found ");
                    } else {
                        $submitter = $submitter_user;
                    }
                }

            } else {
                // Invalid submitter value
                return $response;
            }

        }

        // Return the page if submission is invalid
        if(!$success){
            $response->getBody()->write(
                $twig->render('modcp/workshop/add.workshop.modcp.html.twig', [
                    'categories' => WorkshopCategory::cases(),
                    'tags'       => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                    'builds'     => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
                ])
            );
            return $response;
        }

        // Create the item in DB
        $workshop_item = new WorkshopItem();
        $workshop_item->setName($name);
        $workshop_item->setSubmitter($submitter);
        $workshop_item->setCategory($category);
        $workshop_item->setIsPublished(isset($post['is_published']));

        if(!empty($description)){
            $workshop_item->setDescription($description);
        }

        if(!empty($install_instructions)){
            $workshop_item->setInstallInstructions($install_instructions);
        }

        if(\is_string($original_author) && !empty($original_author)){
            $workshop_item->setOriginalAuthor($original_author);
        }

        if(\is_string($original_creation_date) && !empty($original_creation_date)){
            try {
                $datetime = new \DateTime($original_creation_date);
                if($datetime){
                    $workshop_item->setOriginalCreationDate($datetime);
                }
            } catch (\Exception $ex){}
        }

        // Set optional minimum game build
        if(isset($post['min_game_build']) && !empty($post['min_game_build'])){
            $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
            if($min_game_build){
                $workshop_item->setMinGameBuild($min_game_build);
            }
        }

        $em->persist($workshop_item);
        $em->flush(); // flush because we need ID for creating storage directory

        // Create directories for files
        $workshop_item_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_screenshots_dir = $workshop_item_dir . '/screenshots';
        if(!DirectoryHelper::create($workshop_item_dir)){
            throw new \Exception('Failed to create workshop item storage dir');
        }
        if(!DirectoryHelper::create($workshop_item_screenshots_dir)){
            throw new \Exception('Failed to create workshop item screenshots dir');
        }

        // Store the uploaded file
        // TODO: allow specific files only (archives .7z, .zip, .rar, etc)
        $file = $uploaded_files['file'];
        $filename = $file->getClientFilename();
        $path = $workshop_item_dir . '/' . $filename;
        $file->moveTo($path);
        if(!\file_exists($path)){
            throw new \Exception('Failed to move workshop item file');
        }

        $workshop_item->setFilename($filename);

        // Store any uploaded screenshots
        $screenshot_files = $uploaded_files['screenshots'] ?? [];
        if(!empty($screenshot_files)){
            foreach($screenshot_files as $screenshot_file){
                // NO screenshots were added
                if ($screenshot_file->getError() === \UPLOAD_ERR_NO_FILE) {
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
        }

        // Store thumbnail
        $thumbnail_file = $uploaded_files['thumbnail'] ?? null;
        if($thumbnail_file && $thumbnail_file->getError() !== UPLOAD_ERR_NO_FILE){

            // Generate thumbnail output path
            $ext = \strtolower(\pathinfo($thumbnail_file->getClientFilename(), \PATHINFO_EXTENSION));
            $str = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
            $thumbnail_filename = 'thumbnail_' . $str . '.' . $ext;
            $path = $workshop_item_dir . '/' . $thumbnail_filename;

            // Move thumbnail
            $thumbnail_file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item thumbnail');
            }

            $workshop_item->setThumbnail($thumbnail_filename);
        }

        // Flush again so filenames are added to DB entity
        $em->flush();

        $flash->success('The workshop item has been created.');

        $response = $response->withHeader('Location', '/moderate/workshop/list')->withStatus(302);
        return $response;

    }

}
