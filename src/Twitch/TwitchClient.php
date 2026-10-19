<?php

namespace App\Twitch;

use App\Twitch\Enum\TwitchGame;

class TwitchClient
{
    public static function getStreams(TwitchGame $game = TwitchGame::DUNGEON_KEEPER_1): array
    {
        // Get Twitch API client credentials
        $client_id     = $_ENV['APP_OAUTH_TWITCH_CLIENT_ID']     ?? '';
        $client_secret = $_ENV['APP_OAUTH_TWITCH_CLIENT_SECRET'] ?? '';

        // Create a HTTP client
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.twitch.tv/']);

        // Get OAuth authorization token
        $auth_response = $client->post('https://id.twitch.tv/oauth2/token', [
            'form_params' => [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'client_credentials',
            ],
        ]);

        // Get OAuth App Access Token
        $auth_data    = \json_decode($auth_response->getBody(), true);
        $access_token = $auth_data['access_token'];

        // Fetch all streams
        // Use pagination to go beyond 100
        $streams = [];
        $cursor  = null;
        do {

            $query = [
                'game_id' => (string) $game->value,
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

        return $streams;
    }
}
