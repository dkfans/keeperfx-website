<?php

namespace App\Console\Command\KeeperFX;

use App\Enum\OAuthProviderType;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;
use App\Entity\UserOAuthToken;
use Doctrine\ORM\EntityManager;
use App\OAuth\OAuthProviderService;
use Psr\SimpleCache\CacheInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

use TwitchApi\TwitchApi;
use TwitchApi\HelixGuzzleClient;
use League\OAuth2\Client\Token\AccessToken;

class HandleTwitchStreamsCommand extends Command
{
    public const DUNGEON_KEEPER_GAME_ID = '16169'; // string

    public const KEEPER_FX_STRINGS = [
        'keeperfx',
        'keeper-fx',
        'keeper fx',
        'kfx',
    ];

    private EntityManager $em;

    private CacheInterface $cache;

    private OAuthProviderService $provider_service;

    public function __construct(EntityManager $em, CacheInterface $cache, OAuthProviderService $provider_service) {
        $this->em               = $em;
        $this->cache            = $cache;
        $this->provider_service = $provider_service;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:handle-twitch-streams")
            ->setDescription("Fetch and handle Twitch streams to broadcast on homepage");
    }

    protected function execute(Input $input, Output $output)
    {
        $streams = [];
        $output->writeln("[>] Handling Twitch streams...");

        // We'll use the OAuth tokens as these contain the Twitch account connections
        /** @var UserOAuthToken[] $oauth_tokens */
        $oauth_tokens = $this->em->getRepository(UserOAuthToken::class)->findBy(['provider_type' => OAuthProviderType::Twitch]);

        if(!$oauth_tokens || \count($oauth_tokens) < 1){
            $output->writeln("[+] No OAuth tokens found");
            return Command::SUCCESS;
        }

        // Get API client
        $output->writeln("[>] Setting up Twitch API client...");
        $client = new HelixGuzzleClient($_ENV['APP_OAUTH_TWITCH_CLIENT_ID'], ['verify' => false]);
        $api    = new TwitchApi($client, $_ENV['APP_OAUTH_TWITCH_CLIENT_ID'], $_ENV['APP_OAUTH_TWITCH_CLIENT_SECRET']);

        // Setup OAuth provider (for refreshing tokens)
        $output->writeln("[>] Setting up Twitch OAuth Provider client...");
        $provider = $this->provider_service->getProvider(OAuthProviderType::Twitch);

        $oauth_token_count = \count($oauth_tokens);
        $output->writeln("[>] Checking <info>{$oauth_token_count}</info> OAuth tokens...");

        foreach($oauth_tokens as $token){

            if($token->getToken() === null || $token->getRefreshToken() === null || $token->getExpiresTimestamp() === null){
                $output->writeln("[-] Not checking invalidated token: {$token->getUser()->getUsername()}");
                continue;
            }

            // Refresh expired OAuth Token
            if($token->getExpiresTimestamp()->getTimestamp() < \time()){

                $output->writeln("[>] Refreshing token of user: {$token->getUser()->getUsername()}");

                try {
                    // Get new OAuth Token from provider
                    $new_access_token = $provider->getAccessToken('refresh_token', [
                        'refresh_token' => $token->getRefreshToken()
                    ]);
                } catch (\Exception $ex) {

                    // Invalidate OAuth token in DB
                    $token->setToken(null);
                    $token->setRefreshToken(null);
                    $token->setExpiresTimestamp(null);

                    // Flush changes to DB
                    $this->em->flush();

                    $output->writeln("[!] Token invalidated: {$token->getUser()->getUsername()}");
                    continue;
                }

                // Update OAuth Token in DB
                $token->setToken($new_access_token->getToken());
                $token->setRefreshToken($new_access_token->getRefreshToken());
                $token->setExpiresTimestamp(
                    \DateTime::createFromFormat('U', (int) $new_access_token->getExpires())
                );
                $this->em->persist($token);
                $this->em->flush();
            }

            // Check valid response
            $response = $api->getStreamsApi()->getStreamForUserId($token->getToken(), $token->getUid());
            if(!$response || $response->getStatusCode() !== 200){
                continue;
            }

            // Check valid response body
            $response_content = \json_decode($response->getBody()->getContents());
            if(!isset($response_content->data) || !isset($response_content->data[0])){
                continue;
            }

            // Get stream data
            $data = $response_content->data[0];

            // Check if streaming
            if(empty($data) || !isset($data->game_id)){
                $output->writeln("[-] {$token->getUser()->getUsername()} is not streaming");
                continue;
            }

            // Check if Dungeon Keeper
            if($data->game_id != self::DUNGEON_KEEPER_GAME_ID){
                $output->writeln("[-] {$token->getUser()->getUsername()} is streaming, but not Dungeon Keeper");
                continue;
            }

            // Gather strings to search trough
            $strings   = (array) ($data->tags ?? []);
            $strings[] = (string) ($data->title ?? '');

            // Check if "KeeperFX" string is found in title or tags
            $string_found = false;
            foreach($strings as $string){
                foreach(self::KEEPER_FX_STRINGS as $string_search){
                    if(\strpos(\strtolower($string), $string_search) !== false){
                        $string_found = true;
                        break 2;
                    }
                }
            }

            // Remember stream if string is found
            if($string_found){
                $output->writeln("[+] <info>{$token->getUser()->getUsername()}</info> is streaming KeeperFX!");
                $streams[] = $data->user_login;
                continue;
            }

            $output->writeln("[-] {$token->getUser()->getUsername()} is streaming Dungeon Keeper, but there is no mention of KeeperFX...");
        }

        // Show stream count message
        $stream_count = \count($streams);
        if($stream_count > 1){
            $output->writeln("[+] Found <info>{$stream_count }</info> streams!");
        } elseif($stream_count === 1){
            $output->writeln("[+] Found <info>1</info> stream!");
        } else {
            $output->writeln("[+] Found 0 streams...");
        }

        // Store into cache
        $output->writeln("[>] Storing into Cache...");
        $this->cache->set('twitch_streams', $streams);

        // Done!
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
