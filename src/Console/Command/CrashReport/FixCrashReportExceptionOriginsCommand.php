<?php

namespace App\Console\Command\CrashReport;

use App\Entity\CrashReport;
use App\Workshop\WorkshopHelper;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class FixCrashReportExceptionOriginsCommand extends Command
{
    /** @var Container $container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("crash-report:fix-exception-origins")
            ->setDescription("Grab and set all exception origin in crash reports");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[+] Fixing the exception origin for crash reports...");

        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        // Get crash reports
        /** @var array[CrashReport] $crash_reports */
        $crash_reports = $em->getRepository(CrashReport::class)->findAll();
        if (!$crash_reports || !is_array($crash_reports) || \count($crash_reports) <= 0) {
            $output->writeln("[?] No crash reports found");
            return Command::INVALID;
        }

        $reports_updated = 0;

        // Loop trough all workshop items
        /** @var CrashReport $report */
        foreach ($crash_reports as $crash_report) {

            $game_log = $crash_report->getGameLog();
            if (empty($game_log)) {
                continue;
            }

            if (\preg_match('/\[\#\d+\s?\]\s\S+\s+\:\s+(\S+)\s+\[/', $game_log, $matches)) {
                $exception_source_function = $matches[1] ?? null;
                if (!empty($exception_source_function)) {
                    $crash_report->setExceptionSourceFunction($exception_source_function);
                    $output->writeln("[+] {$crash_report->getId()} -> {$exception_source_function}");
                    $reports_updated++;
                }
            }
        }

        // Save changes to DB
        if ($reports_updated > 0) {
            $em->flush();
        }

        // Show output
        if ($reports_updated > 0) {
            $output->writeln("[+] <info>{$reports_updated}</info> crash reports updated!");
        }

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
