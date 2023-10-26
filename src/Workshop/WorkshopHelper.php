<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;

use Gumlet\ImageResize;
use Doctrine\ORM\EntityManager;

use App\Helper\SystemHelper;
use Psr\SimpleCache\CacheInterface;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

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
        $image_filename              = $images[0]->getFilename();
        $image_filepath              = $item_images_dir . '/' . $image_filename;
        $image_extension             = \strtolower(\pathinfo($image_filename, \PATHINFO_EXTENSION));
        $image_filename_no_extension = \pathinfo($image_filename, \PATHINFO_FILENAME);

        // Make sure image exists
        if(!\file_exists($image_filepath)){
            return false;
        }

        // Get thumbnail filename variables
        $thumbnail_filename = 'thumbnail-' . $image_filename_no_extension . '.png';
        $thumbnail_filepath = $item_images_dir . '/' . $thumbnail_filename;

        // Make sure thumbnail does not exist yet
        if(\file_exists($thumbnail_filepath)){
            @\unlink($thumbnail_filepath);
        }

        // Generate thumbnail
        try {

            // Fix a possible libpng error that might arise
            if(\is_callable('shell_exec') && SystemHelper::verifyShellCommand('mogrify')){
                @\shell_exec("mogrify -interlace none {$image_filepath}");
            }

            $thumbnail = new ImageResize($image_filepath);
            $thumbnail->interlace = 0;
            $thumbnail->crop(256, 256, false, ImageResize::CROPCENTER);
            $thumbnail->save($thumbnail_filepath);
        } catch (\Exception $ex) {
            return false;
        }

        // Check if thumbnail exists now
        if(!\file_exists($thumbnail_filepath)){
            return false;
        }

        // Make sure thumbnail is actually smaller in filesize if the original image was not a gif
        if($image_extension !== 'gif' && \filesize($thumbnail_filepath) >= filesize($image_filepath)){
            return false;
        }

        // Set thumbnail filename for workshop item
        $item->setThumbnail($thumbnail_filename);
        $em->flush();

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

    public static function getCachedBrowsePageData(CacheInterface $cache, array $query_params)
    {
        $cache_key = 'workshop_browse:' . \md5(\serialize((array)$query_params));
        return $cache->get($cache_key);
    }

    public static function setCachedBrowsePageData(CacheInterface $cache, array $query_params, array $data)
    {
        $cache_list_key = 'workshop_browse:all_keys';
        $cache_key      = 'workshop_browse:' . \md5(\serialize((array)$query_params));

        $cache->set($cache_key, $data);

        $browse_pages = $cache->get($cache_list_key);
        if($browse_pages !== null && \is_array($browse_pages)){
            $cache->set($cache_list_key, $browse_pages + [$cache_key]);
        } else {
            $cache->set($cache_list_key, [$cache_key]);
        }
    }

    public static function clearAllCachedBrowsePageData(CacheInterface $cache)
    {
        $cache_list_key = 'workshop_browse:all_keys';

        $browse_pages = $cache->get($cache_list_key);
        if($browse_pages !== null){
            $cache->deleteMultiple($browse_pages);
        }

        $cache->delete($cache_list_key);
    }

}
