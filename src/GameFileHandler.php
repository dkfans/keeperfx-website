<?php

namespace App;

use App\Enum\ReleaseType;

use App\Entity\GithubRelease;
use App\Entity\GameFileIndex;
use App\Entity\LauncherRelease;
use App\Entity\GithubAlphaBuild;

use App\Config\Config;
use Doctrine\ORM\EntityManager;

use Xenokore\Utility\Helper\DirectoryHelper;

class GameFileHandler
{
    public function __construct(
        private EntityManager $em
    ) {}

    public static function generateIndexFromPath(string $path): array|false
    {
        if (DirectoryHelper::isAccessible($path) === false) {
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

    public function getIndex(ReleaseType $release_type, string $version): array|false
    {
        // Get release
        $index = $this->em->getRepository(GameFileIndex::class)->findOneBy(['release_type' => $release_type, 'version' => $version]);
        if (!$index) {
            return false;
        }

        return $index->getData();
    }

    public function storeVersionFromPath(ReleaseType $release_type, string $version, string $source_path): false|int
    {
        // Make sure source directory is accessible
        if (DirectoryHelper::isAccessible($source_path) === false) {
            return false;
        }

        // Generate a file index from the source dir
        // In most cases this should be faster than using the storage dir
        // Best example would be when the storage dir is on a separate file storage server
        $file_index = self::generateIndexFromPath($source_path);

        // Make sure an index is created
        // We need an index to serve these files so it's required
        if ($file_index === false || \count($file_index) === 0) {
            return false;
        }

        // Get storage path
        $dest_path = Config::get('storage.path.game-files') . '/' . $release_type->value . '/' . $version;

        // Remove dir if it already exists
        if (DirectoryHelper::isAccessible($dest_path)) {
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
                if (!\file_exists($item_dir_path) && !\is_dir($item_dir_path)) {
                    \mkdir($item_dir_path);
                }
            } else {
                $item_filepath = $dest_path . \DIRECTORY_SEPARATOR . \ltrim(\substr($item->getPathname(), \strlen($source_path)), \DIRECTORY_SEPARATOR);
                if (\copy($item, $item_filepath) === true) {
                    $file_count++;
                } else {
                    throw new \Exception("failed to copy file");
                }
            }
        }

        // Add latest launcher
        $latest_launcher = $this->em->getRepository(LauncherRelease::class)->findOneBy(['is_available' => true], ['timestamp' => 'DESC']);
        if ($latest_launcher) {
            $launcher_files_dir = Config::get('storage.path.launcher') . '/' . $latest_launcher->getName() . '/files';
            if (DirectoryHelper::isAccessible($launcher_files_dir)) {
                // Add launcher files
                foreach (scandir($launcher_files_dir) as $file) {
                    if ($file != '.' && $file != '..') {
                        $source_file = $launcher_files_dir . '/' . $file;

                        // Copy the file
                        if (\copy($source_file, $dest_path . '/' . $file)) {

                            // Add launcher file to file map
                            $relative_path = \DIRECTORY_SEPARATOR . $file;
                            $file_index[$relative_path] = \hash_file('crc32b', $source_file);
                        }
                    }
                }
            }
        }

        // Add bundled files to alphas
        $bundle_with_releases = $_ENV['APP_GAME_FILE_BUNDLE_WITH_RELEASE'] ?? null;
        if ($bundle_with_releases !== null && ($bundle_with_releases == 'all' || $release_type == ReleaseType::tryFrom($bundle_with_releases))) {
            $bundle_path = Config::get('storage.path.game-files-file-bundle');
            if ($bundle_path !== null && \is_dir($bundle_path)) {
                $dir_iterator = new \RecursiveDirectoryIterator($bundle_path, \RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator     = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        $item_dir_path = $dest_path . \DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                        if (!\file_exists($item_dir_path) && !\is_dir($item_dir_path)) {
                            \mkdir($item_dir_path);
                        }
                    } else {
                        $item_filepath = $dest_path . \DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                        if (\copy($item, $item_filepath) === false) {
                            throw new \Exception("failed to copy bundled file");
                        }

                        // Add copied file to filemap
                        $relative_path = \DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                        $file_index[$relative_path] = \hash_file('crc32b', $item_filepath);
                    }
                }
            }
        }

        // Store game file index in DB
        $index_entity = new GameFileIndex();
        $index_entity->setReleaseType($release_type);
        $index_entity->setVersion($version);
        $index_entity->setData($file_index);
        $this->em->persist($index_entity);
        $this->em->flush();

        return $file_count;
    }

    public function removeAllExcept(ReleaseType $release_type, array $versions_to_keep): array
    {
        // Remember what versions we have removed
        $versions_removed = [];

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
                $version = $item->getFilename();
                if (!\in_array($version, $versions_to_keep, true)) {

                    // Remove it
                    $result = DirectoryHelper::delete($item->getPathname());
                    if (!$result) {
                        throw new \Exception("Failed to remove dir: {$item->getPathname()}");
                    } else {

                        // Remember this version
                        $versions_removed[] = $version;
                    }
                }
            }
        }

        // Get all release entities
        $releases = null;
        if ($release_type == ReleaseType::STABLE) {
            $releases = $this->em->getRepository(GithubRelease::class)->findAll();
        }
        if ($release_type == ReleaseType::STABLE) {
            $releases = $this->em->getRepository(GithubAlphaBuild::class)->findAll();
        }

        // If there are entities found
        if ($releases !== null && \count($releases) === 0) {

            $entities_removed = false;

            // Loop trough the entities and remove versions we do not keep
            /** @var GithubRelease|GithubAlphaBuild $release */
            foreach ($releases as $release) {
                if (\in_array($release->getVersion(), $versions_to_keep) === false) {
                    $this->em->remove($release);
                    $entities_removed = true;
                }
            }

            // Flush database if entities are removed
            if ($entities_removed) {
                $this->em->flush();
            }
        }

        return $versions_removed;
    }
}
