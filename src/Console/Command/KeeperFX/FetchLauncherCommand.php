<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\LauncherRelease;

use App\Config\Config;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\JsonHelper;
use Xenokore\Utility\Helper\DirectoryHelper;

use wapmorgan\UnifiedArchive\UnifiedArchive;
use wapmorgan\UnifiedArchive\Exceptions\EmptyFileListException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;

class FetchLauncherCommand extends Command
{
    public const GITHUB_RELEASE_URL = 'https://api.github.com/repos/yani/keeperfx-launcher-qt/releases';

    private array $files_archive_asset_regex = [
        '/^keeperfx\-launcher\-qt\-(.+?)\-win64\.7z$/'
    ];

    private array $installer_asset_regex = [
        '/^keeperfx\-launcher\-qt\-(.+?)\-win64\-web\-installer\.exe$/'
    ];

    public function __construct(
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-launcher")
            ->setDescription("Fetch the latest launcher release");
    }

    protected function execute(Input $input, Output $output)
    {
        // Make sure an output directory is set
        $storage_dir = Config::get('storage.path.launcher');
        if($storage_dir === null){
            $output->writeln("[-] Launcher storage directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_LAUNCHER_STORAGE_CLI_PATH' or 'APP_LAUNCHER_STORAGE'");
            return Command::FAILURE;
        }

        // Start
        $output->writeln("[>] Fetching latest launcher releases...");
        $output->writeln("[>] API Endpoint: " . self::GITHUB_RELEASE_URL);

        // Create HTTP client
        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        // Fetch releases
        $res = $client->request('GET', self::GITHUB_RELEASE_URL);
        $gh_releases = JsonHelper::decode($res->getBody());
        if(empty($gh_releases)){
            $output->writeln("[-] Failed to fetch releases");
            return Command::FAILURE;
        }

        // Tell user how many releases were found
        $release_count = \count($gh_releases);
        $output->writeln("[>] Found {$release_count} releases...");

        // Loop trough all fetched releases
        foreach($gh_releases as $gh_release){

            // Make sure github release data is valid
            if(empty($gh_release->tag_name) || empty($gh_release->assets) || empty($gh_release->assets[0]->browser_download_url)){
                $output->writeln("[-] Invalid github release data...");
                continue;
            }

            // Check if release already exists in DB
            $db_release = $this->em->getRepository(LauncherRelease::class)->findOneBy(['tag' => $gh_release->tag_name]);
            if($db_release){
                continue;
            }

            // Get assets
            $files_archive_asset = null;
            $installer_asset = null;
            foreach($gh_release->assets as $asset){

                // Files
                foreach($this->files_archive_asset_regex as $regex){
                    if(\preg_match($regex, $asset->name, $matches)){
                        $files_archive_asset = $asset;
                        continue 2;
                    }
                }

                // Installer
                foreach($this->installer_asset_regex as $regex){
                    if(\preg_match($regex, $asset->name, $matches)){
                        $installer_asset = $asset;
                        continue 2;
                    }
                }
            }

            // Make sure assets are found
            if($files_archive_asset === null || $installer_asset === null){
                $output->writeln("[-] Failed to get required assets");
                return Command::FAILURE;
            }

            // Variables
            $temp_archive_dir                = \sys_get_temp_dir() . '/' . $gh_release->name;
            $temp_archive_files_dir          = $temp_archive_dir . '/files';
            $temp_files_archive_path         = $temp_archive_dir . '/' . $files_archive_asset->name;
            $temp_installer_path             = $temp_archive_dir . '/' . $installer_asset->name;
            $launcher_storage_dir            = $storage_dir . '/' . $gh_release->name;
            $launcher_files_storage_dir      = $launcher_storage_dir . '/files';
            $launcher_installer_storage_path = $launcher_storage_dir . '/keeperfx-web-installer.exe';

            // Make sure there isn't a download/archive process already executing
            if(\file_exists($temp_archive_dir)){
                $output->writeln("[-] The temporary directory for this release already exist.");
                $output->writeln("[>] Skipping this release because the process is probably still busy...");
                continue;
            }

            // Make temporary asset directory
            if(!DirectoryHelper::createIfNotExist($temp_archive_dir)){
                $output->writeln("[-] Failed to create temporary folder: $temp_archive_dir");
                return Command::FAILURE;
            }

            // Make temporary asset directory for archive files
            if(!DirectoryHelper::createIfNotExist($temp_archive_files_dir)){
                $output->writeln("[-] Failed to create temporary folder: $temp_archive_files_dir");
                return Command::FAILURE;
            }

            // Download the assets and extract the files
            $output->writeln("[>] Downloading assets for {$gh_release->tag_name}...");
            try {

                // Download installer
                $output->writeln("[>] Downloading: {$gh_release->name} -> <info>{$temp_installer_path}</info> ({$installer_asset->size} bytes)");
                $client->request('GET', $installer_asset->browser_download_url, ['sink' => $temp_installer_path]);
                if(!\file_exists($temp_installer_path)){
                    $output->writeln("[-] Failed to download installer");
                    return Command::FAILURE;
                } else {
                    $output->writeln("[+] Installer downloaded!");
                }

                // Download files archive
                $output->writeln("[>] Downloading: {$gh_release->name} -> <info>{$temp_files_archive_path}</info> ({$files_archive_asset->size} bytes)");
                $client->request('GET', $files_archive_asset->browser_download_url, ['sink' => $temp_files_archive_path]);
                if(!\file_exists($temp_files_archive_path)){
                    $output->writeln("[-] Failed to download files archive");
                    return Command::FAILURE;
                } else {
                    $output->writeln("[+] Installer downloaded!");
                }

                // Open the files archive
                $files_archive = UnifiedArchive::open($temp_files_archive_path);
                if($files_archive === null){
                    $output->writeln("[-] Failed to open the files archive");
                    return Command::FAILURE;
                }

                // Extract the files
                $output->writeln("[>] Extracting...");
                try {
                    $files_archive->extract($temp_archive_files_dir);
                } catch (EmptyFileListException $ex){
                    $output->writeln("[-] No files in files archive");
                    return Command::FAILURE;
                } catch (ArchiveExtractionException $ex){
                    $output->writeln("[-] Archive Extraction Exception: " . $ex->getMessage());
                    return Command::FAILURE;
                }

            } catch (\Exception $ex) {

                $output->writeln("[-] <error>Something went wrong</error>");

                // Cleanup if something went wrong
                $output->writeln("[>] Removing created files and directory...");
                if(\file_exists($temp_archive_dir)){
                    DirectoryHelper::delete($temp_archive_dir);
                }

                return Command::FAILURE;
            }

            // Make storage dir
            if(!DirectoryHelper::createIfNotExist($launcher_storage_dir)){
                $output->writeln("[-] Failed to create folder: $launcher_storage_dir");
                return Command::FAILURE;
            }

            // Move installer
            if(!\rename($temp_installer_path, $launcher_installer_storage_path)){
                $output->writeln("[-] Failed to move installer: $temp_installer_path -> $launcher_installer_storage_path");
                return Command::FAILURE;
            }

            // Make files dir
            if(!DirectoryHelper::createIfNotExist($launcher_files_storage_dir)){
                $output->writeln("[-] Failed to create folder: $launcher_files_storage_dir");
                return Command::FAILURE;
            }

            // Move files in files dir
            foreach (scandir($temp_archive_files_dir) as $file){
                if ($file != '.' && $file != '..'){
                    \copy($temp_archive_files_dir . '/' . $file, $launcher_files_storage_dir . '/' . $file);
                }
            }

            // Clean up
            $output->writeln("[>] Removing temporary files...");
            if(\file_exists($temp_archive_dir)){
                DirectoryHelper::delete($temp_archive_dir);
            }

            // Get launcher size
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($launcher_files_storage_dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }

            // Create entity in database
            $launcher = new LauncherRelease();
            $launcher->setName($gh_release->name);
            $launcher->setTag($gh_release->tag_name);
            $launcher->setSizeInBytes($size);
            $launcher->setTimestamp(new \DateTime($gh_release->published_at));
            $this->em->persist($launcher);
            $this->em->flush();

            // Show that this release is stored
            $output->writeln("[+] Launcher $gh_release->name stored in database");
        }

        // Success
        $output->writeln("[+] Done!");
        return Command::FAILURE;
    }
}
