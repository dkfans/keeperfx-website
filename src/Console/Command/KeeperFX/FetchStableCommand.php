<?php

namespace App\Console\Command\KeeperFX;

use App\Enum\ReleaseType;

use App\Entity\GithubRelease;

use App\DiscordNotifier;
use App\Entity\WorkshopItem;
use App\GameFileHandler;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\JsonHelper;
use Xenokore\Utility\Helper\DirectoryHelper;

use wapmorgan\UnifiedArchive\UnifiedArchive;
use wapmorgan\UnifiedArchive\Exceptions\EmptyFileListException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;

class FetchStableCommand extends Command
{
    public const GITHUB_RELEASE_URL = 'https://api.github.com/repos/dkfans/keeperfx/releases';

    private array $version_regex = [
        '/^KeeperFX (\d+\.\d+\.\d+)$/'
    ];

    private array $old_releases = [
        'KeeperFX 0.5.0b Build 3081' => '0.5.0.3081',
        'KeeperFX 0.5.0 Build 3080'  => '0.5.0.3080',
        'KeeperFX 0.4.9 Build 2762'  => '0.4.9.2762',
        'KeeperFX 0.4.8 Build 2154'  => '0.4.8.2154',
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
        $this->setName("kfx:fetch-stable")
            ->setDescription("Fetch the latest stable release");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest stable releases...");
        $output->writeln("[>] API Endpoint: " . self::GITHUB_RELEASE_URL);

        // Create HTTP client
        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        // Fetch releases
        $res = $client->request('GET', self::GITHUB_RELEASE_URL);
        $gh_releases = JsonHelper::decode($res->getBody());
        if (empty($gh_releases)) {
            $output->writeln("[-] Failed to fetch releases");
            return Command::FAILURE;
        }

        // Tell user how many releases were found
        $release_count = \count($gh_releases);
        $output->writeln("[>] Found {$release_count} releases...");

        // Remember if there was a new release
        $new_release = null;

        // Loop trough all fetched releases
        foreach ($gh_releases as $gh_release) {

            // Make sure github release data is valid
            if (empty($gh_release->tag_name) || empty($gh_release->assets) || empty($gh_release->assets[0]->browser_download_url)) {
                $output->writeln("[-] Invalid github release data...");
                continue;
            }

            // Get version
            $version = null;
            foreach ($this->version_regex as $regex) {
                if (\preg_match($regex, $gh_release->name, $matches)) {
                    $version = $matches[1];
                    break;
                }
            }

            // Make sure we can find a version in this release
            if ($version === null) {

                // Skip old versions (hardcoded)
                if (!empty($this->old_releases[$gh_release->name])) {
                    $version = $this->old_releases[$gh_release->name];
                } else {
                    $output->writeln("[-] Found no version for {$gh_release->name}");
                    continue;
                }
            }

            // Check if release already exists in DB
            $db_release = $this->em->getRepository(GithubRelease::class)->findOneBy(['version' => $version]);
            if ($db_release) {
                continue;
            }

            // Download the release archive
            $output->writeln("[>] Downloading {$version}...");
            try {

                // Variables for file download
                $exp               = \explode('/', $gh_release->assets[0]->browser_download_url);
                $filename          = \end($exp);
                $temp_archive_path = \sys_get_temp_dir() . '/' . $filename;
                $temp_archive_dir  = \sys_get_temp_dir() . '/' . $gh_release->name;

                // Make sure there isn't a download/archive process already executing
                if (
                    \file_exists($temp_archive_path)
                    || \file_exists($temp_archive_dir)
                ) {
                    $output->writeln("[-] One or more temporary files for this release already exist.");
                    $output->writeln("[>] Skipping this release because the process is probably still busy...");
                    continue;
                }

                $output->writeln("[>] Downloading: {$gh_release->name} -> <info>{$temp_archive_path}</info> ({$gh_release->assets[0]->size} bytes)");
                $client->request('GET', $gh_release->assets[0]->browser_download_url, ['sink' => $temp_archive_path]);
                if (!\file_exists($temp_archive_path)) {
                    $output->writeln("[-] Failed to download release");
                    return Command::FAILURE;
                } else {
                    $output->writeln("[+] Release downloaded!");
                }

                // Open the archive
                $temp_archive = UnifiedArchive::open($temp_archive_path);
                if ($temp_archive === null) {
                    $output->writeln("[-] Failed to open the archive");
                    return Command::FAILURE;
                }

                // Check if output directory exists
                if (!DirectoryHelper::isAccessible($temp_archive_dir)) {
                    DirectoryHelper::createIfNotExist($temp_archive_dir);
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

                // Move files with game file handler
                $game_files_store_result = $this->game_file_handler->storeVersionFromPath(ReleaseType::STABLE, $version, $temp_archive_dir);
                if (!$game_files_store_result) {
                    $output->writeln("[-] Failed to move game files");
                    return Command::FAILURE;
                }
                $output->writeln("[+] {$game_files_store_result} game files stored");
            } catch (\Exception $ex) {

                $output->writeln("[-] <error>Something went wrong</error>");

                // Cleanup if something went wrong
                $output->writeln("[>] Removing created files and directory...");
                if (\file_exists($temp_archive_path)) {
                    \unlink($temp_archive_path);
                }
                if (\file_exists($temp_archive_dir)) {
                    DirectoryHelper::delete($temp_archive_dir);
                }

                return Command::FAILURE;
            }

            // Create release
            $output->writeln("[>] Creating {$version} in database...");
            $github_release = new GithubRelease();
            $github_release->setTag($gh_release->tag_name);
            $github_release->setName($gh_release->name);
            $github_release->setTimestamp(new \DateTime($gh_release->published_at));
            $github_release->setDownloadUrl($gh_release->assets[0]->browser_download_url);
            $github_release->setSizeInBytes($gh_release->assets[0]->size);
            $github_release->setVersion($version);

            // Save changes to DB
            $output->writeln("[>] Saving release to database...");
            $this->em->persist($github_release);
            $this->em->flush();

            // Find all releases with same major.minor version excluding the newly added one
            $output->writeln("[>] Searching releases with same major.minor...");
            $query_builder = $this->em->createQueryBuilder();
            $same_major_minor_versions = $query_builder->select('release')
                ->from(GithubRelease::class, 'release')
                ->where($query_builder->expr()->like('release.version', ':prefix'))
                ->andWhere('release.id != :currentId')
                ->setParameter('prefix', $github_release->getVersionMajorMinor() . '.%')
                ->setParameter('currentId', $github_release->getId())
                ->getQuery()
                ->getResult();

            // Find workshop items with older minimum game versions that have same major.minor
            // If new version is 1.0.2 we will search for items with 1.0.1 and 1.0.0
            $output->writeln("[>] Searching workshop items with already existing major.minor...");
            $workshop_items_major_minor = $this->em->getRepository(WorkshopItem::class)->findBy(
                ['min_game_version' => \array_map(fn($entity) => $entity->getId(), $same_major_minor_versions)]
            );

            // Update workshop items with new version
            $output->writeln("[>] Updating workshop items with same major.minor to use latest patch as minimum game version...");
            /** @var WorkshopItem $workshop_item_major_minor $ */
            foreach ($workshop_items_major_minor as $workshop_item_major_minor) {
                $workshop_item_major_minor->setMinGameBuild($github_release->getId());
                $output->writeln("[>] Updating minimum game version for <info>{$workshop_item_major_minor->getName()}</info>...");
            }

            // Save changes to DB
            $output->writeln("[>] Saving changes to database...");
            $this->em->flush();

            // Remember latest new release
            if ($new_release === null || $github_release->getTimestamp() > $new_release->getTimestamp()) {
                $new_release = $github_release;
            }

            $output->writeln("[+] {$version} ADDED!");
        }

        // Update workshop items with a minimum game build set to alpha patch to the new stable version
        if ($new_release !== null) {
            $query_builder = $this->em->getConnection()->createQueryBuilder();
            $query_builder
                ->update('workshop_item')
                ->where('min_game_build = -1')
                ->set('min_game_build', $new_release->getId());
            $query_builder->executeQuery();

            // Send a notification on Discord
            $this->discord_notifier->notifyNewStableBuild($new_release);
        }

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
