<?php

namespace App;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client;

class VirusTotalScanner {

    public const API_SCAN_FILES_ENDPOINT     = 'https://www.virustotal.com/api/v3/files';
    public const API_ADD_COMMENT_ENDPOINT    = 'https://www.virustotal.com/api/v3/files/%s/comments';

    private static function getHttpApiClient(): Client
    {
        return new Client([
            'verify' => false, // Don't verify SSL connection
            'headers' => ['x-apikey' => $_ENV['APP_VIRUSTOTAL_API_KEY']]
        ]);
    }

    public static function scanFile(string $file_path): false|array
    {
        // Make sure VirusTotal API key is set
        if(empty($_ENV['APP_VIRUSTOTAL_API_KEY'])){
            throw new \Exception("APP_VIRUSTOTAL_API_KEY needs to be set");
        }

        // Make sure file exists
        if(!\file_exists($file_path)){
            throw new \Exception("unable to read file: {$file_path}");
        }

        // Get filesize
        $file_size = \filesize($file_path);
        if(!$file_size){
            throw new \Exception("failed to get filesize of file: {$file_path}");
        }

        try {
            // Send the request
            $response = self::getHttpApiClient()->request('POST', self::API_SCAN_FILES_ENDPOINT, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => Psr7\Utils::tryFopen($file_path, 'r')
                    ]
                ]
            ]);

            // Make sure requests was successful
            if(!$response || $response->getStatusCode() !== 200){
                return false;
            }

            // Get the response body as JSON
            $body = $response->getBody()->getContents();

            // Parse the JSON response
            $data = \json_decode($body, true);

            return $data;

        } catch (\Exception $e) {
            return false;
        }
    }

    public static function addComment(string|int $id, string $comment): bool
    {
        // Make sure VirusTotal API key is set
        if(empty($_ENV['APP_VIRUSTOTAL_API_KEY'])){
            throw new \Exception("APP_VIRUSTOTAL_API_KEY needs to be set");
        }

        try {
            // Send the request
            $response = self::getHttpApiClient()->request('POST', \sprintf(self::API_ADD_COMMENT_ENDPOINT, $id), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-apikey'     => $_ENV['APP_VIRUSTOTAL_API_KEY']
                ],
                'json' => [
                    'data' => [
                        'type' => 'comment',
                        'attributes' => [
                            'text' => $comment
                        ]
                    ]
                ]
            ]);

            dump($response);

            // Make sure request was successful
            if(!$response || $response->getStatusCode() !== 200){
                dump($response->getBody());
                return false;
            }

            // If we have 200 response we have posted the comment
            return true;

        } catch (\Exception $e) {

            dump($e);

            return false;
        }
    }

}
