<?php

namespace App\Console\Command\Github;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\DirectoryHelper;
use Symfony\Component\Process\Process;

class GithubFetchWikiCommand extends Command
{
    public const GITHUB_WIKI_URL = 'https://github.com/dkfans/keeperfx.wiki.git';

    protected function configure()
    {
        $this->setName("github:fetch-wiki")
            ->setDescription("Fetch the latest wiki pages");
    }

    protected function execute(Input $input, Output $output)
    {
        $dir = APP_ROOT . '/wiki';

        $output->writeln("[>] Fetching latest wiki revision...");

        if(!DirectoryHelper::isAccessible($dir)){

            $output->writeln("[>] Cloning wiki...");

            $process = new Process(['git',  'clone', self::GITHUB_WIKI_URL, $dir]);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to clone wiki");
                return Command::FAILURE;
            }

        } else {

            $output->writeln("[>] Pulling wiki...");

            $process = new Process(['git',  'reset', '--hard'], $dir);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to reset wiki");

                return Command::FAILURE;
            }

            $process = new Process(['git',  'pull'], $dir);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to pull wiki");
                return Command::FAILURE;
            }

        }

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
