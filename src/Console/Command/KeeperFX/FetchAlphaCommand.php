<?php

namespace App\Console\Command\KeeperFX;

use DateTime;
use App\Entity\GithubAlphaBuild;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

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
            !isset($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOADER_TOKEN'])
            || empty($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOADER_TOKEN'])
        ){
            $output->writeln("[-] Github token not set");
            $output->writeln("[>] ENV VAR: 'KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOADER_TOKEN'");
            return Command::FAILURE;
        }

        // Make sure an output directory is set
        if(
            !isset($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH'])
            || empty($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH'])
        ){
            $output->writeln("[-] Alpha build download directory is not set");
            $output->writeln("[>] ENV VAR: 'KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH'");
            return Command::FAILURE;
        }

        // Create output directory if it does not exist
        if(!\is_dir($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH'])){
            if(!\mkdir($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH'])){
                $output->writeln("[-] Failed to create alpha build download directory");
                return Command::FAILURE;
            }
        }

        $output->writeln("[>] Download directory: " . $_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH']);

        $workflow_id = \intval($_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_WORKFLOW_ID'] ?? 0);

        $output->writeln("[>] Grabbing latest workflow runs...");
        $output->writeln("[>] " . self::GITHUB_WORKFLOW_RUNS_URL);

        // Create API client
        $client = new \GuzzleHttp\Client([
            'verify' => false, // Don't verify SSL connection
            'headers' => [
                'Accept'               => 'application/vnd.github+json',
                'Authorization'        => 'Bearer ' . $_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOADER_TOKEN'],
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
            $exp         = \explode('/', $artifact->archive_download_url);
            $filetype    = \end($exp);
            $filename    = $artifact->name . '.' . $filetype;
            $output_path = $_ENV['KEEPERFX_GITHUB_ALPHA_BUILD_DOWNLOAD_PATH'] . '/' . $filename;

            // Remove file if already exists
            if(\file_exists($output_path)){
                $output->writeln("[>] '{$output_path}' already exists?");
                $output->writeln("[>] Removing file...");
                \unlink($output_path);
            }

            // Download alpha build
            $output->writeln("[>] Downloading: {$filename}");
            $client->request('GET', $artifact->archive_download_url, ['sink' => $output_path]);

            if(!\file_exists($output_path)){
                $output->writeln("[-] Failed to download artifact");
                return Command::FAILURE;
            } else {
                $output->writeln("[+] Downloaded artifact!");
            }

            // Add to database
            $build = new GithubAlphaBuild();
            $build->setName($artifact->name);
            $build->setArtifactId($artifact->id);
            $build->setFilename($filename);
            $build->setSizeInBytes(\filesize($output_path));
            $build->setTimestamp(new DateTime($artifact->created_at));
            $build->setWorkflowTitle($run->display_title);
            $build->setIsAvailable(true);

            $this->em->persist($build);
            $this->em->flush();

            $output->writeln("[+] {$artifact->name} stored!");
        }

        return Command::SUCCESS;
    }
}
