<?php

namespace App\Controller\Workshop;

use App\Entity\GithubRelease;
use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;

use App\Enum\WorkshopCategory;

use App\Account;
use App\Entity\WorkshopImage;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopEditController {

    public function editIndex(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Get image widget data
        $image_widget_data = [];
        foreach($workshop_item->getImages() as $image)
        {
            $image_widget_data[$image->getWeight()] = [
                'id'   => $image->getId(),
                'name' => $image->getFilename(),
                'src'  => '/workshop/image/' . $workshop_item->getId() . '/' . $image->getFilename(),
                'data' => null,
            ];
        }

        // Show edit page
        $response->getBody()->write(
            $twig->render('workshop/edit.workshop.html.twig', [
                'workshop_item'     => $workshop_item,
                'image_widget_data' => $image_widget_data,
            ])
        );
        return $response;
    }

    public function edit(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        UploadSizeHelper $upload_size_helper,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        $uploaded_files = $request->getUploadedFiles();
        $post           = $request->getParsedBody();

        $name                  = \trim((string) ($post['name'] ?? null));
        $description           = \trim((string) ($post['description'] ?? null));
        $install_instructions  = \trim((string) ($post['install_instructions'] ?? null));

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        // Get and validate image data
        $image_post_data = $post['image-widget'] ?? '{}';
        $image_data = @\json_decode($image_post_data, true);
        if(!\is_array($image_data)){
            $flash->error('Invalid image data');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        $workshop_item->setName($name);
        $workshop_item->setDescription($description);
        $workshop_item->setInstallInstructions($install_instructions);

        // Set workshop item category
        $category = WorkshopCategory::tryFrom((int) ($post['category'] ?? null));
        $workshop_item->setCategory($category);

        // Set minimum game build
        $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
        $workshop_item->setMinGameBuild($min_game_build ?? null);

        // Set directories for files
        $workshop_item_dir        = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_images_dir = $workshop_item_dir . '/images';

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

        // TODO: delete existing images

        // Get currently existing images
        // $current_images = $em->getRepository(WorkshopImage::class)->findAll(['item' => $workshop_item], ['weight' => 'ASC']);

        // Image variables
        $images_to_keep = [];
        $current_weight = -1;

        // Handle images
        foreach($image_data as $weight => $image_obj){

            $current_weight++;

            // Check if object is legit
            if(
                !\array_key_exists('id', $image_obj) || (!is_null($image_obj['id']) && !is_int($image_obj['id'])) // id will be set or NULL
                || !\array_key_exists('name', $image_obj) || !is_string($image_obj['name'])
                // || !property_exists($image_obj, 'size') || !is_int($image_obj->size)
                || !\array_key_exists('src', $image_obj) || (!is_null($image_obj['src']) && !is_string($image_obj['src'])) // src will be set or NULL
                || !\array_key_exists('data', $image_obj) || (!is_null($image_obj['data']) && !is_string($image_obj['data'])) // data will be set or NULL
            ) {
                continue;
            }

            // Add image
            if(\is_null($image_obj['id'])){

                // TODO: Check $image_obj['data']

                // Get and check extension
                $ext = \strtolower(\pathinfo($image_obj['name'], \PATHINFO_EXTENSION));
                if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])){
                    continue;
                }

                // Generate image output path
                $str            = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
                $image_filename = $str . '.' . $ext;
                $path           = $workshop_item_images_dir . '/' . $image_filename;

                // Get image blob
                $base64 = explode(',', $image_obj['data'])[1];
                $blob   = \base64_decode($base64);

                // Filesize check
                if(\strlen($blob) > $upload_size_helper->getFinalWorkshopImageUploadSize()){
                    continue;
                }

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

                continue;
            }

            // Get image and check if it exists
            $image = $em->getRepository(WorkshopImage::class)->find($image_obj['id']);
            if(!$image){
                continue;
            }

            // Delete image
            if(\is_null($image_obj['src']) && \is_null($image_obj['data'])){

                // Remove file
                $path = $workshop_item_images_dir . '/' . $image->getFilename();
                if(\file_exists($path)){
                    @\unlink($path);
                }

                // Remove in DB
                $em->remove($image);

                // Set the weight one back as this one is gone now
                $current_weight--;

                continue;
            }

            // Update image
            // Only the weight of the image will be updated as the position might have changed
            if(!\is_null($image_obj['src'])){
                $image->setWeight($current_weight);
            }

        }


        // // Store any uploaded screenshots
        // foreach($uploaded_files['screenshots'] as $screenshot_file){
        //     // NO screenshots were added
        //     if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
        //         continue;
        //     }

        //     // Generate screenshot output path
        //     $ext = \strtolower(\pathinfo($screenshot_file->getClientFilename(), \PATHINFO_EXTENSION));
        //     $str = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
        //     $screenshot_filename = $str . '.' . $ext;
        //     $path = $workshop_item_screenshots_dir . '/' . $screenshot_filename;

        //     // Move screenshot
        //     $screenshot_file->moveTo($path);
        //     if(!\file_exists($path)){
        //         throw new \Exception('Failed to move workshop item screenshot');
        //     }
        // }

        // // Update or set thumbnail
        // if(!empty($uploaded_files['thumbnail']) && $uploaded_files['thumbnail']->getError() !== UPLOAD_ERR_NO_FILE){

        //     $thumbnail_file     = $uploaded_files['thumbnail'];
        //     $thumbnail_filename = $thumbnail_file->getClientFilename();
        //     $thumbnail_path     = $workshop_item_dir . '/' . $thumbnail_filename;

        //     // Remove already existing thumbnail
        //     if($workshop_item->getThumbnail() !== null){
        //         $current_thumbnail_path = $workshop_item_dir . '/' . $workshop_item->getThumbnail();
        //         if(\file_exists($current_thumbnail_path)){
        //             \unlink($current_thumbnail_path);
        //         }
        //     }

        //     $thumbnail_file->moveTo($thumbnail_path);

        //     if(\file_exists($thumbnail_path)){
        //         $workshop_item->setThumbnail($thumbnail_filename);
        //     }
        // }

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

        // Force the workshop item to be accepted again
        // $workshop_item->setIsPublished(false);

        // Write changes to DB
        $em->flush();

        $flash->success(
            'Your workshop item has been updated and has been temporary removed from the workshop. ' .
            'After it has been reviewed by the KeeperFX team it will be available in the workshop again.'
        );

        $response->getBody()->write(
            $twig->render('workshop/alert.workshop.html.twig')
        );

        return $response;
    }
}
