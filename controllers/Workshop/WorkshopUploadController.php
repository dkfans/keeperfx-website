<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;
use App\Entity\WorkshopImage;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopDifficultyRating;

use URLify;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\UploadSizeHelper;

use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

use Slim\Psr7\UploadedFile;
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

        // Get image data
        $image_post_data = $post['image-widget'] ?? '{}';
        $image_data = @\json_decode($image_post_data);
        if(\is_null($image_data)){
            $flash->warning('Invalid image data');
            $success = false;
        }

        // Handle map number
        $map_number = null;
        if($category === WorkshopCategory::Map){

            $check_map_number = (int) ($post['map_number'] ?? 0);

            // Check valid map number
            if($check_map_number < 202 || $check_map_number > 32767){
                $flash->warning('Invalid map number');
                $success = false;
            } else {

                // Check if map with this map number already exists
                $map_number_existing_item = $em->getRepository(WorkshopItem::class)->findOneBy([
                    'category'   => WorkshopCategory::Map,
                    'map_number' => $check_map_number
                ]);
                if($map_number_existing_item !== null){
                    $flash->warning('Map number already in use');
                    $success = false;
                } else {
                    $map_number = $check_map_number;
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
        $workshop_item->setMapNumber($map_number);
        $workshop_item->setDifficultyRatingEnabled(\array_key_exists('enable_difficulty_rating', $post));

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
            $min_build = (int) $post['min_game_build'];
            if($min_build === -1){
                // Latest alpha patch
                $workshop_item->setMinGameBuild(-1);
            } elseif ($min_build > 0) {
                // Stable build
                $min_game_build = $em->getRepository(GithubRelease::class)->find($min_build);
                if($min_game_build){
                    $workshop_item->setMinGameBuild($min_build);
                }
            }
        }

        // Automatically publish item for accounts with a role of 'Moderator' or higher
        if($account->getUser()->getRole()->value >= UserRole::Moderator->value){
            $workshop_item->setIsPublished(true);
        }

        $em->persist($workshop_item);
        $em->flush(); // flush because we need ID for creating storage directory


        // Define directories for files
        $workshop_item_dir        = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_files_dir  = $workshop_item_dir . '/files';
        $workshop_item_images_dir = $workshop_item_dir . '/images';

        // Create directories for files
        if(!DirectoryHelper::create($workshop_item_dir)){
            throw new \Exception('Failed to create workshop item storage dir');
        }
        if(!DirectoryHelper::create($workshop_item_files_dir)){
            throw new \Exception('Failed to create workshop item files dir'); // TODO: move during migration
        }
        if(!DirectoryHelper::create($workshop_item_images_dir)){
            throw new \Exception('Failed to create workshop item images dir');
        }

        // Get file and filename
        $file     = $uploaded_files['file'];
        $filename = $file->getClientFilename();

        // Get and set some variables for the new file
        $file_ext              = \pathinfo($filename, PATHINFO_EXTENSION);
        $file_storage_filename = \sha1($filename . time()) . '__' . $file_ext;
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
        $workshop_file->setWeight(0);
        $em->persist($workshop_file);
        $em->flush();

        // Store any uploaded images
        foreach($image_data as $weight => $image_obj){

            // TODO: check image upload filesizes (DO NOT USE size property on object)

            // Check if object is legit
            if(
                !property_exists($image_obj, 'id') || !is_null($image_obj->id) // id will be NULL during upload
                || !property_exists($image_obj, 'name') || !is_string($image_obj->name)
                // || !property_exists($image_obj, 'size') || !is_int($image_obj->size)
                || !property_exists($image_obj, 'src') || !is_null($image_obj->src) // src will be NULL during upload
                || !property_exists($image_obj, 'data') || !is_string($image_obj->data) // data will be a base64 string during upload
            ) {
                continue;
            }

            // Get and check extension
            $ext = \strtolower(\pathinfo($image_obj->name, \PATHINFO_EXTENSION));
            if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                continue;
            }

            // Generate image output path
            $str            = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
            $image_filename = $str . '.' . $ext;
            $path           = $workshop_item_images_dir . '/' . $image_filename;

            // Get image blob
            $base64 = explode(',', $image_obj->data)[1];
            $blob = \base64_decode($base64);

            // Create image
            if(\file_put_contents($path, $blob) === false){
                continue;
            }

            // Get image width and height
            $width  = null;
            $height = null;
            $size   = @\getimagesize($path);
            if($size && \is_array($size)){
                $width  = $size[0];
                $height = $size[1];
            }

            // Create image entity
            $image_entity = new WorkshopImage();
            $image_entity->setItem($workshop_item);
            $image_entity->setFilename($image_filename);
            $image_entity->setWeight($weight);
            $image_entity->setWidth($width);
            $image_entity->setHeight($height);
            $em->persist($image_entity);

        }

        // Flush again so filenames are added to DB entity
        $em->flush();

        // Show upload success message and redirect to workshop item page
        $flash->success('Your workshop item has been uploaded!');
        $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId() . '/' . URLify::slug($workshop_item->getName()))->withStatus(302);

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
