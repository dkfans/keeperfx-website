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
        private EntityManager $em,
        private GameFileHandler $game_file_handler,
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
        if ($storage_dir === null) {
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
        foreach ($stable_builds as $stable_build) {
            $stable_versions[] = $stable_build->getVersion();
        }

        // Get all alpha versions
        $alpha_versions = [];
        foreach ($alpha_patches as $alpha_patch) {
            $alpha_versions[] = $alpha_patch->getVersion();
        }

        // Check if stable directory is accessible
        $dir = Config::get('storage.path.game-files') . '/' . ReleaseType::STABLE->value;
        if (!DirectoryHelper::isAccessible($dir)) {
            $output->writeln("[-] Stable game files directory is not accessible: {$dir}");
        } else {
            // Remove all stable versions
            $removed_stable_versions = $this->game_file_handler->removeAllExcept(ReleaseType::STABLE, $stable_versions);
            foreach ($removed_stable_versions as $stable_version) {
                $output->writeln("[+] Removed: {$stable_version}");
            }
        }

        // Check if alpha directory is accessible
        $dir = Config::get('storage.path.game-files') . '/' . ReleaseType::ALPHA->value;
        if (!DirectoryHelper::isAccessible($dir)) {
            $output->writeln("[-] Alpha game files directory is not accessible: {$dir}");
        } else {
            // Remove all alpha versions
            $removed_alpha_versions  = $this->game_file_handler->removeAllExcept(ReleaseType::ALPHA, $alpha_versions);
            foreach ($removed_alpha_versions as $alpha_version) {
                $output->writeln("[+] Removed: {$alpha_version}");
            }
        }

        // Return
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
