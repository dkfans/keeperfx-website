<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;

use Gumlet\ImageResize;
use Doctrine\ORM\EntityManager;

class WorkshopHelper {

    public static function generateThumbnailIfNotExists(EntityManager $em, WorkshopItem $item): bool
    {
        // TODO: add env var check if we need to generate thumbnails

        // TODO: add env var for x/y size generation of thumbnail

        // TODO: add env var for minimum filesize to start generating thumbnails (250kb?)

        // Get workshop item image dir
        $item_images_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $item->getId() . '/images';
        if(!\file_exists($item_images_dir)){
            echo('azeaze');
            return false;
        }

        // Make sure this workshop item has images
        $images = $item->getImages();
        if(!$images){
            echo('zeaeazzea');
            return false;
        }

        // Get image filename variables
        $image_filename = $images[0]->getFilename();
        $image_filepath = $item_images_dir . '/' . $image_filename;

        // Make sure image exists
        if(!\file_exists($image_filepath)){
            echo('rezrzer');
            return false;
        }

        // Get thumbnail filename variables
        $thumbnail_filename = 'thumbnail-' . $images[0]->getFilename();
        $thumbnail_filepath = $item_images_dir . '/' . $thumbnail_filename;

        // Make sure thumbnail does not exist yet
        if(\file_exists($thumbnail_filepath)){
            echo('bgfdg');
            return false;
        }

        // Generate thumbnail
        $thumbnail = new ImageResize($image_filepath);
        $thumbnail->crop(256, 256, true, ImageResize::CROPCENTER);
        $thumbnail->save($thumbnail_filepath);

        // Check if thumbnail exists now
        if(!\file_exists($thumbnail_filepath)){
            echo('hrtj');
            return false;
        }

        // Make sure thumbnail is actually smaller in filesize
        if(\filesize($thumbnail_filepath) >= filesize($image_filepath)){
            return false;
        }

        // Set thumbnail filename for workshop item
        $item->setThumbnail($thumbnail_filename);
        $em->flush();

        return true;
    }

}
