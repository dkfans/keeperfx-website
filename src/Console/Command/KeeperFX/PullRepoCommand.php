<?php

namespace App\Console\Command\KeeperFX;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\DirectoryHelper;
use Symfony\Component\Process\Process;

class PullRepoCommand extends Command
{
    protected function configure()
    {
        $this->setName("kfx:pull-repo")
            ->setDescription("Pull the latest master branch of KeeperFX");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest KeeperFX source revision...");

        // Check for repo URL
        $repo_url = $_ENV['APP_KFX_REPO_URL'];
        if(empty($repo_url)){
            $output->writeln("[-] Repo URL not configured (APP_KFX_REPO_URL)");
            return Command::FAILURE;
        }

        // Get local keeperfx repo dir
        // TODO: make CLI chroot accessible
        $kfx_repo_dir = $_ENV['APP_KFX_REPO_STORAGE'];
        if(empty($kfx_repo_dir)){
            $output->writeln("[-] KeeperFX Repo dir not configured (APP_KFX_REPO_STORAGE)");
            return Command::FAILURE;
        }

        // Output dir
        $output->writeln("[>] KeeperFX project dir: {$kfx_repo_dir}");

        // Check if directory is accessible
        if(!DirectoryHelper::isAccessible($kfx_repo_dir)){

            // Clone the wiki repo
            $output->writeln("[>] Cloning KeeperFX repo...");
            $process = new Process(['git',  'clone', $repo_url, $kfx_repo_dir]);
            $process->run();
            if(!$process->isSuccessful()){
                $output->writeln("[-] Failed to clone wiki");
                return Command::FAILURE;
            }

        } else {

            // Check if directory is a git repo
            $output->writeln("[>] Checking if directory is a git repo...");
            $process = new Process(['git',  'status'], $kfx_repo_dir);
            $process->run();
            if(!$process->isSuccessful()){

                // Delete directory
                if(DirectoryHelper::delete($kfx_repo_dir) == false){
                    $output->writeln("[-] Failed to delete incompatible KeeperFX repo directory");
                    return Command::FAILURE;
                }

                // Clone the wiki repo
                $output->writeln("[>] Cloning KeeperFX repo...");
                $process = new Process(['git',  'clone', $repo_url, $kfx_repo_dir]);
                $process->run();
                if(!$process->isSuccessful()){
                    $output->writeln("[-] Failed to clone KeeperFX repo");
                    return Command::FAILURE;
                }

            } else {

                // Reset local repo changes
                $output->writeln("[>] Resetting local KeeperFX repo changes...");
                $process = new Process(['git',  'reset', '--hard'], $kfx_repo_dir);
                $process->run();
                if(!$process->isSuccessful()){
                    $output->writeln("[-] Failed to reset KeeperFX repo");
                    return Command::FAILURE;
                }

                // Update repo
                $output->writeln("[>] Pulling last KeeperFX repo...");
                $process = new Process(['git',  'pull'], $kfx_repo_dir);
                $process->run();
                if(!$process->isSuccessful()){
                    $output->writeln("[-] Failed to pull KeeperFX repo");
                    return Command::FAILURE;
                }
            }
        }

        // Success!!
        $output->writeln("[+] Done!");
        return Command::SUCCESS;

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
