<?php

namespace App\Console\Command\KeeperFX;

use App\SpamDetector;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DomCrawler\Crawler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\StringHelper;

class FetchForumActivityCommand extends Command
{
    public const MAX_THREAD_COUNT = 5;

    public const FORUM_URL = 'https://keeperklan.com/forums/52-KeeperFX';

    public const THREAD_URL_BASE = 'https://keeperklan.com/';

    public function __construct(
        private CacheInterface $cache,
        private SpamDetector $spam_detector,
    ) {
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

        try {

            // Make GET request
            $res = $client->request('GET', self::FORUM_URL);

        } catch (\Exception $ex) {

            if($ex->getCode() == 403){
                $output->writeln("[-] 403 Forbidden");
            } elseif($ex->getCode() == 404) {
                $output->writeln("[-] 404 Not found");
            } else {
                $output->writeln("[-] Unknown problem");
            }

            return Command::FAILURE;
        }

        $content = $res->getBody();
        if(!$content){
            $output->writeln("[-] Failed to grab content");
            return Command::FAILURE;
        }

        $crawler = new Crawler((string)$content);

        $found_threads = $crawler->filter('#threads .threadbit:not(.moved)')->each(function (Crawler $node, $i) {

            // Get amount of replies
            $replies_str = $node->filter('.threadstats li')->first()->text();
            $replies     = \preg_replace('/[^0-9]/', '', $replies_str ?? '');

            // Get URL but remove any query parameters
            $url = self::THREAD_URL_BASE . $node->filter('h3 a')->first()->attr('href');
            if(StringHelper::contains($url, '?')){
                $url = \explode('?', $url)[0];
            }

            // Store into the array
            return [
                'title'    => $node->filter('.title')->text(),
                'date_str' => $node->filter('.threadlastpost dd')->last()->text(),
                'replies'  => $replies,
                'url'      => $url,
            ];
        });

        $found_thread_count = \count($found_threads);
        if($found_thread_count === 0){
            $output->writeln("[-] No threads found");
            $this->cache->delete('keeperfx_forum_threads');
            return Command::FAILURE;
        }

        $output->writeln("[+] Grabbed {$found_thread_count} threads");

        // Make sure threads are not spam
        $threads = [];
        foreach($found_threads as $thread){

            // Don't cache more than 5 threads
            if(\count($threads) == self::MAX_THREAD_COUNT){
                break;
            }

            // Check title for spam or emojis
            $title = $thread['title'];
            if(empty($title) || $this->spam_detector->detectSpam($title) || $this->spam_detector->detectEmojis($title)){
                continue;
            }

            // Cache
            $threads[] = $thread;
        }

        $this->cache->set('keeperfx_forum_threads', $threads);

        $output->writeln("[+] Stored " . \count($threads) . " threads into cache");
        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
