<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\GithubPrototype;

use App\Config\Config;
use App\DiscordNotifier;
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

class FetchPrototypeCommand extends Command
{
    public const IS_ENABLED = true;

    public const GITHUB_WORKFLOW_RUNS_URL = 'https://api.github.com/repos/dkfans/keeperfx/actions/runs';

    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-prototype")
            ->setDescription("Fetch the latest github prototypes");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest prototypes...");

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
        $storage_dir = Config::get('storage.path.prototype');
        if ($storage_dir === null) {
            $output->writeln("[-] Prototype download directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_PROTOTYPE_STORAGE_CLI_PATH' or 'APP_PROTOTYPE_STORAGE'");
            return Command::FAILURE;
        }

        // Create output directory if it does not exist
        if (!\is_dir($storage_dir)) {
            if (!@\mkdir($storage_dir)) {
                $output->writeln("[-] Failed to create prototype download directory");
                $output->writeln("[>] DIR: {$storage_dir}");
                return Command::FAILURE;
            }
        }

        $output->writeln("[>] Download directory: <info>{$storage_dir}</info>");

        $workflow_id = \intval($_ENV['APP_PROTOTYPE_GITHUB_WORKFLOW_ID'] ?? 0);

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

        // If you want to start with old builds first you can use array reverse here
        // But we probably want the latest prototype first
        $runs = (array) $json->workflow_runs;
        $output->writeln("[+] Grabbed " . \count($runs) . " runs");

        // Loop trough all fetched workflow runs
        foreach ($runs as $run) {

            // Make sure this run is a successful prototype build
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

            // Only handle first artifact in workflow run
            $artifact = $json->artifacts[0];

            // Get artifact download URL
            $dl_url = $artifact->archive_download_url ?? null;
            if (!\is_string($dl_url) || !\filter_var($dl_url, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Check if artifact is already downloaded
            $db_build = $this->em->getRepository(GithubPrototype::class)->findOneBy(['artifact_id' => $artifact->id]);
            if ($db_build) {
                continue;
            }

            $output->writeln("[>] New artifact found for run {$run->id}: Artifact ID {$artifact->id}");

            // Create filename and output path
            // Also add a random string to the new filename so the download URL can not be guessed
            $exp          = \explode('/', $artifact->archive_download_url);
            $filetype     = \end($exp);
            $new_filename = $artifact->name . '-' . \substr(sha1(time() . $artifact->name), 0, 8) . '.7z';
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

            // Download prototype
            try {

                $output->writeln("[>] Downloading: {$artifact->name} -> <info>{$temp_archive_path}</info>");
                $client->request('GET', $artifact->archive_download_url, ['sink' => $temp_archive_path]);
                if (!\file_exists($temp_archive_path)) {
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
                $bundle_path = Config::get('storage.path.prototype-file-bundle');
                $output->writeln("[>] Adding file bundle...");
                if ($bundle_path === null || !\is_dir($bundle_path)) {
                    $output->writeln("[-] File bundle path is not set or not a dir");
                    $output->writeln("[>] ENV VAR: 'APP_PROTOTYPE_FILE_BUNDLE_STORAGE_CLI_PATH'");
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
                            \copy($item, $item_filepath);
                        }
                    }
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
                \rename($temp_archive_path_new, $output_path);

                // Get filesize
                $output_filesize = \filesize($output_path);
            } catch (\Exception $ex) {

                $output->writeln("[-] <error>Something went wrong</error>...");
                $output->writeln("[-] <error>{$ex->getMessage()}</error>...");

                dd($ex);

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

            // Remove temp files and dir
            $output->writeln("[>] Removing temporary files and dir...");
            DirectoryHelper::delete($temp_archive_dir);
            \unlink($temp_archive_path);

            // Fix display title
            $display_title = $run->display_title;
            $display_title = \preg_replace('~(\s\(\#\d)\…$~', '…', $display_title);

            // Add to database
            $build = new GithubPrototype();
            $build->setName($artifact->name);
            $build->setArtifactId($artifact->id);
            $build->setFilename($new_filename);
            $build->setSizeInBytes($output_filesize);
            $build->setTimestamp(new DateTime($artifact->created_at));
            $build->setWorkflowTitle($display_title);
            $build->setWorkflowRunId($artifact->workflow_run?->id ?? null);
            $build->setIsAvailable(self::IS_ENABLED);
            $this->em->persist($build);
            $this->em->flush();

            // Show success message
            $output->writeln("[+] <info>{$artifact->name}</info> stored! -> <info>{$display_title}</info>");
            $output->writeln("[+] Output filesize: " . BinaryFormatter::bytes($output_filesize)->format());

            // Scan with VirusTotal
            // We do this so many Antivirus companies get a sample as soon as possible.
            // This hopefully accomplishes a few things:
            // - A possible virus that slipped in will get easily found and flagged
            // - Antivirus companies will get a sample that they can whitelist
            if (!empty($_ENV['APP_VIRUSTOTAL_API_KEY'])) {
                $output->writeln("[+] Sending file to VirusTotal...");
                $resp = VirusTotalScanner::scanFile($output_path);
            }
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
