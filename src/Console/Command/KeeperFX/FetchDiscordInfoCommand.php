<?php

namespace App\Console\Command\KeeperFX;

use App\SpamDetector;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DomCrawler\Crawler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use Xenokore\Utility\Helper\StringHelper;

class FetchDiscordInfoCommand extends Command
{
    public const REQUIRED_API_KEYS = [
        'approximate_member_count',
        'approximate_presence_count',
    ];

    public function __construct(
        private CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:fetch-discord-info")
            ->setDescription("Fetch the info about the Discord server");
    }

    protected function execute(Input $input, Output $output)
    {
        // Get Discord server ID
        $discord_id = $_ENV['APP_DISCORD_INVITE_ID'] ?? null;
        if($discord_id === null){
            $output->writeln("[] 'APP_DISCORD_INVITE_ID' environment variable not set");
            return Command::FAILURE;
        }

        $output->writeln("[>] Fetching Discord info: {$discord_id}");

        // Create API URL
        // This needs to include the counts
        $api_url = "https://discord.com/api/v9/invites/{$discord_id}?with_counts=true&with_expiration=true";

        // HTTP client
        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        $res = $client->request('GET', $api_url);

        // Make sure the request returned a body
        $content = $res->getBody();
        if(!$content){
            $output->writeln("[-] Failed to grab content");
            return Command::FAILURE;
        }

        // Decode JSON
        $json = \json_decode($res->getBody(), true);
        if(!$json){
            $output->writeln("[-] Failed to decode JSON response");
            return Command::FAILURE;
        }

        // Make sure the required array keys are found
        foreach(self::REQUIRED_API_KEYS as $key){
            if(\array_key_exists($key, $json) == false){
                $output->writeln("[-] JSON key not found: {$key}");
                return Command::FAILURE;
            }
        }

        // Save info to cache
        $this->cache->set('discord_info', $json);

        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
