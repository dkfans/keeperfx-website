<?php

namespace App;

class UploadSizeHelper
{
    private int $php_max_upload;
    private int $php_max_post;
    private int $php_memory_limit;

    private int $max_calculated_file_upload;
    private int $max_calculated_total_upload;

    private int $news_image_max_upload_size;
    private int $avatar_max_upload_size;
    private int $workshop_item_max_upload_size;
    private int $workshop_image_max_upload_size;

    public function __construct()
    {
        // Get PHP upload limits
        $this->php_max_upload        = (int)(\ini_get('upload_max_filesize')) * 1024 * 1024;
        $this->php_max_post          = (int)(\ini_get('post_max_size')) * 1024 * 1024;
        $this->php_memory_limit      = (int)(\ini_get('memory_limit')) * 1024 * 1024;

        // Determine max PHP upload size per file
        $this->max_calculated_file_upload = \min(
            $this->php_max_upload,
            $this->php_max_post,
            $this->php_memory_limit
        );

        // Determine max PHP total upload size
        $this->max_calculated_total_upload = \min(
            $this->php_max_post,
            $this->php_memory_limit
        );

        // Determine max news image upload size
        $val = $_ENV['APP_NEWS_IMAGE_MAX_UPLOAD_SIZE'] ?? null;
        if ($val === null || \filter_var($val, \FILTER_VALIDATE_INT) === false) {
            $this->news_image_max_upload_size = $this->max_calculated_file_upload;
        } else {
            $this->news_image_max_upload_size = \min($val, $this->max_calculated_file_upload);;
        }

        // Determine max Avatar upload size
        $val = $_ENV['APP_AVATAR_MAX_UPLOAD_SIZE'] ?? null;
        if ($val === null || \filter_var($val, \FILTER_VALIDATE_INT) === false) {
            $this->avatar_max_upload_size = $this->max_calculated_file_upload;
        } else {
            $this->avatar_max_upload_size = \min($val, $this->max_calculated_file_upload);;
        }

        // Determine max Workshop upload size
        $val = $_ENV['APP_WORKSHOP_ITEM_MAX_UPLOAD_SIZE'] ?? null;
        if ($val === null || \filter_var($val, \FILTER_VALIDATE_INT) === false) {
            $this->workshop_item_max_upload_size = $this->max_calculated_file_upload;
        } else {
            $this->workshop_item_max_upload_size = \min($val, $this->max_calculated_file_upload);;
        }

        // Determine max image (thumbnail & screenshot) size
        $val = $_ENV['APP_WORKSHOP_IMAGE_MAX_UPLOAD_SIZE'] ?? null;
        if ($val === null || \filter_var($val, \FILTER_VALIDATE_INT) === false) {
            $this->workshop_image_max_upload_size = $this->max_calculated_file_upload;
        } else {
            $this->workshop_image_max_upload_size = \min($val, $this->max_calculated_file_upload);;
        }
    }

    /**
     * Get final news image max upload filesize in bytes
     *
     * @return integer
     */
    public function getFinalNewsImageUploadSize(): int
    {
        return $this->news_image_max_upload_size;
    }

    /**
     * Get final avatar max upload filesize in bytes
     *
     * @return integer
     */
    public function getFinalAvatarUploadSize(): int
    {
        return $this->avatar_max_upload_size;
    }

    /**
     * Get final workshop file max upload filesize in bytes
     *
     * @return integer
     */
    public function getFinalWorkshopItemUploadSize(): int
    {
        return $this->workshop_item_max_upload_size;
    }

    /**
     * Get final workshop image max upload filesize in bytes
     *
     * @return integer
     */
    public function getFinalWorkshopImageUploadSize(): int
    {
        return $this->workshop_image_max_upload_size;
    }

    /**
     * Get the value of php_max_upload
     */
    public function getPhpMaxUpload(): int
    {
        return $this->php_max_upload;
    }

    /**
     * Get the value of php_max_post
     */
    public function getPhpMaxPost(): int
    {
        return $this->php_max_post;
    }

    /**
     * Get the value of php_memory_limit
     */
    public function getPhpMemoryLimit(): int
    {
        return $this->php_memory_limit;
    }

    /**
     * Get the value of max_calculated_upload
     */
    public function getMaxCalculatedFileUpload(): int
    {
        return $this->max_calculated_file_upload;
    }

    /**
     * Get the value of max_calculated_upload
     */
    public function getMaxCalculatedTotalUpload(): int
    {
        return $this->max_calculated_total_upload;
    }
}
