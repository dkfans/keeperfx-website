<?php

namespace App\Controller;

use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use App\Entity\NewsArticle;

use FeedWriter\RSS2;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\CommonMark\CommonMarkConverter;

class MasterServerController {

    public function list(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('masterserver.list.html.twig')
        );

        return $response;
    }

    public function jsonList(
        Request $request,
        Response $response,
    )
    {
        $json = [
            'success' => false,
        ];

        // Make sure sockets are available
        if(!\function_exists('socket_create')){
            $json['error'] = 'MISSING_PHP_FUNCTION';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Get Masterserver Host
        $host = (string) ($_ENV['APP_MASTERSERVER_HOST'] ?? '');
        if(empty($host)){
            $json['error'] = 'NO_MASTER_SERVER_CONFIGURED';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Split the host and port
        list($host, $port) = \explode(":", $host);

        // Create a socket
        $socket = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $json['error'] = 'FAILED_TO_CREATE_SOCKET';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Connect to the server
        if (\socket_connect($socket, $host, $port) === false) {
            $json['error'] = 'FAILED_TO_CONNECT_TO_SOCKET';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Read the response
        $socket_response = \socket_read($socket, 1024);
        if ($socket_response === false) {
            $json['error'] = 'FAILED_TO_READ_SOCKET_RESPONSE';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Decode the response
        $socket_json = @\json_decode($socket_response, true);
        if(!$socket_json || empty($socket_json['keeperfx'])){
            $json['error'] = 'INVALID_SOCKET_RESPONSE';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Ask the server for the lobbies
        $message = \json_encode(['method' => 'list_lobbies']);
        \socket_write($socket, $message, \strlen($message));

        // Read the response
        $socket_response = \socket_read($socket, 1024*1024);
        if ($socket_response === false) {
            $json['error'] = 'FAILED_TO_READ_SOCKET_RESPONSE';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        // Decode the response
        $socket_json = @\json_decode($socket_response, true);
        if(!$socket_json || !isset($socket_json['lobbies'])){
            $json['error'] = 'INVALID_SOCKET_RESPONSE';
            $response->getBody()->write(
                \json_encode($json)
            );
            return $response;
        }

        $json['success'] = true;
        $json['lobbies'] = $socket_json['lobbies'];
        $response->getBody()->write(
            \json_encode($json)
        );

        return $response;
    }

}
