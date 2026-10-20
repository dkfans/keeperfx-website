<?php

namespace App\Console\Command\KeeperFX;

use App\DiscordNotifier;
use App\Twitch\Enum\TwitchGame;
use App\Twitch\TwitchClient;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[AsCommand(
    name: 'kfx:monitor-and-post-twitch-streams',
    description: 'Monitor active Twitch streams and post new ones to Discord'
)]
class MonitorAndPostTwitchStreamsCommand extends Command
{
    // Define the Twitch games you want to monitor
    private const array MONITORED_GAMES = [
        TwitchGame::DUNGEON_KEEPER_1,
        TwitchGame::DUNGEON_KEEPER_2,
        TwitchGame::WAR_FOR_THE_OVERWORLD,
    ];

    // Cache key constants
    private const string CACHE_WARMUP_KEY = 'twitch_stream_notify_initialized';
    private const string CACHE_KEY_PREFIX = 'twitch_stream_notify_';

    // Grace period in seconds for stream crash/reconnect protection (e.g. 15 minutes)
    private const int CRASH_GRACE_PERIOD_SECONDS = 900;

    // Cache TTL (24 hours)
    private const int CACHE_TTL_SECONDS = 86400;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DiscordNotifier $discord_notifier,
    ) {
        parent::__construct();
    }

    protected function execute(Input $input, Output $output): int
    {
        // Make sure a webhook is configured
        if (empty($_ENV['APP_DISCORD_NOTIFY_STREAMS_WEBHOOK_URL'])) {
            $output->writeln('[-] No Streams webhook configured (APP_DISCORD_NOTIFY_STREAMS_WEBHOOK_URL)');

            return Command::FAILURE;
        }

        // Start
        $output->writeln('[>] Starting Twitch stream monitor...');
        $all_streams = [];

        // Fetch streams for all configured games
        $output->writeln('[>] Fetching streams...');
        foreach (self::MONITORED_GAMES as $game) {
            try {
                $streams      = TwitchClient::getStreams($game);
                $stream_count = \count($streams);

                $game_name = \ucwords(\strtolower(\str_replace('_', ' ', $game->name)));

                $output->writeln(\sprintf('[+] Found %d stream(s) for %s', $stream_count, $game_name));

                $all_streams = \array_merge($all_streams, $streams);

            } catch (\Throwable $ex) {
                $output->writeln('[-] Something went wrong while fetching streams for ' . $game->name);
                $output->writeln('[-] API error: ' . $ex->getMessage());

                return Command::FAILURE;
            }
        }

        $total_streams = \count($all_streams);
        $output->writeln(\sprintf('[+] Total streams found across all games: %d', $total_streams));

        if ($total_streams === 0) {
            $output->writeln('[+] No active streams right now. Exiting.');

            return Command::SUCCESS;
        }

        // Check if this is the first run (Warm-up phase)
        $is_warmed_up = $this->cache->has(self::CACHE_WARMUP_KEY);

        if (!$is_warmed_up) {
            $output->writeln('[!] First run detected. Warming up cache without sending notifications...');

            foreach ($all_streams as $stream) {
                $streamer_name = $stream['user_name'] ?? 'Unknown';
                $output->writeln(\sprintf('[>] Adding %s to initial cache...', $streamer_name));
                $this->markStreamAsNotified($stream);
            }

            // Mark warmup as completed
            $this->cache->set(self::CACHE_WARMUP_KEY, true);
            $output->writeln('[+] Cache warmup complete. Exiting.');

            return Command::SUCCESS;
        }

        $output->writeln('[>] Checking streams against cache...');

        // Process streams and notify on new streams
        foreach ($all_streams as $stream) {
            $stream_id    = (string) ($stream['id'] ?? '');
            $user_id      = (string) ($stream['user_id'] ?? '');
            $user_name    = $stream['user_name']    ?? 'Unknown';
            $game_name    = $stream['game_name']    ?? 'Unknown Game';
            $viewer_count = $stream['viewer_count'] ?? 0;
            $started_at   = (string) ($stream['started_at'] ?? '');

            if (empty($stream_id) || empty($user_id)) {
                $output->writeln(\sprintf('[-] Skipping %s: Missing stream_id or user_id in API payload.', $user_name));
                continue;
            }

            // Check direct stream ID match
            $stream_cache_key = self::CACHE_KEY_PREFIX . 'stream_' . $stream_id;

            if ($this->cache->has($stream_cache_key)) {
                $output->writeln(\sprintf('[+] Already notified for stream ID %s (%s). Skipping.', $stream_id, $user_name));
                continue;
            }

            // Smart Crash/Reconnect Check
            $user_cache_key = self::CACHE_KEY_PREFIX . 'user_' . $user_id;
            $user_data      = $this->cache->get($user_cache_key);

            if (\is_array($user_data)) {
                $last_started_at  = $user_data['started_at']  ?? null;
                $last_notified_at = $user_data['notified_at'] ?? 0;

                $is_same_start       = ($last_started_at === $started_at);
                $time_since_notified = \time() - $last_notified_at;
                $is_recent_reconnect = $time_since_notified < self::CRASH_GRACE_PERIOD_SECONDS;

                if ($is_same_start || $is_recent_reconnect) {
                    $reason = $is_same_start ? 'same start time' : \sprintf('recent reconnect (%ds ago)', $time_since_notified);

                    $output->writeln(\sprintf('[>] %s reconnected or recovered from crash (Reason: %s). Skipping.', $user_name, $reason));

                    // Update cache for the new stream ID so we don't process it again
                    $this->cache->set($stream_cache_key, true, self::CACHE_TTL_SECONDS);
                    continue;
                }
            }

            // Notify Discord for new stream
            $output->writeln(\sprintf('[+] New valid stream detected: %s streaming %s (%d viewers)', $user_name, $game_name, $viewer_count));
            $output->writeln(\sprintf('[>] Sending Discord webhook for %s...', $user_name));

            $sent = $this->discord_notifier->notifyTwitchStream($stream);

            if ($sent) {
                $this->markStreamAsNotified($stream);
                $output->writeln(\sprintf('[+] Success! Notification sent for %s.', $user_name));
            } else {
                $output->writeln(\sprintf('[-] Failed to send Discord notification for %s.', $user_name));
            }
        }

        $output->writeln('[+] Monitor check finished.');

        return Command::SUCCESS;
    }

    /**
     * Cache both stream ID and user streaming state.
     */
    private function markStreamAsNotified(array $stream): void
    {
        $stream_id  = (string) ($stream['id'] ?? '');
        $user_id    = (string) ($stream['user_id'] ?? '');
        $started_at = (string) ($stream['started_at'] ?? '');

        if (!empty($stream_id)) {
            $this->cache->set(self::CACHE_KEY_PREFIX . 'stream_' . $stream_id, true, self::CACHE_TTL_SECONDS);
        }

        if (!empty($user_id)) {
            $this->cache->set(self::CACHE_KEY_PREFIX . 'user_' . $user_id, [
                'started_at'  => $started_at,
                'notified_at' => \time(),
            ], self::CACHE_TTL_SECONDS);
        }
    }
}
