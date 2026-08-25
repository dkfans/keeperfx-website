<?php

namespace App\Console\Command\Website;

use App\Helper\GitHelper;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Process\Process;

class CacheWebsiteChangelogCommand extends Command
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('website:cache-git-commits')
            ->setDescription('Handle the commit history of the KeeperFX website');
    }

    protected function execute(Input $input, Output $output)
    {
        $commits = [];
        $output->writeln('[>] Grabbing commits from local website repo...');

        // Run 'git log'
        $process = new Process(['git', 'log'], \APP_ROOT);
        $process->run();
        if (!$process->isSuccessful()) {
            $output->writeln('[-] Failed to get git log');

            return Command::FAILURE;
        }

        // Get all commits
        $parsed_commits = GitHelper::parseCommitsFromGitLog($process->getOutput());
        if ($parsed_commits === false) {
            $output->writeln('[-] Failed to grab commits');

            return Command::FAILURE;
        }

        // Loop trough commits
        foreach ($parsed_commits as $parsed_commit) {

            // Create date string
            $date_str = $parsed_commit['timestamp']->format('Y-m-d');

            // Add to commits list
            $commits[$date_str][] = [
                'message' => $parsed_commit['message'],
                'hash'    => $parsed_commit['hash'],
            ];
        }

        // Show commit count message
        $commit_count = \count($parsed_commits);
        if ($commit_count > 0) {
            $output->writeln("[+] Found {$commit_count} commits!");
        } else {
            $output->writeln('[?] No commits found');
        }

        // Store commits into cache
        $this->cache->set('website-changelog-commits', $commits);

        // Success
        $output->writeln('[+] Done!');

        return Command::SUCCESS;
    }
}
