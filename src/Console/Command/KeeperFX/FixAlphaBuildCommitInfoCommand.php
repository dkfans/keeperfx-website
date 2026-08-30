<?php

namespace App\Console\Command\KeeperFX;

use App\Config\Config;
use App\Entity\GithubAlphaBuild;
use App\Helper\GitHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Process\Process;
use Xenokore\Utility\Helper\DirectoryHelper;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'kfx:fix-alpha-commit-info', description: 'Try to fix the commit SHA and commit note for alpha builds')]
class FixAlphaBuildCommitInfoCommand extends Command
{
    public function __construct(
        private EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'Alpha patch version');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('[>] Trying to fix the commit SHA and commit note for alpha build(s)...');

        // Get local keeperfx repo dir
        $kfx_repo_dir = Config::get('storage.path.kfx-repo');
        if (empty($kfx_repo_dir)) {
            $output->writeln('[-] KeeperFX Repo dir not configured (APP_KFX_REPO_STORAGE)');

            return Command::FAILURE;
        }

        // Make sure project directory exists
        if (!DirectoryHelper::isAccessible($kfx_repo_dir)) {
            $output->writeln('[-] Directory does not exist: ' . $kfx_repo_dir);
            $output->writeln("[>] Run the 'kfx:pull-repo' command first");

            return Command::FAILURE;
        }

        // Create process 'git log --full-history'
        $process = new Process([
            'git',
            'log',
            '--full-history',
        ], $kfx_repo_dir);

        // Run the process
        $process->run();
        if (!$process->isSuccessful()) {
            $output->writeln('[-] Failed to get git log');

            return Command::FAILURE;
        }

        // Get the git log commits
        $parsed_commits = GitHelper::parseCommitsFromGitLog($process->getOutput());
        if (!$parsed_commits) {
            $output->writeln('[-] Failed to grab commits');

            return Command::FAILURE;
        }

        // Get version
        $version = (string) $input->getArgument('version');
        if (!empty($version)) {
            $alpha_builds = $this->em->getRepository(GithubAlphaBuild::class)->findBy(['version' => $version]);
        } else {
            // Get all the alpha builds
            $alpha_builds = $this->em->getRepository(GithubAlphaBuild::class)->findAll();
        }

        if (\count($alpha_builds) < 1) {
            $output->writeln('[-] Failed to grab alpha build(s)');

            return Command::FAILURE;
        }

        $alpha_build_updated_count = 0;

        // Loop trough all alpha builds
        /** @var GithubAlphaBuild $alpha */
        foreach ($alpha_builds as $alpha) {

            $title          = $alpha->getName();
            $workflow_title = $alpha->getWorkflowTitle();

            // Loop trough all commits
            foreach ($parsed_commits as $commit) {

                if (empty($commit['message']) || $commit['message'] !== $workflow_title) {
                    continue;
                }

                $alpha->setWorkflowTitle($commit['message']);
                $alpha->setCommitSha($commit['hash']);
                $alpha->setCommitComment($commit['note']);

                $output->writeln("[+] Updating <info>{$title}</info>: [<fg=yellow;options=bold>{$commit['hash']}</>] {$workflow_title}");

                ++$alpha_build_updated_count;
            }
        }

        // Flush changes to database
        $output->writeln('[>] Updating database...');
        $this->em->flush();

        // Let user know what happened
        $output->writeln("[+] Updated {$alpha_build_updated_count} alpha builds");
        $output->writeln('[+] Done!');

        return Command::SUCCESS;
    }
}
