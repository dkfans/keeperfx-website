<?php

namespace App\Console\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'maintenance:start', description: 'Start maintenance mode. Disables any client interaction with the app.')]
class MaintenanceStartCommand extends Command
{
    private const MAINTENANCE_FILE = APP_ROOT . '/__MAINTENANCE_MODE_ACTIVE';

    protected function execute(Input $input, Output $output): int
    {
        if (\touch(self::MAINTENANCE_FILE)) {
            $output->writeln('[+] Maintenance mode started');
        } else {
            $output->writeln('[-] Maintenance mode failed to start');
            $output->writeln('[-] Unable to touch file: ' . self::MAINTENANCE_FILE);
        }

        return Command::SUCCESS;
    }
}
