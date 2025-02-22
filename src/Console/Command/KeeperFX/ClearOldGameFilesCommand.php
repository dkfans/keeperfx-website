<?php

namespace App\Console\Command\KeeperFX;

use App\Enum\ReleaseType;

use App\Config\Config;
use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use App\GameFileHandler;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\DirectoryHelper;

class ClearOldGameFilesCommand extends Command
{
    public function __construct(
        private EntityManager $em
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:clear-old-game-files")
            ->setDescription("Clear old game files");
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

        // Get all releases
        $stable_builds = $this->em->getRepository(GithubRelease::class)->findBy(
            [],
            ['timestamp' => 'DESC'],
            (int)$_ENV['APP_GAME_FILE_MAX_STABLE_VERSIONS']
        );
        $alpha_patches = $this->em->getRepository(GithubAlphaBuild::class)->findBy(
            [],
            ['timestamp' => 'DESC'],
            (int)$_ENV['APP_GAME_FILE_MAX_ALPHA_VERSIONS']
        );

        // Get all stable versions
        $stable_versions = [];
        foreach($stable_builds as $stable_build){
            $stable_versions[] = $stable_build->getVersion();
        }

        // Get all alpha versions
        $alpha_versions = [];
        foreach($alpha_patches as $alpha_patch){
            $alpha_versions[] = $stable_build->getVersion();
        }

        // Get game files directories
        $stable_versions_dir = Config::get('storage.path.game-files') . '/' . ReleaseType::STABLE->value;
        $alpha_versions_dir  = Config::get('storage.path.game-files') . '/' . ReleaseType::ALPHA->value;
        if (!DirectoryHelper::isAccessible($stable_versions_dir)) {
            throw new \RuntimeException("Directory is not accessible: $stable_versions_dir");
        }
        if (!DirectoryHelper::isAccessible($alpha_versions_dir)) {
            throw new \RuntimeException("Directory is not accessible: $alpha_versions_dir");
        }

        // Handle stable versions
        foreach (new \FilesystemIterator($stable_versions_dir, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir()) {
                // Check if we can remove this version dir
                if(!\in_array($item->getFilename(), $stable_versions, true)) {
                    // Remove it
                    if(!DirectoryHelper::delete($item->getPathname())){
                        $output->writeln("[-] Failed to remove stable version dir: {$item->getPathname()}");
                    } else {
                        $output->writeln("[+] Directory removed: {$item->getPathname()}");
                    }
                }
            }
        }

        // Handle alpha versions
        foreach (new \FilesystemIterator($alpha_versions_dir, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir()) {
                // Check if we can remove this version dir
                if(!\in_array($item->getFilename(), $alpha_versions, true)) {
                    // Remove it
                    if(!DirectoryHelper::delete($item->getPathname())){
                        $output->writeln("[-] Failed to remove alpha version dir: {$item->getPathname()}");
                    } else {
                        $output->writeln("[+] Directory removed: {$item->getPathname()}");
                    }
                }
            }
        }

        // Return
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
