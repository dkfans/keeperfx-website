<?php

namespace App\Workshop;

use App\Workshop\Exception\WorkshopException;

class Workshop {

    private static $storage_dir;

    public static function getStorageDir(): string
    {
        if(self::$storage_dir !== null){
            return self::$storage_dir;
        }

        $workshop_storage_path = \trim($_ENV['APP_WORKSHOP_STORAGE'] ?? '', '\t\0\\/');

        if(!\is_dir($workshop_storage_path)){
            if(!@mkdir($workshop_storage_path)){
                throw new WorkshopException('Invalid APP_WORKSHOP_STORAGE. Is not a directory, and could not be made.');
            }
        }

        self::$storage_dir = $workshop_storage_path;
        return self::$storage_dir;
    }

}
