<?php

namespace App;

use App\Enum\ReleaseType;
use Xenokore\Utility\Helper\DirectoryHelper;
use App\Config\Config;

class GameFileHandler
{

    public static function generateIndexFromPath(string $path): array|false
    {
        if(DirectoryHelper::isAccessible($path) === false){
            return false;
        }

        $index = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative_path = \DIRECTORY_SEPARATOR . \ltrim(\substr($file->getPathname(), \strlen($path)), \DIRECTORY_SEPARATOR);
                $index[$relative_path] = \hash_file('crc32b', $file->getPathname());
            }
        }

        return $index;
    }

    public static function generateCacheKey(ReleaseType $type, string $version){
        return \sprintf(
            "game-file-index-%s-%s",
            $type->value,
            $version
        );
    }

    public static function storeVersionFromPath(ReleaseType $release_type, string $version, string $source_path): false|int
    {
        // Make sure source directory is accessible
        if(DirectoryHelper::isAccessible($source_path) === false){
            return false;
        }

        // Get storage path
        $dest_path = Config::get('storage.path.game-files') . '/' . $release_type->value . '/' . $version;

        // Remove dir if it already exists
        if(DirectoryHelper::isAccessible($dest_path)){
            DirectoryHelper::delete($dest_path);
        }

        // Create the dir
        DirectoryHelper::create($dest_path);

        // Move files
        $file_count = 0;
        $dir_iterator = new \RecursiveDirectoryIterator($source_path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator     = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $item_dir_path = $dest_path . \DIRECTORY_SEPARATOR . \ltrim(\substr($item->getPathname(), \strlen($source_path)), \DIRECTORY_SEPARATOR);
                if(!\file_exists($item_dir_path) && !\is_dir($item_dir_path)){
                    \mkdir($item_dir_path);
                }
            } else {
                $item_filepath = $dest_path . \DIRECTORY_SEPARATOR . \ltrim(\substr($item->getPathname(), \strlen($source_path)), \DIRECTORY_SEPARATOR);
                if(\copy($item, $item_filepath) === true){
                    $file_count++;
                } else {
                    throw new \Exception("failed to copy file");
                }
            }
        }

        return $file_count;
    }

    public static function removeAllExcept(ReleaseType $release_type, array $versions_to_keep): bool
    {
        // Get main dir
        $dir = Config::get('storage.path.game-files') . '/' . $release_type->value;
        if (!DirectoryHelper::isAccessible($dir)) {
            throw new \RuntimeException("Directory is not accessible: $dir");
        }

        // Loop trough main dir
        foreach (new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS) as $item) {

            // Check if current item is a dir
            if ($item->isDir()) {

                // Check if we can remove this version dir
                if(!\in_array($item->getFilename(), $versions_to_keep, true)) {

                    // Remove it
                    $result = DirectoryHelper::delete($item->getPathname());
                    if(!$result){
                        throw new \Exception("Failed to remove dir: {$item->getPathname()}");
                    }
                }
            }
        }

        return true;
    }
}
