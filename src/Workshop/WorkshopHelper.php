<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;

use App\Config\Config;
use Doctrine\ORM\EntityManager;

use App\Helper\ThumbnailHelper;

class WorkshopHelper {

    public const int RATING_QUALITY = 1;
    public const int RATING_DIFFICULTY = 2;

    public static function generateThumbnail(EntityManager $em, WorkshopItem $item): bool
    {
        // TODO: add env var check if we need to generate thumbnails
        // TODO: add env var for x/y size generation of thumbnail
        // TODO: add env var for minimum filesize to start generating thumbnails (250kb?)

        // Get workshop item image dir
        $item_images_dir = Config::get('storage.path.workshop') . '/' . $item->getId() . '/images';

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
        $thumbnail_filename = ThumbnailHelper::createThumbnail($image_filepath, 256, 256);
        if($thumbnail_filename){
            $item->setThumbnail($thumbnail_filename);
            $em->flush();
        }

        return true;
    }

    public static function removeThumbnail(EntityManager $em, WorkshopItem $item)
    {
        // Get workshop item image dir
        $item_images_dir = Config::get('storage.path.workshop') . '/' . $item->getId() . '/images';

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

    /**
     * Calculate the rating score of a workshop item.
     *
     * Returns an array with the 'score' and 'count' keys.
     * 'score' can be null
     *
     * @param WorkshopItem $workshop_item
     * @param int $type
     * @return array
     */
    public static function calculateRatingScore(WorkshopItem $workshop_item, int $type = self::RATING_QUALITY): array
    {
        $rating_score = null;

        // Check what kind of rating we are handling
        if($type === self::RATING_QUALITY){
            $ratings = $workshop_item->getRatings();
        } else if ($type === self::RATING_DIFFICULTY){
            $ratings = $workshop_item->getDifficultyRatings();
        } else {
            throw new \InvalidArgumentException("'type' parameter should be either 'quality' or 'difficulty'");
        }

        // Handle item ratings
        if($ratings && \count($ratings) > 0){

            // Get all scores
            $rating_scores = [];
            foreach($ratings as $rating){
                $rating_scores[] = $rating->getScore();
            }

            // Calculate the average
            $rating_average =  \array_sum($rating_scores) / \count($rating_scores);

            // Round the average
            $rating_score  = \round($rating_average, 2);
        }

        return [
            'score' => $rating_score,
            'count' => \count($ratings),
        ];
    }
}
