<?php

namespace App\Console\Command\Website;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;


class BenchmarkBcryptHashCost extends Command
{
    protected function configure()
    {
        $this->setName("website:benchmark-bcrypt-hash-cost")
            ->setDescription("Benchmark and calculate an appropriate bcrypt password hash cost")
            ->addArgument('time', InputArgument::REQUIRED, 'Minimum target time in seconds. Decimals are possible');
    }

    protected function execute(Input $input, Output $output)
    {
        // Time Target
        $time_target = (float)$input->getArgument('time');
        if(!$time_target || $time_target < 0){
            $output->writeln("[-] Invalid time target");
            return Command::FAILURE;
        }

        // Show that we start
        $output->writeln("[>] Calculating appropriate password hash cost...");
        $output->writeln("[>] Time target: " . \number_format($time_target, 8) . "s");

        // Benchmark the cost
        // https://www.php.net/manual/en/function.password-hash.php
        $cost = 10;
        $total_time = 0;
        do {
            $cost++;
            $start = \microtime(true);
            \password_hash("MyPassword1", \PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = \microtime(true);
            $total_time = $end - $start;

            $output->writeln("[>] Cost {$cost}: {$total_time}s");

        } while ($total_time < $time_target);

        // Success
        $output->writeln("[+] Cost found!");
        $output->writeln("[+] Time: {$total_time}s");
        $output->writeln("[+] Cost: <info>{$cost}</info>");
        return Command::SUCCESS;
    }
}
