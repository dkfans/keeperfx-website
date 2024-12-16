<?php

namespace App\Console\Command\KeeperFX;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\DirectoryHelper;
use Symfony\Component\Process\Process;

class FetchWikiCommand extends Command
{
    protected function configure()
    {
        $this->setName("kfx:fetch-wiki")
            ->setDescription("Fetch the latest wiki pages");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest wiki revision...");

        // Check for repo URL
        $repo_url = $_ENV['APP_WIKI_REPO_URL'];
        if(empty($repo_url)){
            $output->writeln("[-] Repo URL not configured (APP_WIKI_REPO_URL)");
            return Command::FAILURE;
        }

        // Get wiki dir
        // TODO: make CLI chroot accessible
        $wiki_dir = $_ENV['APP_WIKI_REPO_STORAGE'];
        if(empty($wiki_dir)){
            $output->writeln("[-] Wiki dir not configured");
            return Command::FAILURE;
        }

        // Output dir
        $output->writeln("[>] Wiki file directory: <info>{$wiki_dir}</info>");

        // Check if directory is accessible
        if(!DirectoryHelper::isAccessible($wiki_dir)){

            // Clone the wiki repo
            $output->writeln("[>] Cloning wiki repo...");
            $process = new Process(['git',  'clone', $repo_url, $wiki_dir]);
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
                $process = new Process(['git',  'clone', $repo_url, $wiki_dir]);
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
