<?php

namespace App\Console\Command\ClamAV;

use App\Enum\WorkshopScanStatus;

use App\Entity\WorkshopFile;

use App\Config\Config;
use Appwrite\ClamAV\Pipe;
use Appwrite\ClamAV\Network;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\StringHelper;

class ScanWorkshopAllCommand extends Command
{
    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("clamav:scan-workshop-all")
            ->setDescription("Use ClamAV to scan all workshop files.");
    }

    protected function execute(Input $input, Output $output)
    {
        // Define workshop storage dir
        $storage_dir = Config::get('storage.path.workshop');
        if($storage_dir === null) {
            $output->writeln("[-] Workshop storage directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_WORKSHOP_STORAGE_CLI_PATH' or 'APP_WORKSHOP_STORAGE'");
            return Command::FAILURE;
        }

        $output->writeln("[>] Setting up ClamAV client...");

        // Setup client
        try {

            $dsn = $_ENV['APP_CLAMAV_DSN'] ?? null;

            if(!\is_string($dsn)){
                $output->writeln("[-] Invalid ClamAV DSN string ('APP_CLAMAV_DSN')");
                return Command::FAILURE;
            }

            if(StringHelper::startsWith($dsn, 'unix://')){
                $clam = new Pipe(StringHelper::subtract($dsn, \strlen('unix://')));
            } elseif(StringHelper::startsWith($dsn, 'tcp://')){
                $exp = \explode(':', StringHelper::subtract($dsn, \strlen('tcp://')));
                if(\count($exp) !== 2){
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
            $output->writeln("[-] Failed to setup ClamAV client!");
            return Command::FAILURE;
        }

        $output->writeln("[+] Successfully setup ClamAV client");
        $output->writeln("[+] ClamAV version: {$version}");

        // Get all files to scan
        $files = $this->em->getRepository(WorkshopFile::class)->findBy(
            ['scan_status' => [WorkshopScanStatus::NOT_SCANNED_YET, WorkshopScanStatus::SCANNED]],
            ['created_timestamp' => 'DESC']
        );
        if(!$files || \count($files) === 0){
            $output->writeln("[?] No files found to scan");
            $output->writeln("[>] Done!");
            return Command::SUCCESS;
        }

        foreach($files as $file)
        {
            try {

                $output->writeln("[>] Scanning: <comment>{$file->getFilename()}</comment> [<info>{$file->getItem()->getName()}</info>]");

                $path = $storage_dir. '/' . $file->getItem()->getId() . '/files/' . $file->getStorageFilename();
                $output->writeln("[>] File: <info>{$path}</info>");

                // Update scan status
                $file->setScanStatus(WorkshopScanStatus::SCANNING);
                $this->em->flush();

                // Make sure file exists
                if(!\file_exists($path) || !\is_readable($path)){
                    $output->writeln("[-] File does not exist or is not accessible");
                    return Command::FAILURE;
                }

                // Scan file
                $result = $clam->fileScanInStream($path);

                // Virus found !!
                if($result === false){

                    $output->writeln("[!] <error>Malware found!</error>");

                    // Remove from DB
                    $this->em->remove($file);
                    $this->em->flush();
                    $output->writeln("[+] Removed from DB!");

                    // Remove FILE
                    @\unlink($path);

                    // Make sure file is removed
                    if(!\file_exists($path) || !\is_readable($path)){
                        $output->writeln("[-] Failed to remove file...");
                    } else {
                        $output->writeln("[+] File removed!");
                    }

                    // TODO: do some reporting (send mail to admin)

                    $output->writeln("[>] Done!");
                    return Command::SUCCESS;
                }

                // Update scan status
                $file->setScanStatus(WorkshopScanStatus::SCANNED);
                $this->em->flush();

                // Yay!
                $output->writeln("[+] <question>No malware found</question>");

            } catch (\Exception $ex) {

                $output->writeln("[-] Something went wrong");

                // Reset scan status
                $file->setScanStatus(WorkshopScanStatus::NOT_SCANNED_YET);
                $this->em->flush();

                $output->writeln("[>] Scan status reset");
            }
        }

        $output->writeln("[>] Done!");
        return Command::SUCCESS;
    }
}
