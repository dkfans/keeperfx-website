<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class VirusTotalScanner
{
    public const API_FILE_REPORT_ENDPOINT    = 'https://www.virustotal.com/api/v3/files/%s';
    public const API_SCAN_FILES_ENDPOINT     = 'https://www.virustotal.com/api/v3/files';
    public const API_SCAN_BIG_FILES_ENDPOINT = 'https://www.virustotal.com/api/v3/files/upload_url';

    public const API_SCAN_BIG_FILES_MINIMUM_SIZE = 32000000;

    public const ANALYSIS_MINIMUM_REQUIRED = 50;

    private static function getHttpApiClient(): Client
    {
        return new Client([
            'verify'  => false, // Don't verify SSL connection
            'headers' => ['x-apikey' => $_ENV['APP_VIRUSTOTAL_API_KEY']],
        ]);
    }

    /**
     * Send a file for scanning to VirusTotal.
     *
     * This function will not wait for the result as that would take too long.
     *
     * @param string      $file_path the local filepath of the file to send
     * @param string|null $file_name the original name of the file to send
     *
     * @return false|array<mixed> returns false if we failed to submit, or the JSON response of the VirusTotal endpoint on success
     */
    public static function submitFile(string $file_path, ?string $file_name = null): false|array
    {
        // Make sure VirusTotal API key is set
        if (empty($_ENV['APP_VIRUSTOTAL_API_KEY'])) {
            throw new \Exception('APP_VIRUSTOTAL_API_KEY needs to be set');
        }

        // Make sure file exists
        if (!\file_exists($file_path)) {
            throw new \Exception("unable to read file: {$file_path}");
        }

        // Get filesize
        $file_size = \filesize($file_path);
        if (!$file_size) {
            throw new \Exception("failed to get filesize of file: {$file_path}");
        }

        // The default endpoint
        $upload_endpoint = self::API_SCAN_FILES_ENDPOINT;

        // Check if we need to use the big files endpoint
        if ($file_size > self::API_SCAN_BIG_FILES_MINIMUM_SIZE) {

            // Send the request
            try {
                $response = self::getHttpApiClient()->request('GET', self::API_SCAN_BIG_FILES_ENDPOINT);
            } catch (RequestException $ex) {

                if ($ex->getCode() === 429) {
                    throw new VirusTotalScannerQuoteExceededException();
                }

                return false;
            }

            // Make sure requests was successful
            if ($response->getStatusCode() !== 200) {
                return false;
            }

            // Get the response body as JSON
            $body = $response->getBody()->getContents();

            // Parse the JSON response
            $data = \json_decode($body, true);

            // Change upload endpoint to one for big files
            $upload_endpoint = $data['data'];
        }

        // Send the request
        try {
            $response = self::getHttpApiClient()->request('POST', $upload_endpoint, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => Psr7\Utils::tryFopen($file_path, 'r'),
                        'filename' => $file_name ?? \basename($file_path),
                    ],
                ],
            ]);
        } catch (RequestException $ex) {

            if ($ex->getCode() === 429) {
                throw new VirusTotalScannerQuoteExceededException();
            }

            // The report endpoint
            $report_endpoint = \sprintf(self::API_FILE_REPORT_ENDPOINT, \sha1_file($file_path));

            // Try and get the report
            try {
                $response = self::getHttpApiClient()->request('GET', $report_endpoint);
            } catch (RequestException $ex) {

                if ($ex->getCode() === 429) {
                    throw new VirusTotalScannerQuoteExceededException();
                }

                return false;
            }
        }

        // Make sure requests was successful
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Get the response body as JSON
        $body = $response->getBody()->getContents();

        // Parse the JSON response
        $data = \json_decode($body, true);

        return $data;
    }

    public static function getAnalysisUrl(array $data): false|string
    {
        if (
            !isset($data['data'])
            || !isset($data['data']['links'])
            || !isset($data['data']['type'])
            || !isset($data['data']['links']['self'])
        ) {
            return false;
        }

        // Make sure that we can get the analysis URL
        if ($data['data']['type'] !== 'analysis') {
            return false;
        }

        // Get URL and make sure it's a valid URL
        $url = $data['data']['links']['self'];
        if (!\filter_var($url, \FILTER_VALIDATE_URL)) {
            return false;
        }

        return $url;
    }

    public static function waitForAnalysisAndGetStats(string $analysis_url, bool $retry = true, int $sleep_seconds = 30, int $max_retries = 10, ?callable $on_progress = null): false|array
    {
        $try_count = 1;

        $data = [];

        while ($retry && $try_count <= $max_retries) {

            // Trigger the callback to print output in the console
            if ($on_progress !== null) {
                $on_progress($try_count, $max_retries, $sleep_seconds);
            }

            // Send the request
            try {
                $response = self::getHttpApiClient()->request('GET', $analysis_url);
            } catch (RequestException $ex) {

                if ($ex->getCode() === 429) {
                    throw new VirusTotalScannerQuoteExceededException();
                }

                ++$try_count;
                \sleep($sleep_seconds);

                continue;
            }

            // Get the response body as JSON
            $body = $response->getBody()->getContents();

            // Parse the JSON response
            $data = \json_decode($body, true);

            // Check if analysis is complete
            if ($data['data']['attributes']['status'] === 'completed') {
                break;
            }

            // Check if we have enough results already
            if (\count($data['data']['attributes']['reports'] ?? []) >= self::ANALYSIS_MINIMUM_REQUIRED) {
                break;
            }

            ++$try_count;
            \sleep($sleep_seconds);

            continue;
        }

        // Get stats
        $stats = $data['data']['attributes']['stats'] ?? null;
        if ($stats !== null) {

            // Check if the status is completed
            if (isset($data['data']['attributes']['status']) && $data['data']['attributes']['status'] === 'completed') {
                return $stats;
            }

            // Check if we have enough results
            if (isset($data['data']['attributes']['reports']) && \count($data['data']['attributes']['reports']) >= self::ANALYSIS_MINIMUM_REQUIRED) {
                return $stats;
            }
        }

        return false;
    }

    /**
     * Check if VirusTotal already has a scan report for a specific file hash.
     *
     * @param string $hash SHA-256, SHA-1 or MD5 of the file
     *
     * @return false|array<mixed> JSON response if found, false if not found (404) or on error
     */
    public static function getReportByHash(string $hash): false|array
    {
        $endpoint = \sprintf(self::API_FILE_REPORT_ENDPOINT, $hash);

        try {
            $response = self::getHttpApiClient()->request('GET', $endpoint);
        } catch (RequestException $ex) {

            if ($ex->getCode() === 429) {
                throw new VirusTotalScannerQuoteExceededException();
            }

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $body = $response->getBody()->getContents();

        return \json_decode($body, true);
    }
}
