<?php

namespace App\Console\Command\KeeperFX;

use App\Enum\ReleaseType;

use App\Entity\GithubAlphaBuild;

use App\Config\Config;
use App\DiscordNotifier;
use App\GameFileHandler;
use App\VirusTotalScanner;

use DateTime;
use Doctrine\ORM\EntityManager;
use ByteUnits\Binary as BinaryFormatter;
use wapmorgan\UnifiedArchive\UnifiedArchive;
use wapmorgan\UnifiedArchive\Drivers\Basic\BasicDriver;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\DirectoryHelper;

use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;
use wapmorgan\UnifiedArchive\Exceptions\EmptyFileListException;

class FetchAlphaCommand extends Command
{
    public const IS_ENABLED = true;

    public const GITHUB_WORKFLOW_RUNS_URL = 'https://api.github.com/repos/dkfans/keeperfx/actions/runs';

    private array $version_regex = [
        '/^keeperfx\-(\d+\_\d+\_\d+\_\d+)\_Alpha\-patch$/'
    ];

    public function __construct(
        private EntityManager $em,
        private DiscordNotifier $discord_notifier,
        private GameFileHandler $game_file_handler,
    ) {
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
        if (
            !isset($_ENV['APP_GITHUB_API_AUTH_TOKEN'])
            || empty($_ENV['APP_GITHUB_API_AUTH_TOKEN'])
        ) {
            $output->writeln("[-] Github token not set");
            $output->writeln("[>] ENV VAR: 'APP_GITHUB_API_AUTH_TOKEN'");
            return Command::FAILURE;
        }

        // Make sure an output directory is set
        $storage_dir = Config::get('storage.path.alpha-patch');
        if ($storage_dir === null) {
            $output->writeln("[-] Alpha build download directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_ALPHA_PATCH_STORAGE_CLI_PATH' or 'APP_ALPHA_PATCH_STORAGE'");
            return Command::FAILURE;
        }

        // Create output directory if it does not exist
        if (!\is_dir($storage_dir)) {
            if (!@\mkdir($storage_dir)) {
                $output->writeln("[-] Failed to create alpha build download directory");
                $output->writeln("[>] DIR: {$storage_dir}");
                return Command::FAILURE;
            }
        }

        $output->writeln("[>] Download directory: <info>{$storage_dir}</info>");

        $workflow_id = \intval($_ENV['APP_ALPHA_PATCH_GITHUB_WORKFLOW_ID'] ?? 0);

        $output->writeln("[>] Grabbing latest workflow runs...");
        $output->writeln("[>] " . self::GITHUB_WORKFLOW_RUNS_URL);

        // Create API client
        $client = new \GuzzleHttp\Client([
            'verify' => false, // Don't verify SSL connection
            'headers' => [
                'Accept'               => 'application/vnd.github+json',
                'Authorization'        => 'Bearer ' . $_ENV['APP_GITHUB_API_AUTH_TOKEN'],
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
        ]);

        // Grab Github workflow runs
        $res = $client->request('GET', self::GITHUB_WORKFLOW_RUNS_URL);
        $json = \json_decode($res->getBody());
        if (!$json || empty($json->workflow_runs)) {
            $output->writeln("[-] Failed to fetch workflow runs");
            return Command::FAILURE;
        }

        // Get runs and order them from old to newer
        // This makes sure they get added in chronological order
        $runs = \array_reverse((array) $json->workflow_runs);
        $output->writeln("[+] Grabbed " . \count($runs) . " runs");

        // Loop trough all fetched workflow runs
        foreach ($runs as $run) {

            // Make sure this run is a successful alpha build
            if (
                $run->status      !== 'completed' ||
                $run->conclusion  !== 'success' ||
                $run->workflow_id !== $workflow_id
            ) {
                continue;
            }

            // Make sure this run has artifacts
            if (empty($run->artifacts_url)) {
                continue;
            }

            // Grab artifacts
            $res = $client->request('GET', $run->artifacts_url);
            $json = \json_decode($res->getBody());
            if (!$json || empty($json->artifacts)) {
                continue;
            }

            // Handle specific artifact in workflow run
            // Fallback to first artifact
            $artifact_index = (int) ($_ENV['APP_ALPHA_PATCH_GITHUB_WORKFLOW_ARTIFACT_INDEX'] ?? 0);
            $artifact = $json->artifacts[!empty($json->artifacts[$artifact_index]) ? $artifact_index : 0];

            // Get artifact download URL
            $dl_url = $artifact->archive_download_url ?? null;
            if (!\is_string($dl_url) || !\filter_var($dl_url, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Check if artifact is already downloaded
            $db_build = $this->em->getRepository(GithubAlphaBuild::class)->findOneBy(['artifact_id' => $artifact->id]);
            if ($db_build) {
                continue;
            }

            $output->writeln("[>] New artifact found: {$run->id} -> {$artifact->id}");

            // Create filename and output path
            $exp          = \explode('/', $artifact->archive_download_url);
            $filetype     = \end($exp);
            $new_filename = \preg_replace('/\-signed$/', '', $artifact->name) . '.7z';
            $output_path  = $storage_dir . '/' . $new_filename;

            // Create temp filename and paths for extraction and repackage process
            $temp_archive_path     = \sys_get_temp_dir() . '/' . $artifact->name . '.' . $filetype;
            $temp_archive_path_new = \sys_get_temp_dir() . '/' . $artifact->name . '-new.7z';
            $temp_archive_dir      = \sys_get_temp_dir() . '/' . $artifact->name;

            // Remove possible existing temp archive
            if (\file_exists($temp_archive_path) && \unlink($temp_archive_path) == false) {
                $output->writeln("[-] Temporary file already exists and can not be deleted: {$temp_archive_path}");
                $output->writeln("[>] Skipping this release because the process is probably still busy...");
                continue;
            }

            // Remove possible existing new temp archive
            if (\file_exists($temp_archive_path_new) && \unlink($temp_archive_path_new) == false) {
                $output->writeln("[-] Temporary file already exists and can not be deleted: {$temp_archive_path_new}");
                $output->writeln("[>] Skipping this release because the process is probably still busy...");
                continue;
            }

            // Remove possible existing temp dir
            if (\file_exists($temp_archive_dir) && DirectoryHelper::delete($temp_archive_dir) == false) {
                $output->writeln("[-] Temporary dir already exists and can not be deleted: {$temp_archive_dir}");
                $output->writeln("[>] Skipping this release because the process is probably still busy...");
                continue;
            }

            // Download alpha build
            try {

                $output->writeln("[>] Downloading: {$artifact->name} -> <info>{$temp_archive_path}</info>");
                $client->request('GET', $artifact->archive_download_url, ['sink' => $temp_archive_path]);
                if (!\file_exists($temp_archive_path)) {
                    $output->writeln("[-] Failed to download artifact");
                    return Command::FAILURE;
                } else {
                    $output->writeln("[+] Downloaded artifact!");
                }

                // Open the archive
                $temp_archive = UnifiedArchive::open($temp_archive_path);
                if ($temp_archive === null) {
                    $output->writeln("[-] Failed to open the archive");
                    return Command::FAILURE;
                }

                // Extract the files
                $output->writeln("[>] Extracting...");
                try {
                    $temp_archive->extract($temp_archive_dir);
                } catch (EmptyFileListException $ex) {
                    $output->writeln("[-] No files in archive");
                    return Command::FAILURE;
                } catch (ArchiveExtractionException $ex) {
                    $output->writeln("[-] Archive Extraction Exception: " . $ex->getMessage());
                    return Command::FAILURE;
                }

                // Add bundle files
                $bundled_files_added = 0;
                $bundle_path = Config::get('storage.path.alpha-patch-file-bundle');
                $output->writeln("[>] Adding file bundle...");
                if ($bundle_path === null || !\is_dir($bundle_path)) {
                    $output->writeln("[-] File bundle path is not a dir");
                    $output->writeln("[>] ENV VAR: 'APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE_CLI_PATH'");
                    return Command::FAILURE;
                } else {
                    $dir_iterator = new \RecursiveDirectoryIterator($bundle_path, \RecursiveDirectoryIterator::SKIP_DOTS);
                    $iterator     = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($iterator as $item) {
                        if ($item->isDir()) {
                            $item_dir_path = $temp_archive_dir . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                            if (!\file_exists($item_dir_path) && !\is_dir($item_dir_path)) {
                                \mkdir($item_dir_path);
                            }
                        } else {
                            $item_filepath = $temp_archive_dir . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                            if (\copy($item, $item_filepath) === true) {
                                $bundled_files_added++;
                            } else {
                                throw new \Exception("failed to copy bundled alpha patch file");
                            }
                        }
                    }

                    $output->writeln("[+] Copied <info>{$bundled_files_added}</info> files from alpha patch bundle");
                }

                // Rename the default 'keeperfx.cfg' file
                $cfg_filepath     = $temp_archive_dir . '/keeperfx.cfg';
                $cfg_filepath_new = $temp_archive_dir . '/_keeperfx.cfg';
                if (\file_exists($cfg_filepath)) {
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
                if (\file_exists($output_path)) {
                    $output->writeln("[>] '{$output_path}' already exists?");
                    $output->writeln("[>] Removing file...");
                    \unlink($output_path);
                }

                // Move new archive
                $output->writeln("[>] Moving new archive to: <info>{$output_path}</info>");
                if (\rename($temp_archive_path_new, $output_path) === false) {
                    throw new \Exception("failed to move file");
                }

                // Change file permissions
                /*$output->writeln("[>] Changing file permissions...");
                if(\chmod($output_path, 0777) === false){
                    throw new \Exception("failed to change file permissions");
                }*/

                // Get filesize
                $output_filesize = \filesize($output_path);
            } catch (\Exception $ex) {

                $output->writeln("[-] <error>Something went wrong</error>");

                // Cleanup if something went wrong
                $output->writeln("[>] Removing created files and directory...");
                if (\file_exists($temp_archive_path)) {
                    \unlink($temp_archive_path);
                }
                if (\file_exists($temp_archive_path_new)) {
                    \unlink($temp_archive_path_new);
                }
                if (\file_exists($temp_archive_dir)) {
                    DirectoryHelper::delete($temp_archive_dir);
                }

                return Command::FAILURE;
            }

            // Fix display title
            $display_title = $run->display_title;
            $display_title = \preg_replace('~(\s\(\#\d)\…$~', '…', $display_title);

            // Strip '-signed' from signed artifact names
            $build_name = \preg_replace('/\-signed$/', '', $artifact->name);

            // Get version
            $version = null;
            foreach ($this->version_regex as $regex) {
                if (\preg_match($regex, $build_name, $matches)) {
                    $version = \str_replace('_', '.', $matches[1]);
                    break;
                }
            }

            // Store game files
            $output->writeln("[>] Storing game files for version {$version}");
            $game_files_store_result = $this->game_file_handler->storeVersionFromPath(ReleaseType::ALPHA, $version, $temp_archive_dir);
            if (!$game_files_store_result) {
                $output->writeln("[-] Failed to move game files");
                return Command::FAILURE;
            }
            $output->writeln("[+] {$game_files_store_result} game files stored");

            // Create entity
            $build = new GithubAlphaBuild();
            $build->setName($build_name);
            $build->setArtifactId($artifact->id);
            $build->setFilename($new_filename);
            $build->setSizeInBytes($output_filesize);
            $build->setTimestamp(new DateTime($artifact->created_at));
            $build->setWorkflowTitle($display_title);
            $build->setWorkflowRunId($artifact->workflow_run?->id ?? null);
            $build->setIsAvailable(self::IS_ENABLED);
            $build->setVersion($version);

            // Save to DB
            $this->em->persist($build);
            $this->em->flush();

            // Show success message
            $output->writeln("[+] <info>{$artifact->name}</info> stored! -> <info>{$display_title}</info>");
            $output->writeln("[+] Output filesize: " . BinaryFormatter::bytes($output_filesize)->format());

            // Send a notification on Discord
            if (self::IS_ENABLED) {
                if ($this->discord_notifier->notifyNewAlphaPatch($build)) {
                    $output->writeln("[+] Discord has been notified!");
                }
            }

            // Scan with VirusTotal
            // We do this so many Antivirus companies get a sample as soon as possible.
            // This hopefully accomplishes a few things:
            // - A possible virus that slipped in will get easily found and flagged
            // - Antivirus companies will get a sample that they can whitelist
            if (!empty($_ENV['APP_VIRUSTOTAL_API_KEY'])) {
                $output->writeln("[+] Sending file to VirusTotal...");
                $resp = VirusTotalScanner::scanFile($output_path);
            }

            // Remove temp files and dir
            $output->writeln("[>] Removing temporary files and dir...");
            DirectoryHelper::delete($temp_archive_dir);
            \unlink($temp_archive_path);
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
