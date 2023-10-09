<?php

namespace App\Controller\ModCP\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\User;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopImage;

use App\Account;
use App\FlashMessage;
use App\UploadSizeHelper;
use Doctrine\ORM\EntityManager;
use App\Workshop\WorkshopHelper;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use App\Workshop\Exception\WorkshopException;

class ModerateWorkshopEditController {


    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $id
    ){
        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
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

        $response->getBody()->write(
            $twig->render('modcp/workshop/edit.workshop.modcp.html.twig', [
                'workshop_item'     => $workshop_item,
                'image_widget_data' => $image_widget_data,
            ])
        );

        return $response;
    }

    public function edit(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Account $account,
        UploadSizeHelper $upload_size_helper,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        $post           = $request->getParsedBody();

        $name                  = \trim((string) ($post['name'] ?? null));
        $description           = \trim((string) ($post['description'] ?? null));
        $install_instructions  = \trim((string) ($post['install_instructions'] ?? null));

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        $submitter          = null;
        $submitter_value    = $post['submitter'] ?? null;

        // Handle submitter
        if(!\in_array($submitter_value, ['current_user', 'kfx', 'username'])){
            throw new WorkshopException('invalid submitter');
            $success = false;
        } else {
            if($submitter_value === 'current_user'){
                // Current logged in user
                $submitter = $account->getUser();
            } elseif($submitter_value === 'kfx') {
                // KeeperFX Team
                $submitter = null;
            } elseif($submitter_value === 'username') {
                // Custom user
                $submitter_username = (string) ($post['submitter_username'] ?? '');

                // Check valid username for custom user
                if(empty($submitter_username)){
                    $flash->warning('No username given for custom submitter.');
                    $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId())->withStatus(302);
                    return $response;
                } else {
                    // Search user
                    $submitter_user = $em->getRepository(User::class)->findOneBy(['username' => $submitter_username]);
                    if(!$submitter_user){
                        $flash->warning("User '{$submitter_username}' not found ");
                        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId())->withStatus(302);
                        return $response;
                    } else {
                        $submitter = $submitter_user;
                    }
                }

            } else {
                // Invalid submitter value
                throw new WorkshopException('invalid submitter');
            }

        }

        // Get category
        $category = WorkshopCategory::tryFrom((int) ($post['category'] ?? null));
        if($category === null){
            throw new WorkshopException('invalid category');
        }

        // Get and validate image data
        $image_post_data = $post['image-widget'] ?? '{}';
        $image_data = @\json_decode($image_post_data, true);
        if(!\is_array($image_data)){
            throw new WorkshopException('invalid image data');
        }

        // Handle map number
        $map_number = null;
        if($category === WorkshopCategory::Map){

            $check_map_number = (int) ($post['map_number'] ?? 0);

            // Check valid map number
            if($check_map_number < 202 || $check_map_number > 32767){
                $flash->warning('Invalid map number');
                $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId())->withStatus(302);
                return $response;
            } else {

                // Check if map with this map number already exists
                $map_number_existing_item = $em->getRepository(WorkshopItem::class)->findOneBy([
                    'category'   => WorkshopCategory::Map,
                    'map_number' => $check_map_number
                ]);
                if($map_number_existing_item !== null && $workshop_item !== $map_number_existing_item){
                    $flash->warning('Map number already in use');
                    $response = $response->withHeader(
                        'Location', '/moderate/workshop/' . $workshop_item->getId()
                    )->withStatus(302);
                    return $response;
                } else {
                    $map_number = $check_map_number;
                }
            }
        }

        $workshop_item->setName($name);
        $workshop_item->setDescription($description);
        $workshop_item->setInstallInstructions($install_instructions);
        $workshop_item->setCategory($category);
        $workshop_item->setMapNumber($map_number);
        $workshop_item->setSubmitter($submitter);
        $workshop_item->setDifficultyRatingEnabled(\array_key_exists('enable_difficulty_rating', $post));
        $workshop_item->setIsBundledWithGame(\array_key_exists('is_bundled_with_game', $post));

        // Set optional minimum game build
        $workshop_item->setMinGameBuild(null);
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

        // Set directories for files
        $workshop_item_dir        = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_images_dir = $workshop_item_dir . '/images';

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
                if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
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
                $image_entity->setWeight($current_weight);
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
            } catch (\Exception $ex){
                $workshop_item->setOriginalCreationDate(null);
            }
        } else {
            $workshop_item->setOriginalCreationDate(null);
        }

        // Write changes to DB
        $em->flush();

        // Create or update thumbnail
        // TODO: improve this
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($workshop_item->getId());
        WorkshopHelper::removeThumbnail($em, $workshop_item);
        WorkshopHelper::generateThumbnail($em, $workshop_item);

        $flash->success('Workshop item updated!');
        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

    public function delete(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){
        // Check for valid CSRF check
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get workshop item dir and check if it exists
        $workshop_item_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        if(!\is_dir($workshop_item_dir)){
            throw new WorkshopException('workshop item dir does not exist');
        }

        // Clear workshop item dir
        if(!DirectoryHelper::clear($workshop_item_dir)){
            throw new WorkshopException('failed to clear and remove workshop item dir');
        }

        // Remove workshop item dir
        if(\is_dir($workshop_item_dir)){
            @\rmdir($workshop_item_dir);
        }

        // Remove from DB
        $em->remove($workshop_item);
        $em->flush();

        $flash->success('The workshop item has been removed.');
        $response = $response->withHeader('Location', '/moderate/workshop/list')->withStatus(302);
        return $response;
    }

}
