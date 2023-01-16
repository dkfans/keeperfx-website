<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Process\Process;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\DirectoryHelper;

class HandleCommitsCommand extends Command
{
    public const PROJECT_DIR = APP_ROOT . '/keeperfx';

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
            return Command::FAILURE;
        }

        // Handle tags in ascending order: v1 -> v2 -> v3
        $releases = $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'ASC']);
        foreach($releases as $index => $release){

            // ALready handled commits for this release
            if($release->getCommitsHandled() === true){
                continue;
            }

            // Check if there is previous release
            if(!isset($releases[$index - 1])){

                // Can't handle first release
                $release->setCommitsHandled(true);
                $this->em->persist($release);
                continue;
            }

            // Get tag range
            $current_tag  = $release->getTag();
            $previous_tag = $releases[$index - 1]->getTag();
            $output->writeln("[>] Handling commits: {$previous_tag}...{$current_tag}");

            // Get git log between tags
            $process = new Process([
                'git',
                'log',
                $current_tag . '...' . $previous_tag,
                '--pretty=format:%H-----%aD-----%s'
            ], self::PROJECT_DIR);

            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to get git log");
                return Command::FAILURE;
            }

            $count = 0;

            // Loop trough all git log lines
            $log = $process->getOutput();
            foreach(\preg_split("/((\r?\n)|(\r\n?))/", $log) as $line){
                $exp = \explode('-----', $line);

                $commit = new GitCommit();
                $commit->setHash($exp[0]);
                $commit->setTimestamp(new \DateTime($exp[1]));
                $commit->setMessage($exp[2]);
                $commit->setRelease($release);

                $this->em->persist($commit);

                $count++;
            }

            $output->writeln("[+] Handled {$count} commits");

            $release->setCommitsHandled(true);
        }

        $output->writeln("[>] Writing changes to database...");
        $this->em->flush();

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
