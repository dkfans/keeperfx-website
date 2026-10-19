<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\UserOAuthToken;
use App\Enum\OAuthProviderType;
use Doctrine\ORM\EntityManager;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'kfx:handle-twitch-streams', description: 'Fetch and handle Twitch streams to broadcast on homepage')]
class HandleTwitchStreamsCommand extends Command
{
    public const DUNGEON_KEEPER_GAME_ID = 16169;

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
        // Get Twitch API client credentials
        $client_id     = $_ENV['APP_OAUTH_TWITCH_CLIENT_ID'];
        $client_secret = $_ENV['APP_OAUTH_TWITCH_CLIENT_SECRET'];

        // Create a HTTP client
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.twitch.tv/']);

        try {
            $output->writeln('[>] Authenticating with Twitch API...');

            // Get OAuth App Access Token
            $auth_response = $client->post('https://id.twitch.tv/oauth2/token', [
                'form_params' => [
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type'    => 'client_credentials',
                ],
            ]);
            $auth_data    = \json_decode($auth_response->getBody(), true);
            $access_token = $auth_data['access_token'];

            // Fetch all streams
            $output->writeln('[>] Grabbing streams...');
            $streams = [];

            // Use pagination to go beyond 100
            $cursor = null;
            do {

                $query = [
                    'game_id' => (string) self::DUNGEON_KEEPER_GAME_ID,
                    'first'   => 100,
                ];

                // If a cursor exists from the previous loop, attach it to fetch the next page
                if ($cursor) {
                    $query['after'] = $cursor;
                }

                // Get the streams
                $response = $client->get('helix/streams', [
                    'headers' => [
                        'Client-Id'     => $client_id,
                        'Authorization' => 'Bearer ' . $access_token,
                    ],
                    'query' => $query,
                ]);

                // Decode the data
                $data = \json_decode($response->getBody(), true);

                // Append the current page of streams to our main array
                if (!empty($data['data'])) {
                    \array_push($streams, ...$data['data']);
                }

                // Twitch returns an empty pagination object when we reach the end of the results
                $cursor = $data['pagination']['cursor'] ?? null;

            } while ($cursor !== null);

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {

            $output->writeln('[-] Something went wrong');
            $output->writeln('[-] API error: ' . $e->getMessage());

            // Clear the cache because if something went wrong we want to make sure we don't show anything
            $this->cache->set('twitch_streams', []);

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
