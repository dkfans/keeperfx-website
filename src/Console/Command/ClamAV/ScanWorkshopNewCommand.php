<?php

namespace App\Console\Command\ClamAV;

use App\Enum\WorkshopScanStatus;

use App\Entity\WorkshopFile;

use Appwrite\ClamAV\Pipe;
use Appwrite\ClamAV\Network;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\StringHelper;

class ScanWorkshopNewCommand extends Command
{
    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("clamav:scan-workshop-new")
            ->setDescription("Use ClamAV to scan new workshop files.");
    }

    protected function execute(Input $input, Output $output)
    {
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

        $output->writeln("[>] Checking for new files to be scanned...");

        // Find an un-scanned file
        $file = $this->em->getRepository(WorkshopFile::class)->findOneBy(['scan_status' => WorkshopScanStatus::NOT_SCANNED_YET]);
        if(!$file){
            $output->writeln("[>] No files waiting to be scanned");
            $output->writeln("[>] Done!");
            return Command::SUCCESS;
        }

        try {

            $output->writeln("[>] Scanning: <comment>{$file->getFilename()}</comment> [<info>{$file->getItem()->getName()}</info>]");

            $path = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $file->getItem()->getId() . '/files/' . $file->getStorageFilename();
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

        // Done
        $output->writeln("[>] Done!");
        return Command::SUCCESS;
    }
}
