<?php

namespace App\Console\Command\KeeperFX;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\DirectoryHelper;
use Symfony\Component\Process\Process;

class FetchWikiCommand extends Command
{
    public const GITHUB_WIKI_URL = 'https://github.com/dkfans/keeperfx.wiki.git';

    protected function configure()
    {
        $this->setName("kfx:fetch-wiki")
            ->setDescription("Fetch the latest wiki pages");
    }

    protected function execute(Input $input, Output $output)
    {
        // Get wiki dir
        // TODO: make CLI chroot accessible
        $wiki_dir = $_ENV['APP_WIKI_STORAGE'];
        if(empty($wiki_dir)){
            $output->writeln("[-] Wiki dir not configured");
            return Command::FAILURE;
        }

        // Output some text
        $output->writeln("[>] Fetching latest wiki revision...");
        $output->writeln("[>] Wiki file directory: <info>{$wiki_dir}</info>");

        // Check if directory is accessible
        if(!DirectoryHelper::isAccessible($wiki_dir)){

            // Clone the wiki repo
            $output->writeln("[>] Cloning wiki repo...");
            $process = new Process(['git',  'clone', self::GITHUB_WIKI_URL, $wiki_dir]);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to clone wiki");
                return Command::FAILURE;
            }

        } else {

            // Check if directory is a git repo
            $output->writeln("[>] Checking if directory is a git repo...");
            $process = new Process(['git',  'status'], $wiki_dir);
            $process->run();
            if(!$process->isSuccessful()){

                // Delete directory
                if(DirectoryHelper::delete($wiki_dir) == false){
                    $output->writeln("[-] Failed to delete incompatible wiki directory");
                    return Command::FAILURE;
                }

                // Clone the wiki repo
                $output->writeln("[>] Cloning wiki repo...");
                $process = new Process(['git',  'clone', self::GITHUB_WIKI_URL, $wiki_dir]);
                $process->run();
                if(!$process->isSuccessful()){
                    $output->writeln("[-] Failed to clone wiki");
                    return Command::FAILURE;
                }

            } else {

                // Reset local repo changes
                $output->writeln("[>] Resetting local wiki changes...");
                $process = new Process(['git',  'reset', '--hard'], $wiki_dir);
                $process->run();
                if(!$process->isSuccessful()){
                    $output->writeln("[-] Failed to reset wiki");
                    return Command::FAILURE;
                }

                // Update repo
                $output->writeln("[>] Pulling last wiki repo...");
                $process = new Process(['git',  'pull'], $wiki_dir);
                $process->run();
                if(!$process->isSuccessful()){
                    $output->writeln("[-] Failed to pull wiki");
                    return Command::FAILURE;
                }
            }
        }

        // Success!!
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
