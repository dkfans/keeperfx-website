<?php

namespace App\Console\Command\KeeperFX;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DomCrawler\Crawler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class FetchForumActivityCommand extends Command
{
    public const FORUM_URL = 'https://keeperklan.com/forums/52-KeeperFX';

    public const THREAD_URL_BASE = 'https://keeperklan.com/';

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-forum-activity")
            ->setDescription("Fetch the latest KeeperFX forum threads from Keeper Klan");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching threads: " . self::FORUM_URL);

        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        $res = $client->request('GET', self::FORUM_URL);

        $content = $res->getBody();
        if(!$content){
            $output->writeln("[-] Failed to grab content");
            return Command::FAILURE;
        }

        $crawler = new Crawler((string)$content);

        $threads = $crawler->filter('#threads .threadbit:not(.moved)')->each(function (Crawler $node, $i) {
            $replies_str = $node->filter('.threadstats li')->first()->text();
            $replies     = \preg_replace('/[^0-9]/', '', $replies_str ?? '');
            return [
                'title'    => $node->filter('.title')->text(),
                'date_str' => $node->filter('.threadlastpost dd')->last()->text(),
                'replies'  => $replies,
                'url'      => self::THREAD_URL_BASE . $node->filter('h3 a')->first()->attr('href'),
            ];
        });

        if(\count($threads) < 5){
            $output->writeln("[-] Failed to grab at least 5 threads");
            return Command::FAILURE;
        }

        $output->writeln("[+] Grabbed " . \count($threads) . " threads");

        $this->cache->set('keeperfx_forum_threads', \array_slice($threads, 0, 5));

        $output->writeln("[+] Stored 5 threads into cache");
        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
