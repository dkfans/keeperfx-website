<?php

namespace App\Console\Command\Website;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;

use Doctrine\ORM\EntityManager;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Process\Process;

use App\Helper\GitHelper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\DirectoryHelper;

class CacheWebsiteChangelogCommand extends Command
{
    public const PROJECT_DIR = APP_ROOT . '/var/keeperfx';

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache) {
        $this->cache = $cache;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("website:cache-git-commits")
            ->setDescription("Handle the commit history of the KeeperFX project");
    }

    protected function execute(Input $input, Output $output)
    {
        $commits = [];
        $commit_count = 0;

        $output->writeln("[>] Grabbing commits from local website repo...");

        // Run 'git log'
        $process = new Process(['git', 'log'], \APP_ROOT);
        $process->run();
        if(!$process->isSuccessful()){
            $output->writeln("[-] Failed to get git log");
            return Command::FAILURE;
        }

        // Get all commits
        $preg_matches = GitHelper::parseCommitsFromGitLog($process->getOutput());
        if(!$preg_matches){
            $output->writeln("[-] Failed to grab commits");
            return Command::FAILURE;
        }

        // Loop trough commits
        foreach($preg_matches as $match){

            // Get date
            $date_time = new \DateTime($match[3]);
            $date_str  = $date_time->format('Y-m-d');

            // Add to commits list
            $commits[$date_str][] = [
                'hash'    => $match[1],
                'author'  => $match[2],
                'date'    => $date_time,
                'subject' => $match[4],
            ];

            $commit_count++;
        }

        // Show commit count message
        if($commit_count > 0){
            $output->writeln("[+] Found {$commit_count} commits!");
        } else {
            $output->writeln("[?] No commits found");
        }

        // Store commits into cache
        $this->cache->set("website-changelog-commits", $commits);

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
