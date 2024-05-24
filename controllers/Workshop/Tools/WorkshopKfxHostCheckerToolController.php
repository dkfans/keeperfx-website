<?php

namespace App\Controller\Workshop\Tools;

use App\FlashMessage;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;
use Xenokore\Utility\Helper\StringHelper;

/**
 * A tool to compare CFGs and show the differences.
 * This is useful for getting only updated properties from KeeperFX configs.
 */
class WorkshopKfxHostCheckerToolController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ){
        // Get IP
        $ip = $request->getAttribute('ip_address');

        // Make sure IP is valid IPv4 address
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) == false) {
            $ip = null;
        }

        // Response
        $response->getBody()->write(
            $twig->render('workshop/tools/kfx_host_checker_tool.html.twig', ['ip' => $ip])
        );
        return $response;
    }

    public function ping(
        Request $request,
        Response $response,
        string $ip,
    ){
        if (filter_var($ip, FILTER_VALIDATE_IP) == false) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error' => 'INVALID_IP'
                ])
            );
            return $response;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) == false) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error' => 'MUST_BE_IPV4'
                ])
            );
            return $response;
        }

        // Raw packet data (excluding IP and UDP headers)
        $packet = hex2bin('8fff864b82ff00010000ffff0000057800010000000000020000000000000000000013880000000200000002ec5093d400000000');
        $port = 5556;

        // Create a UDP socket
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            die("Could not create socket\n");
        }

        // Set a 5-second timeout for sending and receiving (10 sec total)
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        // Send the binary payload to the server
        if (socket_sendto($socket, $packet, strlen($packet), 0, $ip, $port) === false) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error' => 'SOCKET_ERROR'
                ])
            );
            return $response;
        }

        // Receive response from the server
        $port = 0;
        if (socket_recvfrom($socket, $buffer, 2048, 0, $address, $port) === false) {
            $error = socket_last_error($socket);
            if ($error == SOCKET_EWOULDBLOCK || $error == SOCKET_ETIMEDOUT) {
                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error' => 'TIMED_OUT'
                    ])
                );
                return $response;
            } else {
                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error' => 'FAILED_TO_RECEIVE_DATA'
                    ])
                );
                return $response;
            }
        }

        // Close the socket
        \socket_close($socket);

        // Return
        $response->getBody()->write(
            \json_encode([
                'success' => true
            ])
        );
        return $response;
    }
}
