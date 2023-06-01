<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\GithubRelease;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\JsonHelper;

class FetchStableCommand extends Command
{
    public const GITHUB_RELEASE_URL = 'https://api.github.com/repos/dkfans/keeperfx/releases';

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-stable")
            ->setDescription("Fetch the latest stable release");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest stable release...");
        $output->writeln("[>] " . self::GITHUB_RELEASE_URL);

        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        $res = $client->request('GET', self::GITHUB_RELEASE_URL);
        $gh_releases = JsonHelper::decode($res->getBody());

        if(empty($gh_releases)){
            $output->writeln("[-] Failed to fetch releases");
            return Command::FAILURE;
        }

        $new_release = null;

        foreach($gh_releases as $gh_release){

            // Make sure github release data is valid
            if(empty($gh_release->tag_name) || empty($gh_release->assets) || empty($gh_release->assets[0]->browser_download_url)){
                $output->writeln("[-] Invalid github release data...");
                continue;
            }

            $tag = (string) $gh_release->tag_name;
            $output->writeln("[>] Checking if exists locally: {$tag}");

            // Check if release already exists in DB
            $db_release = $this->em->getRepository(GithubRelease::class)->findOneBy(['tag' => $tag]);
            if($db_release){
                $output->writeln("[>] {$tag} already exists");
                continue;
            }

            // Add release to DB
            $github_release = new GithubRelease();
            $github_release->setTag($tag);
            $github_release->setName($gh_release->name);
            $github_release->setTimestamp(new \DateTime($gh_release->published_at));
            $github_release->setDownloadUrl($gh_release->assets[0]->browser_download_url);
            $github_release->setSizeInBytes($gh_release->assets[0]->size);
            $this->em->persist($github_release);
            $this->em->flush();

            // Remember latest new release
            if($new_release === null || $github_release->getTimestamp() > $new_release->getTimestamp()) {
                $new_release = $github_release;
            }

            $output->writeln("[+] {$tag} ADDED!");
        }

        // Update workshop items with a minimum game build set to alpha patch to the new stable version
        if($new_release !== null){
            $query_builder = $this->em->getConnection()->createQueryBuilder();
            $query_builder
                ->update('workshop_item')
                ->where('min_game_build = -1')
                ->set('min_game_build', $new_release->getId());
            $query_builder->executeQuery();
        }

        return Command::SUCCESS;
    }
}
