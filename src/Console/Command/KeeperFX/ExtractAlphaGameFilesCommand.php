<?php

namespace App\Console\Command\KeeperFX;

use App\Enum\ReleaseType;

use App\Entity\GithubRelease;

use App\Config\Config;
use App\Entity\GithubAlphaBuild;
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
use Xenokore\Utility\Helper\FileHelper;

class ExtractAlphaGameFilesCommand extends Command
{
    public function __construct(
        private EntityManager $em,
        private GameFileHandler $game_file_handler
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:extract-alpha-game-files")
            ->setDescription("Extract the game files for an alpha patch")
            ->addArgument('version', InputArgument::REQUIRED, 'Alpha patch version');
    }


    protected function execute(Input $input, Output $output)
    {
        // Make sure the game files directory is set
        $game_file_dir = Config::get('storage.path.game-files');
        if($game_file_dir === null) {
            $output->writeln("[-] Game files directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_GAME_FILE_STORAGE'");
            return Command::FAILURE;
        }

        // Make sure the alpha patch archive directory is set
        $archive_dir = Config::get('storage.path.alpha-patch');
        if($archive_dir === null){
            $output->writeln("[-] Alpha build download directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_ALPHA_PATCH_STORAGE_CLI_PATH' or 'APP_ALPHA_PATCH_STORAGE'");
            return Command::FAILURE;
        }

        // Get version
        $version = (string) $input->getArgument('version');
        /** @var GithubAlphaBuild $alpha_patch */
        $alpha_patch = $this->em->getRepository(GithubAlphaBuild::class)->findOneBy(['version' => $version]);

        // Make sure version is found
        if(!$alpha_patch){
            $output->writeln("[-] Alpha patch version '{$version}' not found");
            return Command::FAILURE;
        }

        // Get archive
        $alpha_patch_archive_path = $archive_dir . "/" . $alpha_patch->getFilename();

        // Make sure archive is accessible
        if(!FileHelper::isAccessible($alpha_patch_archive_path)){
            $output->writeln("[-] Alpha patch archive is not accessible: {$alpha_patch_archive_path}");
            return Command::FAILURE;
        }

        // Extraction output dir
        $temp_archive_dir  = \sys_get_temp_dir() . '/' . $alpha_patch->getName() . "-gamefile";

        try {

            // Remove any leftover temp files
            if(\file_exists($temp_archive_dir)){
                DirectoryHelper::delete($temp_archive_dir);
            }

            // Open the archive
            $temp_archive = UnifiedArchive::open($alpha_patch_archive_path);
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
            $game_files_store_result = $this->game_file_handler->storeVersionFromPath(ReleaseType::ALPHA, $version, $temp_archive_dir);
            if(!$game_files_store_result){
                $output->writeln("[-] Failed to move game files");
                return Command::FAILURE;
            }
            $output->writeln("[+] {$game_files_store_result} game files stored");

        } catch (\Exception $ex) {

            $output->writeln("[-] <error>Something went wrong</error>");

            // Cleanup if something went wrong
            $output->writeln("[>] Removing temp directory...");
            if(\file_exists($temp_archive_dir)){
                DirectoryHelper::delete($temp_archive_dir);
            }

            return Command::FAILURE;
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
