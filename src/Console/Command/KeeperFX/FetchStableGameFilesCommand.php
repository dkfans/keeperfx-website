<?php

namespace App\Console\Command\KeeperFX;

use App\Enum\ReleaseType;

use App\Entity\GithubRelease;

use App\Config\Config;
use App\GameFileHandler;
use Directory;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\JsonHelper;
use Xenokore\Utility\Helper\DirectoryHelper;

use wapmorgan\UnifiedArchive\UnifiedArchive;
use wapmorgan\UnifiedArchive\Exceptions\EmptyFileListException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;

class FetchStableGameFilesCommand extends Command
{
    public function __construct(
        private EntityManager $em,
        private GameFileHandler $game_file_handler,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-stable-game-files")
            ->setDescription("Fetch the game files for a stable release")
            ->addArgument('version', InputArgument::REQUIRED, 'Stable version');
    }


    protected function execute(Input $input, Output $output)
    {
        // Make sure the game files directory is set
        $storage_dir = Config::get('storage.path.game-files');
        if($storage_dir === null) {
            $output->writeln("[-] Game files directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_GAME_FILE_STORAGE'");
            return Command::FAILURE;
        }

        // Get version
        $version = (string) $input->getArgument('version');
        $release = $this->em->getRepository(GithubRelease::class)->findOneBy(['version' => $version]);

        // Make sure version is found
        if(!$release){
            $output->writeln("[-] Stable release version '{$version}' not found");
            return Command::FAILURE;
        }

        // Create HTTP client
        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        try {

            // Variables for file download
            $exp               = \explode('/', $release->getDownloadUrl());
            $filename          = \end($exp);
            $temp_archive_path = \sys_get_temp_dir() . '/' . $filename;
            $temp_archive_dir  = \sys_get_temp_dir() . '/' . $release->getName();

            // Remove any leftover temp files
            if(\file_exists($temp_archive_path)){
                \unlink($temp_archive_path);
            }
            if(\file_exists($temp_archive_dir)){
                DirectoryHelper::delete($temp_archive_dir);
            }

            // Download the archive
            $output->writeln("[>] Downloading: {$release->getName()} -> <info>{$temp_archive_path}</info> ({$release->getSizeInBytes()} bytes)");
            $client->request('GET', $release->getDownloadUrl(), ['sink' => $temp_archive_path]);
            if(!\file_exists($temp_archive_path)){
                $output->writeln("[-] Failed to download release");
                return Command::FAILURE;
            } else {
                $output->writeln("[+] Release downloaded!");
            }

            // Open the archive
            $temp_archive = UnifiedArchive::open($temp_archive_path);
            if($temp_archive === null){
                $output->writeln("[-] Failed to open the archive");
                return Command::FAILURE;
            }

            // Check if output directory exists
            if(!DirectoryHelper::isAccessible($temp_archive_dir)){
                DirectoryHelper::createIfNotExist($temp_archive_dir);
            }

            // Extract the files
            $output->writeln("[>] Extracting...");
            try {
                $temp_archive->extract($temp_archive_dir);
            } catch (EmptyFileListException $ex){
                $output->writeln("[-] No files in archive");
                return Command::FAILURE;
            } catch (ArchiveExtractionException $ex){
                $output->writeln("[-] Archive Extraction Exception: " . $ex->getMessage());
                return Command::FAILURE;
            }

            // Move files with game file handler
            $game_files_store_result = $this->game_file_handler->storeVersionFromPath(ReleaseType::STABLE, $version, $temp_archive_dir);
            if(!$game_files_store_result){
                $output->writeln("[-] Failed to move game files");
                return Command::FAILURE;
            }
            $output->writeln("[+] {$game_files_store_result} game files stored");

        } catch (\Exception $ex) {

            $output->writeln("[-] <error>Something went wrong</error>");

            // Cleanup if something went wrong
            $output->writeln("[>] Removing created files and directory...");
            if(\file_exists($temp_archive_path)){
                \unlink($temp_archive_path);
            }
            if(\file_exists($temp_archive_dir)){
                DirectoryHelper::delete($temp_archive_dir);
            }

            return Command::FAILURE;
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
