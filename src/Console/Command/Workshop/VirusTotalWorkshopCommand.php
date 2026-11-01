<?php

namespace App\Console\Command\Workshop;

use App\Config\Config;
use App\Entity\WorkshopFile;
use App\Enum\UserRole;
use App\Notifications\Notification\VirusRemovedNotification;
use App\Notifications\NotificationCenter;
use App\VirusTotalScanner;
use App\VirusTotalScannerQuoteExceededException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\FileHelper;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'workshop:virustotal-submit', description: 'Send Workshop files to VirusTotal.')]
class VirusTotalWorkshopCommand extends Command
{
    public const MAX_MALICIOUS_COUNT = 3;

    public function __construct(
        private EntityManager $em,
        private NotificationCenter $nc,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('target', InputArgument::REQUIRED, 'Target to send (<id>|submitted|new|all)')
            ->addArgument('order', InputArgument::OPTIONAL, 'Order (ASC|DESC)');
    }

    protected function execute(Input $input, Output $output): int
    {
        // Define workshop storage dir
        $storage_dir = Config::get('storage.path.workshop');
        if ($storage_dir === null) {
            $output->writeln('[-] Workshop storage directory is not set');
            $output->writeln("[>] ENV VAR: 'APP_WORKSHOP_STORAGE'");

            return Command::FAILURE;
        }

        // Make sure VirusTotal API key is set
        if (empty($_ENV['APP_VIRUSTOTAL_API_KEY'])) {
            $output->writeln('[-] VirusTotal API key is not set');
            $output->writeln("[>] ENV VAR: 'APP_VIRUSTOTAL_API_KEY'");

            return Command::FAILURE;
        }

        // Get the order for this scan (only ASC and DESC allowed)
        $order = $input->getArgument('order');
        if (empty($order) || !\in_array(\strtoupper($order), ['ASC', 'DESC'])) {
            $order = 'ASC';
        } else {
            $order = \strtoupper($order);
        }

        // Get the target for this scan
        // all          -> scans every single item in the workshop, should not really be used except for dev environments
        // new          -> scans new items that are not scanned yet
        // scanned      -> scans previously scanned items
        // <id>[,<id>]  -> scans one or more items by their id
        $target = (string) $input->getArgument('target');

        if ($target === 'all') {

            $files = $this->em->getRepository(WorkshopFile::class)->findBy(
                [],
                ['created_timestamp' => $order]
            );
        } elseif ($target === 'new') {

            $files = $this->em->getRepository(WorkshopFile::class)->findBy(
                ['sent_to_virustotal' => false],
                ['created_timestamp' => $order]
            );
        } elseif ($target === 'submitted') {

            $files = $this->em->getRepository(WorkshopFile::class)->findBy(
                ['sent_to_virustotal' => true],
                ['created_timestamp' => $order]
            );
        } else {

            $ids = [];

            foreach (\explode(',', $target) as $id) {

                if (!\is_numeric($id)) {
                    $output->writeln("[-] Invalid ID or target to scan: {$id}");

                    return Command::FAILURE;
                }

                $id = (int) $id;

                if (\in_array($id, $ids)) {
                    $output->writeln("[?] Duplicate ID to scan: {$id} -> ignoring");
                    continue;
                }

                $ids[] = $id;
            }

            $files = $this->em->getRepository(WorkshopFile::class)->findBy(
                ['id' => $ids],
                ['created_timestamp' => $order]
            );
        }

        $file_count = \count($files);

        // Make sure we found some files to send
        if ($file_count < 1) {
            $output->writeln('[?] No files found that match the target');
            $output->writeln('[>] Done!');

            return Command::SUCCESS;
        }

        // Show how many files we are submitting
        $file_str = 'file' . ($file_count > 1 ? 's' : '');
        $output->writeln("[>] Submitting <comment>{$file_count}</comment> {$file_str} to VirusTotal...");
        $current_file_count = 1;

        // Loop trough all files
        /** @var WorkshopFile $file */
        foreach ($files as $file) {
            try {

                $storage_filename = $file->getStorageFilename();
                $filename         = $file->getFilename();
                $item_id          = $file->getItem()->getId();
                $item_name        = $file->getItem()->getName();

                $output->writeln("[>] Submitting: <comment>{$filename}</comment> [<info>{$item_name}</info>]");

                $path = $storage_dir . '/' . $item_id . '/files/' . $storage_filename;
                $output->writeln("\t[>] File: <info>{$path}</info>");

                // Make sure file exists
                if (FileHelper::isAccessible($path) === false) {
                    $output->writeln("\t[-] <error>File does not exist or is not accessible</error>");
                    continue;
                }

                // Show filesize
                $file_size = @\filesize($path);
                $output->writeln("\t[>] Size: <info>" . \App\Helper\FormatHelper::formatBytes($file_size) . '</info>');

                // Get sha256 hash of file
                $hash = \hash_file('sha256', $path);
                $output->writeln("\t[>] Hash (SHA256): <comment>{$hash}</comment>");

                // Check if hash is already known
                try {
                    $virustotal_data = VirusTotalScanner::getReportByHash($hash);
                } catch (VirusTotalScannerQuoteExceededException $ex) {
                    $output->writeln('[!] <error>API quota exceeded!</error>');

                    return Command::FAILURE;
                }

                if ($virustotal_data !== false) {
                    $output->writeln("\t[+] <info>Report found by hash! Skipping upload.</info>");
                } else {

                    // Not found by hash, submit the file
                    $output->writeln("\t[>] Hash not found on VirusTotal. Uploading file...");

                    try {
                        $virustotal_data = VirusTotalScanner::submitFile($path, $file->getFilename());
                    } catch (VirusTotalScannerQuoteExceededException $ex) {
                        $output->writeln('[!] <error>API quota exceeded!</error>');

                        return Command::FAILURE;
                    }

                    // Check if submission was unsuccessful
                    if ($virustotal_data === false) {
                        $output->writeln("\t[-] <error>Failed to submit file</error>");
                        continue;
                    }

                    $output->writeln("\t[>] Successfully submitted");
                }

                // Mark file as sent
                $file->setSentToVirusTotal(true);
                $this->em->flush();

                // Fallback stats array
                $stats = ['malicious' => 0];

                // Wait for analysis
                if ($virustotal_data['data']['type'] == 'analysis') {

                    // Get analysis URL
                    try {
                        $analysis_url = VirusTotalScanner::getAnalysisUrl($virustotal_data);
                    } catch (VirusTotalScannerQuoteExceededException $ex) {
                        $output->writeln('[!] <error>API quota exceeded!</error>');

                        return Command::FAILURE;
                    }

                    if ($analysis_url === false) {
                        $output->writeln("\t[-] <error>Failed to get analysis URL</error>");
                        continue;
                    }

                    // Get result stats from file
                    try {
                        $stats = VirusTotalScanner::waitForAnalysisAndGetStats(
                            analysis_url: $analysis_url,
                            retry: true,
                            sleep_seconds: 30,
                            max_retries: 10,
                            on_progress: static function (int $try_count, int $max_retries, int $sleep_seconds) use ($output) {
                                $output->writeln("\t[>] Waiting for analysis... (Attempt {$try_count}/{$max_retries}) [Sleeping {$sleep_seconds}s]");
                            }
                        );
                    } catch (VirusTotalScannerQuoteExceededException $ex) {
                        $output->writeln('[!] <error>API quota exceeded!</error>');

                        return Command::FAILURE;
                    }

                    if ($stats === false) {
                        $output->writeln("\t[-] <error>Analysis timed out or failed</error>");
                        continue;
                    }
                }

                // Check if file is already analyzed and get its stats
                if ($virustotal_data['data']['type'] == 'file') {
                    $stats = $virustotal_data['data']['attributes']['last_analysis_stats'] ?? ['malicious' => 0];
                }

                // Get amount of engines that consider the file to be malicious
                // and see if it is more than our minimum allowed amount
                $malicious_count = $stats['malicious'] ?? 0;
                if ($malicious_count >= self::MAX_MALICIOUS_COUNT) {

                    $output->writeln("\t[!] <error>Malware found!</error>");

                    // Remove from DB
                    $this->em->remove($file);
                    $this->em->flush();
                    $output->writeln("\t[+] File removed from the database!");

                    // Remove FILE
                    @\unlink($path);

                    // Make sure file is removed
                    if (\file_exists($path)) {
                        $output->writeln("\t[-] <error>Failed to remove file from filesystem...</error>");
                    } else {
                        $output->writeln("\t[+] File removed from filesystem!");
                    }

                    // Notify admins
                    $this->nc->sendNotificationToAllWithRole(
                        UserRole::Admin,
                        VirusRemovedNotification::class,
                        ['filename' => $filename, 'item_id' => $item_id, 'item_name' => $item_name]
                    );
                } else {
                    $output->writeln("\t[+] File looks <info>clean</info>");
                }

                if ($current_file_count < $file_count) {
                    $seconds = 45;
                    $output->writeln("[>] Sleeping {$seconds} seconds...");
                    \sleep($seconds);
                }

                ++$current_file_count;

            } catch (\Exception $ex) {

                $output->writeln("\t[-] Something went wrong");
                $output->writeln("\t[-] Error: {$ex->getMessage()}");
            }

        }

        return Command::SUCCESS;
    }
}
