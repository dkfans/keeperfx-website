<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Process\Process;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use App\Helper\GitHelper;
use Xenokore\Utility\Helper\DirectoryHelper;

class HandleCommitsCommand extends Command
{
    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:handle-commits")
            ->setDescription("Handle the commit history of the KeeperFX project");
    }

    protected function execute(Input $input, Output $output)
    {
        $commits_handled = false;
        $output->writeln("[>] Handling project commits...");

        // Get local keeperfx repo dir
        // TODO: make CLI chroot accessible
        $kfx_repo_dir = $_ENV['APP_KFX_REPO_STORAGE'];
        if(empty($kfx_repo_dir)){
            $output->writeln("[-] KeeperFX Repo dir not configured (APP_KFX_REPO_STORAGE)");
            return Command::FAILURE;
        }

        // Make sure project directory exists
        if(!DirectoryHelper::isAccessible($kfx_repo_dir)){
            $output->writeln("[-] Directory does not exist: " . $kfx_repo_dir);
            $output->writeln("[>] Run the 'kfx:pull-repo' command first");
            return Command::FAILURE;
        }

        // Handle tags in ascending order: v1 -> v2 -> v3
        $github_releases = $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'ASC']);
        foreach($github_releases as $index => $github_release){

            // ALready handled commits for this release
            if($github_release->getCommitsHandled() === true){
                continue;
            }

            // Check if there is previous release
            if(!isset($github_releases[$index - 1])){

                // Can't handle first release
                $github_release->setCommitsHandled(true);
                $this->em->persist($github_release);
                continue;
            }

            // Get tag range
            $current_tag  = $github_release->getTag();
            $previous_tag = $github_releases[$index - 1]->getTag();
            $output->writeln("[>] Handling commits: {$previous_tag} -> {$current_tag}");

            // Create process 'git log' between tags
            // We use '<TAG> --not <TAG2>' instead of '<TAG>...<TAG2>' as this leaves out commits in a different branch
            $process = new Process([
                'git',
                'log',
                $current_tag,
                '--not',
                $previous_tag,
            ], $kfx_repo_dir);

            // Run the process
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to get git log");
                return Command::FAILURE;
            }

            // Get the git log commits
            $parsed_commits = GitHelper::parseCommitsFromGitLog($process->getOutput());
            if(!$parsed_commits){
                $output->writeln("[-] Failed to grab commits for {$current_tag}");
                continue;
            }

            // Loop trough all commits
            foreach($parsed_commits as $parsed_commit){

                $commit = new GitCommit();
                $commit->setHash($parsed_commit['hash']);
                $commit->setTimestamp($parsed_commit['timestamp']);
                $commit->setMessage($parsed_commit['message']);
                $commit->setRelease($github_release);

                $this->em->persist($commit);
            }

            // Show commit count message
            if(($commit_count = \count($parsed_commits)) > 0){
                $output->writeln("[+] Handled {$commit_count} commits!");
            } else {
                $output->writeln("[?] No commits handled");
            }

            // Make this release as handled
            // This makes it so these commits are not handled again
            $github_release->setCommitsHandled(true);

            // Remember if we handled some commits as we'll have to flush the DB changes
            $commits_handled = true;
        }

        // If we handled commits we'll have to flush the DB changes
        if($commits_handled){
            $output->writeln("[>] Writing changes to database...");
            $this->em->flush();
        } else {
            $output->writeln("[*] No commits were handled");
        }

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
