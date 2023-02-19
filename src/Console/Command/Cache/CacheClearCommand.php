<?php

namespace App\Console\Command\Cache;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CacheClearCommand extends Command
{
    public const CACHE_DIR = APP_ROOT . '/cache';

    protected function configure()
    {
        $this->setName("cache:clear")
            ->setDescription("Clear the cache directory");
    }

    protected function execute(Input $input, Output $output)
    {
        $current_user = \exec('whoami');
        $owning_user  = \get_current_user();

        if($current_user !== $owning_user){
            $output->writeln('[!] <error>Current user and script owner do not match!</error>');
            $output->writeln('[>] User executing the command: ' . $current_user);
            $output->writeln('[>] Script owner: ' . $owning_user);
            $output->writeln('[!] Running this command might result in permission errors.');
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('[?] Continue? [y/n] ', false);
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $output->writeln('[>] Clearing cache directory: ' . self::CACHE_DIR);

        $iterator = new \RecursiveDirectoryIterator(self::CACHE_DIR, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        $dir_count  = 0;
        $file_count = 0;
        $dir_count_deleted  = 0;
        $file_count_deleted = 0;

        /** @var \SplFileInfo $file */
        foreach($files as $file) {
            $path = $file->getRealPath();

            if($file->getFilename() === '.gitignore'){
                continue;
            }

            if ($file->isDir()){
                $dir_count++;
                if(@\rmdir($path)){
                    $dir_count_deleted++;
                    $output->writeln("[+] DIR: <info>{$path}</info> DELETED");
                } else {
                    $output->writeln("[-] DIR: <error>{$path}</error> FAILED");
                }
            } else {
                $file_count++;
                if(@\unlink($path)){
                    $file_count_deleted++;
                    $output->writeln("[+] FILE: <info>{$path}</info> DELETED");
                } else {
                    $output->writeln("[-] FILE: <error>{$path}</error> FAILED");
                }
            }
        }

        $output->writeln("[>] Done!");
        $output->writeln("[>] Files deleted: {$file_count_deleted}/{$file_count}");
        $output->writeln("[>] Directories removed: {$dir_count_deleted}/{$dir_count}");

        return Command::SUCCESS;
    }
}
