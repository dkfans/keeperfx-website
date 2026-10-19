<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\UserOAuthToken;
use App\Enum\OAuthProviderType;
use App\Twitch\Enum\TwitchGame;
use App\Twitch\TwitchClient;
use Doctrine\ORM\EntityManager;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'kfx:fetch-homepage-twitch-streams', description: 'Fetch and handle Twitch streams to broadcast on homepage')]
class FetchHomepageTwitchStreamsCommand extends Command
{
    public const PRIORITY_LANGUAGE = 'en';

    public const KEEPER_FX_STRINGS = [
        'keeperfx',
        'keeper-fx',
        'keeper fx',
        'kfx',
        'dungeonkeeperfx',
    ];

    public function __construct(
        private EntityManager $em,
        private CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function execute(Input $input, Output $output): int
    {
        // Get Dungeon Keeper Twitch streams
        try {
            $output->writeln('[+] Grabbing Dungeon Keeper Twitch streams...');
            $streams = TwitchClient::getStreams(TwitchGame::DUNGEON_KEEPER_1);

        } catch (\Exception $ex) {

            $output->writeln('[-] Something went wrong');
            $output->writeln('[-] API error: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        // Make sure we fetched some streams
        if (\count($streams) > 0) {

            $output->writeln('[+] Grabbed <comment>' . \count($streams) . '</comment> total streams');

            // Variables
            $priority_user_found         = false;
            $priority_language_found     = false;
            $prioritized_twitch_user_ids = [];
            $stream_removed_count        = 0;

            // Grab Twitch user ids of our registered users
            // We'll use the OAuth tokens as these contain the Twitch account connections
            /** @var UserOAuthToken[] $oauth_tokens */
            $oauth_tokens = $this->em->getRepository(UserOAuthToken::class)->findBy(['provider_type' => OAuthProviderType::Twitch]);
            foreach ($oauth_tokens as $oauth_token) {
                $prioritized_twitch_user_ids[] = $oauth_token->getUid();
            }
            $output->writeln('[+] <comment>' . \count($prioritized_twitch_user_ids) . '</comment> priority users in our database');

            $output->writeln('[>] Filtering streams...');

            // Filter out non KeeperFX streams
            foreach ($streams as $key => $stream) {

                $has_keeperfx_string = false;

                // Gather strings to search trough
                $strings   = (array) ($stream['tags'] ?? []);
                $strings[] = (string) ($stream['title'] ?? '');

                // Check if "KeeperFX" string is found in title or tags
                foreach ($strings as $string) {
                    foreach (self::KEEPER_FX_STRINGS as $string_search) {
                        if (\str_contains(\strtolower($string), \strtolower($string_search)) === true) {
                            $has_keeperfx_string = true;
                            break 2;
                        }
                    }
                }

                // Remove the stream if it doesn't mention keeperfx
                if ($has_keeperfx_string === false) {
                    unset($streams[$key]);
                    ++$stream_removed_count;
                }
            }

            // See if we found a prioritized user
            if (\count($prioritized_twitch_user_ids) > 0) {
                foreach ($streams as $stream) {
                    if (\in_array($stream['user_id'], $prioritized_twitch_user_ids)) {
                        $output->writeln('[+] Priority user found!');
                        $priority_user_found = true;
                        break;
                    }
                }
            }

            // Remove non priority user streams
            if ($priority_user_found) {
                foreach ($streams as $key => $stream) {
                    if (\in_array($stream['user_id'], $prioritized_twitch_user_ids) === false) {
                        unset($streams[$key]);
                        ++$stream_removed_count;
                    }
                }
            }

            // See if we found our prioritized language
            foreach ($streams as $stream) {
                if (\strtolower($stream['language']) === \strtolower(self::PRIORITY_LANGUAGE)) {
                    $priority_language_found = true;
                    $output->writeln('[+] Priority language found!');
                    break;
                }
            }

            // If our priority language is found we will remove all the other ones
            if ($priority_language_found) {
                foreach ($streams as $key => $stream) {
                    if (\strtolower($stream['language']) !== \strtolower(self::PRIORITY_LANGUAGE)) {
                        unset($streams[$key]);
                        ++$stream_removed_count;
                    }
                }
            }

            // Show that we removed some streams
            if ($stream_removed_count > 0) {
                $output->writeln("[+] Removed <comment>{$stream_removed_count}</comment> streams");
            }

        }

        // Store into cache
        $output->writeln('[>] Storing <info>' . \count($streams) . '</info> streams into cache...');
        $this->cache->set('twitch_streams', $streams);

        $output->writeln('[+] Done!');

        return Command::SUCCESS;
    }
}
