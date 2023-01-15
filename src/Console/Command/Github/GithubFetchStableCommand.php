<?php

namespace App\Console\Command\Github;


use App\Entity\GithubRelease;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\JsonHelper;

class GithubFetchStableCommand extends Command
{
    public const GITHUB_RELEASE_URL = 'https://api.github.com/repos/dkfans/keeperfx/releases';

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("github:fetch-stable")
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

        foreach($gh_releases as $gh_release){

            $tag = (string) $gh_release->tag_name;
            $output->writeln("[>] Checking if exists locally: {$tag}");

            $db_release = $this->em->getRepository(GithubRelease::class)->findOneBy(['tag' => $tag]);
            if($db_release){
                continue;
            }

            if(empty($gh_release->assets) || empty($gh_release->assets[0]->browser_download_url)){
                continue;
            }

            $release = new GithubRelease();
            $release->setTag($tag);
            $release->setName($gh_release->name);
            $release->setTimestamp(new \DateTime($gh_release->published_at));
            $release->setDownloadUrl($gh_release->assets[0]->browser_download_url);
            $release->setSizeInBytes($gh_release->assets[0]->size);

            $this->em->persist($release);
            $this->em->flush();

            $output->writeln("[+] {$tag} ADDED!");
        }

        return Command::SUCCESS;
    }
}
