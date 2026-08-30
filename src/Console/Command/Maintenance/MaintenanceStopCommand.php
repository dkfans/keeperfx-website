<?php

namespace App\Console\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'maintenance:stop', description: 'Stop maintenance mode.')]
class MaintenanceStopCommand extends Command
{
    private const MAINTENANCE_FILE = APP_ROOT . '/__MAINTENANCE_MODE_ACTIVE';

    protected function execute(Input $input, Output $output): int
    {
        if (\unlink(self::MAINTENANCE_FILE)) {
            $output->writeln('[+] Maintenance mode stopped');
        } else {
            $output->writeln('[-] Maintenance mode failed to stop');
            $output->writeln('[-] Try manually deleting file: ' . self::MAINTENANCE_FILE);
        }

        return Command::SUCCESS;
    }
}
