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
    public const PROJECT_DIR = APP_ROOT . '/var/keeperfx';

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
        $output->writeln("[>] Handling project commits...");

        // Make sure project directory exists
        if(!DirectoryHelper::isAccessible(self::PROJECT_DIR)){
            $output->writeln("[-] Directory does not exist: " . self::PROJECT_DIR);
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
            ], self::PROJECT_DIR);

            // Run the process
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to get git log");
                return Command::FAILURE;
            }

            // Get the git log commits
            $preg_matches = GitHelper::parseCommitsFromGitLog($process->getOutput());
            if(!$preg_matches){
                $output->writeln("[-] Failed to grab commits for {$current_tag}");
                continue;
            }

            // Loop trough all commits
            $commit_count = 0;
            foreach($preg_matches as $match){

                $commit = new GitCommit();
                $commit->setHash($match[1]);
                $commit->setTimestamp(new \DateTime($match[3]));
                $commit->setMessage($match[4]);
                $commit->setRelease($github_release);

                $this->em->persist($commit);

                $commit_count++;
            }

            $output->writeln("[+] Handled {$commit_count} commits");

            $github_release->setCommitsHandled(true);
        }

        $output->writeln("[>] Writing changes to database...");
        $this->em->flush();

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
