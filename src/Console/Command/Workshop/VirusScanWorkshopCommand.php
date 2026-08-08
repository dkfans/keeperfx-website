<?php

namespace App\Console\Command\Workshop;

use App\Config\Config;
use App\Entity\WorkshopFile;
use App\Enum\UserRole;
use App\Enum\WorkshopScanStatus;
use App\Notifications\Notification\VirusRemovedNotification;
use App\Notifications\NotificationCenter;
use Appwrite\ClamAV\Network;
use Appwrite\ClamAV\Pipe;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Xenokore\Utility\Helper\StringHelper;

class VirusScanWorkshopCommand extends Command
{
    public function __construct(
        private EntityManager $em,
        private NotificationCenter $nc,
    ) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("workshop:virus-scan")
            ->setDescription("Use ClamAV to scan workshop files.")
            ->addArgument('target', InputArgument::REQUIRED, 'Target to scan (<id>|scanned|new|all)')
            ->addArgument('order', InputArgument::OPTIONAL, 'Order (ASC|DESC)');
    }

    protected function execute(Input $input, Output $output)
    {
        // Define workshop storage dir
        $storage_dir = Config::get('storage.path.workshop');
        if ($storage_dir === null) {
            $output->writeln("[-] Workshop storage directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_WORKSHOP_STORAGE'");
            return Command::FAILURE;
        }

        $output->writeln("[>] Setting up ClamAV client...");

        // Setup client
        try {

            $dsn = $_ENV['APP_CLAMAV_DSN'] ?? null;

            if (!\is_string($dsn)) {
                $output->writeln("[-] Invalid ClamAV DSN string ('APP_CLAMAV_DSN')");
                return Command::FAILURE;
            }

            if (StringHelper::startsWith($dsn, 'unix://')) {
                $clam = new Pipe(StringHelper::subtract($dsn, \strlen('unix://')));
            } elseif (StringHelper::startsWith($dsn, 'tcp://')) {
                $exp = \explode(':', StringHelper::subtract($dsn, \strlen('tcp://')));
                if (\count($exp) !== 2) {
                    $output->writeln("[-] Invalid ClamAV DSN string ('APP_CLAMAV_DSN')");
                    return Command::FAILURE;
                }
                $clam = new Network($exp[0], $exp[1]);
            } else {
                $output->writeln("[-] Invalid ClamAV DSN string ('APP_CLAMAV_DSN')");
                return Command::FAILURE;
            }

            $version = $clam->version();
        } catch (\Exception $ex) {
            $output->writeln("[-] Exception: {$ex->getMessage()}");
            $output->writeln("[-] Failed to setup ClamAV client!");
            return Command::FAILURE;
        }

        $output->writeln("[+] Successfully setup ClamAV client");
        $output->writeln("[+] ClamAV version: {$version}");

        // Get the order for this scan (only ASC and DESC allowed)
        $order = $input->getArgument('order');
        if (empty($order) || !in_array(\strtoupper($order), ['ASC', 'DESC'])) {
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
                ['scan_status' => [WorkshopScanStatus::NOT_SCANNED_YET]],
                ['created_timestamp' => $order]
            );
        } elseif ($target === 'scanned') {

            $files = $this->em->getRepository(WorkshopFile::class)->findBy(
                ['scan_status' => [WorkshopScanStatus::SCANNED]],
                ['created_timestamp' => $order]
            );
        } else {

            $ids = [];

            foreach (explode(',', $target) as $id) {

                if (!\is_numeric($id)) {
                    $output->writeln("[-] Invalid ID or target to scan: {$id}");
                    return Command::FAILURE;
                }

                $id = (int) $id;

                if (in_array($id, $ids)) {
                    $output->writeln("[?] Duplicate ID to scan: {$id} -> ignoring");
                    continue;
                }

                $ids[] = $id;
            }

            $files = $this->em->getRepository(WorkshopFile::class)->findBy(
                ['item' => $ids],
                ['created_timestamp' => $order]
            );
        }

        if (!$files || \count($files) === 0) {
            $output->writeln("[?] No files found to scan");
            $output->writeln("[>] Done!");
            return Command::SUCCESS;
        }

        /** @var WorkshopFile $file */
        foreach ($files as $file) {
            try {

                $storage_filename = $file->getStorageFilename();
                $filename         = $file->getFilename();
                $item_id          = $file->getItem()->getId();
                $item_name        = $file->getItem()->getName();

                $output->writeln("[>] Scanning: <comment>{$filename}</comment> [<info>{$item_name}</info>]");

                $path = $storage_dir . '/' . $item_id . '/files/' . $storage_filename;
                $output->writeln("\t[>] File: <info>{$path}</info>");

                // Update scan status
                $file->setScanStatus(WorkshopScanStatus::SCANNING);
                $this->em->flush();

                // Make sure file exists
                if (!\file_exists($path) || !\is_readable($path)) {
                    $output->writeln("\t[-] <error>File does not exist or is not accessible</error>");
                    $file->setScanStatus(WorkshopScanStatus::INVALID);
                    $this->em->flush();
                    continue;
                }

                // Scan file
                // When using the default docker compose file we can scan the path directly
                // because the volume is mapped exactly like it is in PHP.
                $result = $clam->fileScan($path);

                // Virus found !!
                if ($result === false) {

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
                        $output->writeln("\t[+] File removed!");
                    }

                    // Notify admins
                    $this->nc->sendNotificationToAllWithRole(
                        UserRole::Admin,
                        VirusRemovedNotification::class,
                        ['filename' => $filename, 'item_id' => $item_id, 'item_name' => $item_name]
                    );
                    continue;
                }

                // Update scan status
                $file->setScanStatus(WorkshopScanStatus::SCANNED);
                $this->em->flush();

                // Yay!
                $output->writeln("\t[+] <question>No malware found</question>");
            } catch (\Exception $ex) {

                $output->writeln("\t[-] Something went wrong");

                // Reset scan status
                $file->setScanStatus(WorkshopScanStatus::NOT_SCANNED_YET);
                $this->em->flush();

                $output->writeln("\t[>] Scan status reset");
            }
        }

        $output->writeln("[>] Done!");
        return Command::SUCCESS;
    }
}
