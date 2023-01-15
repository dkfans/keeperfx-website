<?php

namespace App\Console\Command\Project;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\DirectoryHelper;
use Symfony\Component\Process\Process;

class ProjectPullCommand extends Command
{
    public const GIT_REPO = 'https://github.com/dkfans/keeperfx.git';

    public const PROJECT_DIR = APP_ROOT . '/keeperfx';

    protected function configure()
    {
        $this->setName("project:pull")
            ->setDescription("Pull the latest master branch of KeeperFX");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] KeeperFX project dir: " . self::PROJECT_DIR);

        if(!DirectoryHelper::isAccessible(self::PROJECT_DIR)){

            $output->writeln("[>] Cloning KeeperFX...");

            $process = new Process(['git',  'clone', self::GIT_REPO, self::PROJECT_DIR]);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to clone KeeperFX project");
                return Command::FAILURE;
            }

        } else {

            $output->writeln("[>] Pulling KeeperFX master branch...");

            $process = new Process(['git',  'reset', '--hard'], self::PROJECT_DIR);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to reset KeeperFX master branch");

                return Command::FAILURE;
            }

            $process = new Process(['git',  'pull'], self::PROJECT_DIR);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to pull KeeperFX master branch");
                return Command::FAILURE;
            }

        }

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
