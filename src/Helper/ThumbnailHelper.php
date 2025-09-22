<?php

namespace App\Helper;

use Gumlet\ImageResize;

class ThumbnailHelper
{

    /**
     * Create a thumbnail for the given image.
     *
     * The thumbnail will be created in the same directory.
     *
     * @param string $image_filepath        Path to the original image
     * @param integer $height               Height of the created thumbnail
     * @param integer $width                Width of the created thumbnail
     * @param int $crop_position            ImageResize crop position (check the code)
     * @return string|false                 The filename of the thumbnail on success. False on failure
     */
    public static function createThumbnail(string $image_filepath, int $height, int $width, int $crop_position = ImageResize::CROPCENTER): false|string
    {
        // Get variables
        $image_filename              = \basename($image_filepath);
        $image_dir                   = \dirname($image_filepath);
        $image_extension             = \strtolower(\pathinfo($image_filename, \PATHINFO_EXTENSION));
        $image_filename_no_extension = \pathinfo($image_filename, \PATHINFO_FILENAME);

        // Make sure image exists
        if (!\file_exists($image_filepath)) {
            throw new \Exception("file not found: {$image_filepath}");
        }

        // Get thumbnail filename variables
        $thumbnail_filename = $image_filename_no_extension . '-thumbnail.png';
        $thumbnail_filepath = $image_dir . '/' . $thumbnail_filename;

        // Make sure thumbnail does not exist yet
        if (\file_exists($thumbnail_filepath)) {
            @\unlink($thumbnail_filepath);
        }

        // Fix a possible libpng error that might arise
        try {
            if (\is_callable('shell_exec') && SystemHelper::verifyShellCommand('mogrify')) {
                @\shell_exec("mogrify -interlace none {$image_filepath}");
            }
        } catch (\Exception $ex) {
        }

        // Generate thumbnail
        try {
            $thumbnail = new ImageResize($image_filepath);
            $thumbnail->interlace = 0;
            $thumbnail->crop($height, $width, false, $crop_position);
            $thumbnail->save($thumbnail_filepath);
        } catch (\Exception $ex) {
            return false;
        }

        // Check if thumbnail exists now
        if (!\file_exists($thumbnail_filepath)) {
            return false;
        }

        // Make sure thumbnail is actually smaller in filesize if the original image was not a gif
        if ($image_extension !== 'gif' && \filesize($thumbnail_filepath) >= filesize($image_filepath)) {
            @\unlink($thumbnail_filepath);
            return false;
        }

        return $thumbnail_filename;
    }
}
