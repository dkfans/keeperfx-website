<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopDifficultyRating;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Entity\WorkshopFile;
use App\UploadSizeHelper;

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopUploadController {

    public function uploadIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('workshop/upload.workshop.html.twig')
        );

        return $response;
    }

    public function upload(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        UploadSizeHelper $upload_size_helper,
    ){

        $success = true;

        $uploaded_files        = $request->getUploadedFiles();
        $post                  = $request->getParsedBody();

        $name                  = \trim((string) ($post['name'] ?? null));
        $description           = \trim((string) ($post['description'] ?? null));
        $install_instructions  = \trim((string) ($post['install_instructions'] ?? null));

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        // Filter name (remove extra spaces)
        $name = \preg_replace('/\s+/', ' ', \trim($name));

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

        // Check if a workshop file was uploaded
        if(empty($uploaded_files['file']) || !($uploaded_files['file'] instanceof UploadedFileInterface) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('You did not submit a file');
            $success = false;
        } else {

            // Check workshop file filesize
            if($uploaded_files['file']->getSize() > $upload_size_helper->getFinalWorkshopItemUploadSize()){
                $flash->warning(
                    'Maximum upload size for workshop item exceeded. (' .
                    BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopItemUploadSize())->format() .
                    ')'
                );
                $success = false;
            }
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
                if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])){
                    $success = false;
                    $flash->warning('One or more screenshots are invalid. Allowed file types: jpg, jpeg, png, gif');
                } else {

                    // Check screenshot filesize
                    if($screenshot_file->getSize() > $upload_size_helper->getFinalWorkshopImageUploadSize()){
                        $flash->warning(
                            'Maximum upload size for workshop screenshot exceeded. (' .
                            BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopImageUploadSize())->format() .
                            ')'
                        );
                        $success = false;
                    }

                }
            }
        }

        // Check valid thumbnail file
        if(!empty($uploaded_files['thumbnail']) && $uploaded_files['thumbnail']->getError() !== UPLOAD_ERR_NO_FILE){
            /** @var UploadedFile $thumbnail_file */
            $thumbnail_file = $uploaded_files['thumbnail'];

            $filename = $thumbnail_file->getClientFilename();
            $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
            if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])){
                $success = false;
                $flash->warning('Invalid thumbnail. Allowed file types: jpg, jpeg, png, gif');
            } else {

                // Check filesize
                if($uploaded_files['thumbnail']->getSize() > $upload_size_helper->getFinalWorkshopImageUploadSize()){
                    $flash->warning(
                        'Maximum upload size for workshop item exceeded. (' .
                        BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopImageUploadSize())->format() .
                        ')'
                    );
                    $success = false;
                }
            }
        }

        // Return the page if submission is invalid
        if(!$success){
            // TODO: remove post vars (request twig extension)
            $response->getBody()->write(
                $twig->render('workshop/upload.workshop.html.twig', [
                    'name'                 => $name,
                    'description'          => $description,
                    'install_instructions' => $install_instructions,
                ])
            );
            return $response;
        }

        // Create the item in DB
        $workshop_item = new WorkshopItem();
        $workshop_item->setName($name);
        $workshop_item->setSubmitter($account->getUser());
        $workshop_item->setCategory($category);

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

        // Automatically publish item for accounts with a role of 'Moderator' or higher
        if($account->getUser()->getRole()->value >= UserRole::Moderator->value){
            $workshop_item->setIsPublished(true);
        }

        $em->persist($workshop_item);
        $em->flush(); // flush because we need ID for creating storage directory

        // Create directories for files
        $workshop_item_dir             = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_files_dir       = $workshop_item_dir . '/files';
        $workshop_item_screenshots_dir = $workshop_item_dir . '/screenshots';
        if(!DirectoryHelper::create($workshop_item_dir)){
            throw new \Exception('Failed to create workshop item storage dir');
        }
        if(!DirectoryHelper::create($workshop_item_files_dir)){
            throw new \Exception('Failed to create workshop item files dir');
        }
        if(!DirectoryHelper::create($workshop_item_screenshots_dir)){
            throw new \Exception('Failed to create workshop item screenshots dir');
        }

        // Get file and filename
        $file     = $uploaded_files['file'];
        $filename = $file->getClientFilename();

        // Get and set some variables for the new file
        $file_ext              = \pathinfo($filename, PATHINFO_EXTENSION);
        $file_storage_filename = \sha1($filename . time()) . $file_ext;
        $file_path             = $workshop_item_files_dir . '/' . $file_storage_filename;

        // Store the uploaded file
        $file->moveTo($file_path);
        if(!\file_exists($file_path)){
            throw new \Exception('Failed to move workshop item file');
        }

        $workshop_file = new WorkshopFile();
        $workshop_file->setFilename($filename);
        $workshop_file->setStorageFilename($file_storage_filename);
        $workshop_file->setItem($workshop_item);
        $em->persist($workshop_file);
        $em->flush();

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

        // Redirect accounts with a role higher than 'User' (because their item is automatically published)
        if($account->getUser()->getRole()->value >= UserRole::Moderator->value) {
            $flash->success('Workshop item successfully uploaded!');
            $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
        } else {

            // Redirect normal accounts
            $flash->success('Your workshop item has been submitted and will be soon be available for others to download.');
            $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
        }

        return $response;
    }


    public function checkMapNumber(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        $map_number,
    ){

        $map_number = (int)$map_number;

        // Check if map number is valid
        if($map_number < 202 || $map_number > 32767){
            $response->getBody()->write(
                \json_encode([
                    'success'    => true,
                    'map_number' => $map_number,
                    'available'  => false,
                ])
            );
            return $response;
        }

        // Check if a workshop item with this map number already exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->findOneBy(['map_number' => $map_number]);
        if($workshop_item){
            $response->getBody()->write(
                \json_encode([
                    'success'    => true,
                    'map_number' => $map_number,
                    'available'  => false,
                ])
            );
            return $response;
        }

        // Map number is available!
        $response->getBody()->write(
            \json_encode([
                'success'    => true,
                'map_number' => $map_number,
                'available'  => true,
            ])
        );
        return $response;
    }
}
