<?php

namespace App\Console\Command\Workshop;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;

use App\Config\Config;
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
use Xenokore\Utility\Helper\JsonHelper;
use Xenokore\Utility\Helper\StringHelper;

class FetchUnearthCommand extends Command
{
    private const UNEARTH_GITHUB_RELEASE_URL = 'https://api.github.com/repos/rainlizard/Unearth/releases';

    private const UNEARTH_WORKSHOP_ID = 1;

    private const UNEARTH_VERSION_REGEX_1 = "v([0-9\.]+[a-z]*?)\.";    // old way of versioning
    private const UNEARTH_VERSION_REGEX_2 = "unearth\-([0-9\.]*?)\-";
    private const UNEARTH_VERSION_REGEX_3 = "unearth\-[a-z]*?\-([0-9\.]*?)\.(?:zip|7z)";

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
        if(\preg_match("~" . self::UNEARTH_VERSION_REGEX_1 . "~", $string, $matches) === 1){
            return $matches[1];
        }

        if(\preg_match("~" . self::UNEARTH_VERSION_REGEX_2 . "~", $string, $matches) === 1){
            return $matches[1];
        }

        if(\preg_match("~" . self::UNEARTH_VERSION_REGEX_3 . "~", $string, $matches) === 1){
            return $matches[1];
        }

        return false;
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Checking if Unearth workshop item needs updating...");

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
        $output->writeln("[+] Local Unearth version: <info>{$local_unearth_version}</info>");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        $res = $client->request('GET', self::UNEARTH_GITHUB_RELEASE_URL);
        $gh_releases = JsonHelper::decode($res->getBody());

        if(empty($gh_releases)){
            $output->writeln("[-] Failed to fetch releases");
            return Command::FAILURE;
        }

        $release = $gh_releases[0];

        // Make sure github release data is valid
        if(empty($release->tag_name) || empty($release->assets) || empty($release->assets[0]->browser_download_url)){
            $output->writeln("[-] Invalid github release data...");
            return Command::FAILURE;
        }

        $version = (string) $release->tag_name;

        if($version === $local_unearth_version){
            $output->writeln("[+] Already latest Unearth version!");
            return Command::SUCCESS;
        }

        $output->writeln("[+] New version found: <info>{$version}</info>");


        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        $linux_download_url = null;
        $linux_download_filename = null;
        $windows_download_url = null;
        $windows_download_filename = null;

        foreach($release->assets as $asset){
            if(StringHelper::contains(\strtolower($asset->name), 'linux')){
                $linux_download_url      = $asset->browser_download_url;
                $linux_download_filename = $asset->name;
            }
            if(StringHelper::contains(\strtolower($asset->name), 'window')){
                $windows_download_url      = $asset->browser_download_url;
                $windows_download_filename = $asset->name;
            }
        }

        if( $linux_download_url == null || $windows_download_url == null ||
            $linux_download_filename == null || $windows_download_filename == null){

            $output->writeln("[-] Failed to get required download data");
            return Command::FAILURE;
        }

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

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
        $output->writeln("[+] Windows release downloaded! <info>{$windows_temp_archive_path}</info> ({$windows_temp_archive_filesize_mb} MiB)");

        // Download Linux release
        $output->writeln("[>] Downloading Linux release...");
        $client->get($linux_download_url, ['sink' => $linux_temp_archive_path]);
        if(!file_exists($linux_temp_archive_path)){
            $output->writeln("[-] Failed to download Linux release");
            return Command::FAILURE;
        }
        $linux_temp_archive_filesize = \filesize($linux_temp_archive_path);
        $linux_temp_archive_filesize_mb = \round($linux_temp_archive_filesize / (1024 * 1024), 2);
        $output->writeln("[+] Linux release downloaded! <info>{$linux_temp_archive_path}</info> ({$linux_temp_archive_filesize_mb} MiB)");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Extract Windows release
        $windows_temp_archive_dir = $windows_temp_archive_path . '_extract';
        $output->writeln("[>] Extracting Windows release...");
        $temp_archive = UnifiedArchive::open($windows_temp_archive_path);
        $temp_archive->extract($windows_temp_archive_dir);
        $output->writeln("[+] Extracted! <info>{$windows_temp_archive_dir}</info>");

        // Extract Linux release
        $linux_temp_archive_dir = $linux_temp_archive_path . '_extract';
        $output->writeln("[>] Extracting Linux release...");
        $temp_archive = UnifiedArchive::open($linux_temp_archive_path);
        $temp_archive->extract($linux_temp_archive_dir);
        $output->writeln("[+] Extracted! <info>{$linux_temp_archive_dir}</info>");

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

        $workshop_files_dir = Config::get('storage.path.workshop') . '/' . $workshop_item->getId() . '/files';

        // Remove already existing workshop files
        $workshop_file = null;
        $workshop_files = $this->em->getRepository(WorkshopFile::class)->findBy(['item' => $workshop_item]);
        foreach($workshop_files as $workshop_file){
            $file_path = $workshop_files_dir . '/' . $workshop_file->getStorageFilename();
            if(\file_exists($file_path)){
                if(@unlink($file_path)){
                    $output->writeln("[+] Existing file removed: <info>{$file_path}</info>");
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
        $output->writeln("[+] Windows release stored: <info>{$windows_new_archive_storage_path}</info>");

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
        $output->writeln("[+] Linux release stored: <info>{$linux_new_archive_storage_path}</info>");

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

        // Update last updated date on the workshop item
        $workshop_item->setUpdatedTimestamp(new \DateTime("now"));

        // Save changes to DB
        $this->em->flush();
        $output->writeln("[+] Database updated!");

        ////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////

        // Success
        $output->writeln("[+] Successfully updated Unearth v{$local_unearth_version} to v{$version}!");
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
