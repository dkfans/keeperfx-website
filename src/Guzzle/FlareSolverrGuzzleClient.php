<?php

namespace App\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class FlareSolverrGuzzleClient extends Client
{
    public function __construct(array $config = [])
    {
        // Check for FlareSolverr client
        $env_val = $_ENV['GUZZLE_FLARESOLVERR_CLIENT'] ??  null;

        // Determine if FlareSolverr should be enabled
        $is_enabled = !empty($env_val) && !in_array(strtolower((string)$env_val), ['false', '0', 'null'], true);

        if ($is_enabled) {
            $flaresolverr_url = rtrim((string)$env_val, '/') . '/v1';

            // Get existing handler stack or create a new one
            $stack = $config['handler'] ?? HandlerStack::create();

            // Intercept OUTGOING requests and route them to FlareSolverr
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($flaresolverr_url) {
                $method = strtoupper($request->getMethod());

                // FlareSolverr primarily supports request.get and request.post
                $cmd = $method === 'POST' ? 'request.post' : 'request.get';
                $post_data = (string) $request->getBody();

                $payload = [
                    'cmd'        => $cmd,
                    'url'        => (string) $request->getUri(),
                    'maxTimeout' => 60000, // 60 seconds to solve the challenge
                ];

                if ($method === 'POST' && $post_data !== '') {
                    $payload['postData'] = $post_data;
                }

                // Rewrite the Guzzle request to hit the FlareSolverr API instead
                return new Request(
                    'POST',
                    $flaresolverr_url,
                    ['Content-Type' => 'application/json'],
                    json_encode($payload)
                );
            }), 'flaresolverr_request');

            // Intercept INCOMING responses and unwrap the actual website's HTML/Headers
            $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
                $body = (string) $response->getBody();
                $data = json_decode($body, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    // Handle FlareSolverr-specific errors (e.g., timeout, challenge not solved)
                    if (isset($data['status']) && $data['status'] === 'error') {
                        // Return a 500 error so standard Guzzle Exception handling catches it
                        return new Response(500, ['Content-Type' => 'application/json'], $body);
                    }

                    // If solved successfully, unpack the inner response
                    if (isset($data['solution']['response'])) {
                        $solution = $data['solution'];
                        $html     = $solution['response'];
                        $status   = (int) ($solution['status'] ?? 200);

                        // Ensure headers are strictly strings
                        $headers = [];
                        if (isset($solution['headers']) && is_array($solution['headers'])) {
                            foreach ($solution['headers'] as $k => $v) {
                                $headers[$k] = (string) $v;
                            }
                        }

                        return new Response($status, $headers, $html);
                    }
                }

                // Fallback: return raw response if it doesn't match the expected FlareSolverr structure
                return $response;
            }), 'flaresolverr_response');

            // Save the modified stack back to the config
            $config['handler'] = $stack;
        }

        // Initialize the parent Guzzle Client with the final configuration
        parent::__construct($config);
    }
}
