<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;

use Doctrine\ORM\EntityManager;

use Psr\SimpleCache\CacheInterface;

use App\Helper\SystemHelper;
use App\Helper\ThumbnailHelper;

class WorkshopHelper {

    public static function generateThumbnail(EntityManager $em, WorkshopItem $item): bool
    {
        // TODO: add env var check if we need to generate thumbnails
        // TODO: add env var for x/y size generation of thumbnail
        // TODO: add env var for minimum filesize to start generating thumbnails (250kb?)

        // Get workshop item image dir
        if(\php_sapi_name() === 'cli' || \defined('STDIN')){
            $item_images_dir = $_ENV['APP_WORKSHOP_STORAGE_CLI_PATH'] . '/' . $item->getId() . '/images';
        } else {
            $item_images_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $item->getId() . '/images';
        }

        // Make sure image dir exists
        if(!\file_exists($item_images_dir)){
            return false;
        }

        // Make sure this workshop item has images
        $images = $item->getImages();
        if(!$images || !isset($images[0])){
            return false;
        }

        // Get image filename variables
        $image_filename = $images[0]->getFilename();
        $image_filepath = $item_images_dir . '/' . $image_filename;

        // Create a thumbnail
        $thumbnail_filename = ThumbnailHelper::createThumbnail($image_filepath, 512, 512);
        if($thumbnail_filename){
            $item->setThumbnail($thumbnail_filename);
            $em->flush();
        }

        return true;
    }

    public static function removeThumbnail(EntityManager $em, WorkshopItem $item)
    {
        // Get workshop item image dir
        if(\php_sapi_name() === 'cli' || \defined('STDIN')){
            $item_images_dir = $_ENV['APP_WORKSHOP_STORAGE_CLI_PATH'] . '/' . $item->getId() . '/images';
        } else {
            $item_images_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $item->getId() . '/images';
        }

        // Make sure image dir exists
        if(!\file_exists($item_images_dir)){
            return false;
        }

        // Get thumbnail filename
        $thumbnail_filename = $item->getThumbnail();
        if(!$thumbnail_filename){
            return false;
        }

        // Set workshop item thumbnail to null
        $item->setThumbnail(null);
        $em->flush();

        // Get thumbnail filepath
        $thumbnail_filepath = $item_images_dir . '/' . $thumbnail_filename;
        if(file_exists($thumbnail_filepath)){
            @\unlink($thumbnail_filepath);
        }

        return true;
    }
}
