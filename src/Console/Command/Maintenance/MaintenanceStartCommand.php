<?php

namespace App\Console\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class MaintenanceStartCommand extends Command
{
    private const MAINTENANCE_FILE = APP_ROOT . '/__MAINTENANCE_MODE_ACTIVE';

    protected function configure()
    {
        $this->setName("maintenance:start")
            ->setDescription("Start maintenance mode. Disables any client interaction with the app.");
    }

    protected function execute(Input $input, Output $output)
    {
        if(\touch(self::MAINTENANCE_FILE)){
            $output->writeln("[+] Maintenance mode started");
        } else {
            $output->writeln("[-] Maintenance mode failed to start");
            $output->writeln("[-] Unable to touch file: " . self::MAINTENANCE_FILE);
        }

        return Command::SUCCESS;
    }
}
