<?php

namespace App\Controller\Api\v1;

use App\Enum\ReleaseType;

use App\Config\Config;
use App\GameFileHandler;
use Psr\SimpleCache\CacheInterface;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;

use Xenokore\Utility\Helper\DirectoryHelper;

class GameFileController
{

    public function listFiles(
        Request $request,
        Response $response,
        CacheInterface $cache,
        GameFileHandler $game_file_handler,
        string $type,
        string $version,
    ){
        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        // Try and get the release type
        try {
            $release_type = ReleaseType::from($type);
        } catch (\ValueError $ex){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'INVALID_RELEASE_TYPE'
                ])
            );
            return $response->withStatus(500);
        }

        // Get path and make sure it is accessible
        $path = Config::get('storage.path.game-files') . '/' . $release_type->value . '/' . $version;
        if(DirectoryHelper::isAccessible($path) === false){
            throw new HttpNotFoundException($request, "'{$path}' not accessible");
        }

        // Get game file index
        $index = $game_file_handler->getIndex($release_type, $version);
        if($index === false){
            throw new HttpNotFoundException($request, "game file index not found: {$release_type->value} {$version}");
        }

        // Return response
        $response->getBody()->write(
            \json_encode([
                'success'      => true,
                'release_type' => $release_type->value,
                'version'      => $version,
                'files'        => $index,
            ])
        );
        return $response
            ->withStatus(200);
    }
}
