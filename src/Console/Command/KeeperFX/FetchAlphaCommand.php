<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\GithubAlphaBuild;

use DateTime;
use Doctrine\ORM\EntityManager;
use wapmorgan\UnifiedArchive\UnifiedArchive;
use wapmorgan\UnifiedArchive\Drivers\Basic\BasicDriver;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\DirectoryHelper;

class FetchAlphaCommand extends Command
{
    public const GITHUB_WORKFLOW_RUNS_URL = 'https://api.github.com/repos/dkfans/keeperfx/actions/runs';

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-alpha")
            ->setDescription("Fetch the latest alpha releases");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest alpha releases...");

        // Make sure a Github token is set
        if(
            !isset($_ENV['APP_ALPHA_PATCH_GITHUB_DOWNLOADER_AUTH_TOKEN'])
            || empty($_ENV['APP_ALPHA_PATCH_GITHUB_DOWNLOADER_AUTH_TOKEN'])
        ){
            $output->writeln("[-] Github token not set");
            $output->writeln("[>] ENV VAR: 'APP_ALPHA_PATCH_GITHUB_DOWNLOADER_AUTH_TOKEN'");
            return Command::FAILURE;
        }

        // Make sure an output directory is set
        if(!empty($_ENV['APP_ALPHA_PATCH_STORAGE_CLI_PATH'])){
            $storage_dir = $_ENV['APP_ALPHA_PATCH_STORAGE_CLI_PATH'];
        } elseif (!empty($_ENV['APP_ALPHA_PATCH_STORAGE'])){
            $storage_dir = $_ENV['APP_ALPHA_PATCH_STORAGE'];
        } else {
            $output->writeln("[-] Alpha build download directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_ALPHA_PATCH_STORAGE_CLI_PATH' or 'APP_ALPHA_PATCH_STORAGE'");
            return Command::FAILURE;
        }

        // Create output directory if it does not exist
        if(!\is_dir($storage_dir)){
            if(!@\mkdir($storage_dir)){
                $output->writeln("[-] Failed to create alpha build download directory");
                $output->writeln("[>] DIR: {$storage_dir}");
                return Command::FAILURE;
            }
        }

        $output->writeln("[>] Download directory: " . $storage_dir);

        $workflow_id = \intval($_ENV['APP_ALPHA_PATCH_GITHUB_WORKFLOW_ID'] ?? 0);

        $output->writeln("[>] Grabbing latest workflow runs...");
        $output->writeln("[>] " . self::GITHUB_WORKFLOW_RUNS_URL);

        // Create API client
        $client = new \GuzzleHttp\Client([
            'verify' => false, // Don't verify SSL connection
            'headers' => [
                'Accept'               => 'application/vnd.github+json',
                'Authorization'        => 'Bearer ' . $_ENV['APP_ALPHA_PATCH_GITHUB_DOWNLOADER_AUTH_TOKEN'],
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
        ]);

        // Grab Github workflow runs
        $res = $client->request('GET', self::GITHUB_WORKFLOW_RUNS_URL);
        $json = \json_decode($res->getBody());
        if(!$json || empty($json->workflow_runs)){
            $output->writeln("[-] Failed to fetch workflow runs");
            return Command::FAILURE;
        }

        $output->writeln("[+] Workflow runs found: " . \count($json->workflow_runs));

        // Loop trough all fetched workflow runs
        foreach($json->workflow_runs as $run){

            // Make sure this run is a successful alpha build
            if(
                $run->status      !== 'completed' ||
                $run->conclusion  !== 'success' ||
                $run->workflow_id !== $workflow_id
            ) {
                continue;
            }

            $output->writeln("[>] Checking run {$run->id}");

            if(empty($run->artifacts_url)){
                continue;
            }

            // Grab artifacts
            $res = $client->request('GET', $run->artifacts_url);
            $json = \json_decode($res->getBody());
            if(!$json || empty($json->artifacts)){
                continue;
            }

            // Only handle first artifact in workflow run
            $artifact = $json->artifacts[0];

            // Get artifact download URL
            $dl_url = $artifact->archive_download_url ?? null;
            if(!\is_string($dl_url) || !\filter_var($dl_url, FILTER_VALIDATE_URL)){
                continue;
            }

            // Check if artifact is already downloaded
            $db_build = $this->em->getRepository(GithubAlphaBuild::class)->findOneBy(['artifact_id' => $artifact->id]);
            if($db_build){
                $output->writeln("[>] Already downloaded and in database: {$artifact->id}");
                continue;
            }

            // Create filename and output path
            $exp          = \explode('/', $artifact->archive_download_url);
            $filetype     = \end($exp);
            $new_filename = $artifact->name . '.7z';
            $output_path  = $storage_dir . '/' . $new_filename;

            // Create temp filename and paths for extraction and repackage process
            $temp_archive_path     = \sys_get_temp_dir() . '/' . $artifact->name . '.' . $filetype;
            $temp_archive_path_new = \sys_get_temp_dir() . '/' . $artifact->name . '-new.7z';
            $temp_archive_dir      = \sys_get_temp_dir() . '/' . $artifact->name;

            // Make sure there isn't a download/archive process already executing
            if(\file_exists($temp_archive_path)){
                $output->writeln("[-] Temporary download file already exists: {$temp_archive_path}");
                $output->writeln("[>] Skipping...");
                continue;
            }
            if(\file_exists($temp_archive_path_new)){
                $output->writeln("[-] Temporary archive already exists: {$temp_archive_path_new}");
                $output->writeln("[>] Skipping...");
                continue;
            }
            if(\file_exists($temp_archive_dir)){
                $output->writeln("[-] Temporary archive dir already exists: {$temp_archive_dir}");
                $output->writeln("[>] Skipping...");
                continue;
            }

            // Download alpha build
            $output->writeln("[>] Downloading: {$artifact->name} -> {$temp_archive_path}");
            $client->request('GET', $artifact->archive_download_url, ['sink' => $temp_archive_path]);
            if(!\file_exists($temp_archive_path)){
                $output->writeln("[-] Failed to download artifact");
                return Command::FAILURE;
            } else {
                $output->writeln("[+] Downloaded artifact!");
            }

            // Extract the files
            $output->writeln("[>] Extracting...");
            $temp_archive = UnifiedArchive::open($temp_archive_path);
            $temp_archive->extract($temp_archive_dir);

            // Add bundle files
            if(!empty($_ENV['APP_ALPHA_PATCH_FILE_BUNDLE_CLI_PATH'])){
                $bundle_path = $_ENV['APP_ALPHA_PATCH_FILE_BUNDLE_CLI_PATH'];
                $output->writeln("[>] Adding file bundle...");
                if(!\is_dir($bundle_path)){
                    $output->writeln("[-] File bundle path is not a dir");
                    $output->writeln("[>] ENV VAR: 'APP_ALPHA_PATCH_FILE_BUNDLE_CLI_PATH'");
                    return Command::FAILURE;
                } else {
                    $dir_iterator = new \RecursiveDirectoryIterator($bundle_path, \RecursiveDirectoryIterator::SKIP_DOTS);
                    $iterator     = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($iterator as $item) {
                        if ($item->isDir()) {
                            $item_dir_path = $temp_archive_dir . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                            if(!\file_exists($item_dir_path) && !\is_dir($item_dir_path)){
                                \mkdir($item_dir_path);
                            }
                        } else {
                            $item_filepath = $temp_archive_dir . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                            \copy($item, $item_filepath);
                        }
                    }
                }
            }

            // Rename the default 'keeperfx.cfg' file
            $cfg_filepath     = $temp_archive_dir . '/keeperfx.cfg';
            $cfg_filepath_new = $temp_archive_dir . '/_keeperfx.cfg';
            if(\file_exists($cfg_filepath)){
                \rename($cfg_filepath, $cfg_filepath_new);
            }

            // Create new 7z archive
            $output->writeln("[>] Creating new 7z archive...");
            try {
                UnifiedArchive::create(['' => $temp_archive_dir], $temp_archive_path_new, BasicDriver::COMPRESSION_STRONG);
                $output->writeln("[+] Archive created: <info>{$temp_archive_path_new}</info>");
            } catch (\Exception $ex) {
                throw $ex;
            }

            // Remove output file if it already exists
            if(\file_exists($output_path)){
                $output->writeln("[>] '{$output_path}' already exists?");
                $output->writeln("[>] Removing file...");
                \unlink($output_path);
            }

            // Move new archive
            $output->writeln("[>] Moving new archive to: <info>{$output_path}</info>");
            \rename($temp_archive_path_new, $output_path);

            // Remove temp files and dir
            $output->writeln("[>] Removing temporary files and dir...");
            DirectoryHelper::delete($temp_archive_dir);
            \unlink($temp_archive_path);

            // Add to database
            $build = new GithubAlphaBuild();
            $build->setName($artifact->name);
            $build->setArtifactId($artifact->id);
            $build->setFilename($new_filename);
            $build->setSizeInBytes(\filesize($output_path));
            $build->setTimestamp(new DateTime($artifact->created_at));
            $build->setWorkflowTitle($run->display_title);
            $build->setIsAvailable(true);

            $this->em->persist($build);
            $this->em->flush();

            $output->writeln("[+] <info>{$artifact->name}</info> stored!");
        }

        return Command::SUCCESS;
    }
}
