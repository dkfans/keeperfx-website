<?php

namespace App\Helper;

use Gumlet\ImageResize;

class ThumbnailHelper {

    public static function createThumbnail(string $image_filepath, int $height, int $width, int $position = ImageResize::CROPCENTER): false|string
    {
        // Get variables
        $image_filename              = \basename($image_filepath);
        $image_dir                   = \dirname($image_filepath);
        $image_extension             = \strtolower(\pathinfo($image_filename, \PATHINFO_EXTENSION));
        $image_filename_no_extension = \pathinfo($image_filename, \PATHINFO_FILENAME);

        // Make sure image exists
        if(!\file_exists($image_filepath)){
            throw new \Exception("file not found: {$image_filepath}");
        }

        // Get thumbnail filename variables
        $thumbnail_filename = $image_filename_no_extension . '-thumbnail.png';
        $thumbnail_filepath = $image_dir . '/' . $thumbnail_filename;

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
            @\unlink($thumbnail_filepath);
            return false;
        }

        return $thumbnail_filename;
    }

}
