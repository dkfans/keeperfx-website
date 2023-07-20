<?php

namespace App\Console\Command\Workshop;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface as Container;
use wapmorgan\UnifiedArchive\UnifiedArchive;
use wapmorgan\UnifiedArchive\Drivers\Basic\BasicDriver;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\DirectoryHelper;

class FetchUnearthCommand extends Command
{
    private const UNEARTH_WORKSHOP_ID = 1;

    private const UNEARTH_VERSION_REGEX = "v([0-9\.]+[a-z]*?)\.";

    /** @var EntityManager $em */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("workshop:fetch-unearth")
                ->setDescription("Fetch the latest version of Unearth");
    }

    private function getUnearthVersionFromString(string $string): string|false
    {
        if(\preg_match("~" . self::UNEARTH_VERSION_REGEX . "~", $string, $matches) !== 1){
            return false;
        }
        return $matches[1];
    }

    protected function execute(Input $input, Output $output)
    {
        // Get Unearth workshop item
        $workshop_item = $this->em->getRepository(WorkshopItem::class)->find(self::UNEARTH_WORKSHOP_ID);
        if(!$workshop_item){
            $output->writeln("[-] Failed to get workshop item");
            return Command::FAILURE;
        }

        // Get latest Unearth workshop file
        $workshop_file = $this->em->getRepository(WorkshopFile::class)->findOneBy(['item' => $workshop_item, 'weight' => 0]); // TODO: order by date get latest
        if(!$workshop_file){
            $output->writeln("[-] Failed to get workshop file");
            return Command::FAILURE;
        }

        // Get Unearth version
        $local_unearth_version = $this->getUnearthVersionFromString($workshop_file->getFilename());
        if(!$local_unearth_version){
            $output->writeln("[-] Failed to get Unearth version from workshop file");
            return Command::FAILURE;
        }
        $output->writeln("[+] Local Unearth version: {$local_unearth_version}");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Create HTTP client
        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        $output->writeln("[>] Fetching Unearth page...");

        // GET page
        $url = "https://rainlizard.itch.io/unearth";
        $response = $client->get($url);
        if($response->getStatusCode() !== 200){
            $output->writeln("[-] Failed to grab Unearth release page: {$url}");
            return Command::FAILURE;
        }

        // Get Windows download data
        if(\preg_match("~data\-upload\_id\=\"(\d+)\".+?(Unearth\sv\d+\.\d+\.zip)~", $response->getBody(), $matches) !== 1){
            $output->writeln("[-] Failed to get Unearth Windows download filename");
            return Command::FAILURE;
        }

        $windows_download_id       = $matches[1];
        $windows_download_filename = $matches[2];
        $output->writeln("[+] Windows download: {$windows_download_filename} (#{$windows_download_id})");

        // Get Linux download data
        if(\preg_match("~" . $windows_download_id . ".+?data\-upload\_id\=\"(\d+)\".+?(UnearthLinux\sv\d+\.\d+\.zip)~", $response->getBody(), $matches) !== 1){
            $output->writeln("[-] Failed to get Unearth Linux download filename");
            return Command::FAILURE;
        }

        $linux_download_id       = $matches[1];
        $linux_download_filename = $matches[2];
        $output->writeln("[+] Linux download: {$linux_download_filename} (#{$linux_download_id})");

        // Get CSRF token
        if(\preg_match("~csrf_token\"\svalue\=\"(.+?)\"~", $response->getBody(), $matches) !== 1){
            $output->writeln("[-] Failed to get CSRF token");
            return Command::FAILURE;
        }

        $csrf_token = $matches[1];
        $output->writeln("[+] CSRF token: {$csrf_token}");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Get remote Unearth version
        $remote_unearth_version = $this->getUnearthVersionFromString($windows_download_filename);
        if(!$remote_unearth_version){
            $output->writeln("[-] Failed to figure out new Unearth version");
            return Command::FAILURE;
        }
        $output->writeln("[+] Remote Unearth version: {$remote_unearth_version}");

        // Check if we need to update
        if($remote_unearth_version === $local_unearth_version){
            $output->writeln("[+] Already at latest version!");
            return Command::SUCCESS;
        }

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Get download URLs
        $windows_download_url = $this->getDownloadURL($windows_download_id, $client, $csrf_token);
        $linux_download_url   = $this->getDownloadURL($linux_download_id, $client, $csrf_token);

        // Define local download paths
        $windows_temp_archive_filename = \str_replace(" ", "_", $windows_download_filename);
        $linux_temp_archive_filename = \str_replace(" ", "_", $linux_download_filename);
        $windows_temp_archive_path = \sys_get_temp_dir() . '/' . time() . '-' . $windows_temp_archive_filename;
        $linux_temp_archive_path   = \sys_get_temp_dir() . '/' . time() . '-' . $linux_temp_archive_filename;

        // Download Windows release
        $output->writeln("[>] Downloading Windows release...");
        $client->get($windows_download_url, ['sink' => $windows_temp_archive_path]);
        if(!file_exists($windows_temp_archive_path)){
            $output->writeln("[-] Failed to download Windows release");
            return Command::FAILURE;
        }
        $windows_temp_archive_filesize = \filesize($windows_temp_archive_path);
        $windows_temp_archive_filesize_mb = \round($windows_temp_archive_filesize / (1024 * 1024), 2);
        $output->writeln("[+] Windows release downloaded! {$windows_temp_archive_path} ({$windows_temp_archive_filesize_mb} MiB)");

        // Download Linux release
        $output->writeln("[>] Downloading Linux release...");
        $client->get($linux_download_url, ['sink' => $linux_temp_archive_path]);
        if(!file_exists($linux_temp_archive_path)){
            $output->writeln("[-] Failed to download Linux release");
            return Command::FAILURE;
        }
        $linux_temp_archive_filesize = \filesize($linux_temp_archive_path);
        $linux_temp_archive_filesize_mb = \round($linux_temp_archive_filesize / (1024 * 1024), 2);
        $output->writeln("[+] Linux release downloaded! {$linux_temp_archive_path} ({$linux_temp_archive_filesize_mb} MiB)");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Extract Windows release
        $windows_temp_archive_dir = $windows_temp_archive_path . '_extract';
        $output->writeln("[>] Extracting Windows release...");
        $temp_archive = UnifiedArchive::open($windows_temp_archive_path);
        $temp_archive->extract($windows_temp_archive_dir);
        $output->writeln("[+] Extracted! {$windows_temp_archive_dir}");

        // Extract Linux release
        $linux_temp_archive_dir = $linux_temp_archive_path . '_extract';
        $output->writeln("[>] Extracting Linux release...");
        $temp_archive = UnifiedArchive::open($linux_temp_archive_path);
        $temp_archive->extract($linux_temp_archive_dir);
        $output->writeln("[+] Extracted! {$linux_temp_archive_dir}");

        // Clean up downloaded archives
        @unlink($windows_temp_archive_path);
        @unlink($linux_temp_archive_path);

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Re-archive Windows release
        $windows_new_archive_filename = \pathinfo($windows_temp_archive_filename, PATHINFO_FILENAME) . ".7z";
        $windows_new_archive_path = \sys_get_temp_dir() . '/' . time() . '-' . $windows_new_archive_filename;
        $output->writeln("[>] Creating new Windows archive...");
        try {
            UnifiedArchive::create(['' => $windows_temp_archive_dir], $windows_new_archive_path, BasicDriver::COMPRESSION_STRONG);
            $windows_new_archive_filesize = \filesize($windows_new_archive_path);
            $windows_new_archive_filesize_mb = \round($windows_new_archive_filesize / (1024 * 1024), 2);
            $output->writeln("[+] Archive created: <info>{$windows_new_archive_path}</info> ({$windows_new_archive_filesize_mb} MiB)");
        } catch (\Exception $ex) {
            // throw $ex;
            $output->writeln("[-] Failed to create Windows archive");
            return Command::FAILURE;
        }

        // Re-archive Linux release
        $linux_new_archive_filename = \pathinfo($linux_temp_archive_filename, PATHINFO_FILENAME) . ".7z";
        $linux_new_archive_path = \sys_get_temp_dir() . '/' . time() . '-' . $linux_new_archive_filename;
        $output->writeln("[>] Creating new Linux archive...");
        try {
            UnifiedArchive::create(['' => $linux_temp_archive_dir], $linux_new_archive_path, BasicDriver::COMPRESSION_STRONG);
            $linux_new_archive_filesize = \filesize($linux_new_archive_path);
            $linux_new_archive_filesize_mb = \round($linux_new_archive_filesize / (1024 * 1024), 2);
            $output->writeln("[+] Archive created: <info>{$linux_new_archive_path}</info> ({$linux_new_archive_filesize_mb} MiB)");
        } catch (\Exception $ex) {
            // throw $ex;
            $output->writeln("[-] Failed to create Linux archive");
            return Command::FAILURE;
        }

        // Clean up extracted dirs
        DirectoryHelper::delete($windows_temp_archive_dir);
        DirectoryHelper::delete($linux_temp_archive_dir);

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        $workshop_files_dir = $_ENV['APP_WORKSHOP_STORAGE_CLI_PATH'] . '/' . $workshop_item->getId() . '/files';

        // Remove already existing workshop files
        $workshop_file = null;
        $workshop_files = $this->em->getRepository(WorkshopFile::class)->findBy(['item' => $workshop_item]);
        foreach($workshop_files as $workshop_file){
            $file_path = $workshop_files_dir . '/' . $workshop_file->getStorageFilename();
            if(\file_exists($file_path)){
                if(@unlink($file_path)){
                    $output->writeln("[+] Existing file removed: {$file_path}");
                }
            }
            $this->em->remove($workshop_file);
        }

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Move Windows file
        $windows_new_archive_storage_path = $workshop_files_dir . '/' . $windows_new_archive_filename;
        if(!\rename($windows_new_archive_path, $windows_new_archive_storage_path)){
            $output->writeln("[-] Failed to move Windows release to workshop 'files' dir: {$windows_new_archive_storage_path}");
            $output->writeln("[-] This might be a permission error");
            return Command::FAILURE;
        }
        $output->writeln("[+] Windows release stored: {$windows_new_archive_storage_path}");

        // Create Windows workshop file entity
        $windows_file = new WorkshopFile();
        $windows_file->setItem($workshop_item);
        $windows_file->setFilename($windows_new_archive_filename);
        $windows_file->setStorageFilename($windows_new_archive_filename);
        $windows_file->setSize(\filesize($windows_new_archive_storage_path));
        $windows_file->setWeight(0);

        $this->em->persist($windows_file);

        // Move Linux file
        $linux_new_archive_storage_path = $workshop_files_dir . '/' . $linux_new_archive_filename;
        if(!\rename($linux_new_archive_path, $linux_new_archive_storage_path)){
            $output->writeln("[-] Failed to move Linux release to workshop 'files' dir: {$linux_new_archive_storage_path}");
            $output->writeln("[-] This might be a permission error");
            return Command::FAILURE;
        }
        $output->writeln("[+] Linux release stored: {$linux_new_archive_storage_path}");

        // Create Linux workshop file entity
        $linux_file = new WorkshopFile();
        $linux_file->setItem($workshop_item);
        $linux_file->setFilename($linux_new_archive_filename);
        $linux_file->setStorageFilename($linux_new_archive_filename);
        $linux_file->setSize(\filesize($linux_new_archive_storage_path));
        $linux_file->setWeight(1);

        $this->em->persist($linux_file);

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Save changes to DB
        $this->em->flush();
        $output->writeln("[+] Database flushed!");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }

    private function getDownloadURL(int $file_id, Client $client, string $csrf_token)
    {
        $response = $client->post("https://rainlizard.itch.io/unearth/file/" . $file_id . "?source=view_game&as_props=1&after_download_lightbox=true", [
            'csrf_token' => $csrf_token
        ]);
        if($response->getStatusCode() !== 200){
            return;
        }
        $json = \json_decode($response->getBody(), true);
        if(!isset($json['url'])){
            return;
        }

        return $json['url'];
    }

}
