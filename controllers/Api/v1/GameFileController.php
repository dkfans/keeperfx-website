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

        // Check if this file list is in the cache
        $cache_key = GameFileHandler::generateCacheKey($release_type, $version);
        $list = $cache->get($cache_key);

        // Return files from cache if hit
        if($list !== null && \is_array($list) && !empty($list)){
            $response->getBody()->write(
                \json_encode([
                    'success'      => true,
                    'release_type' => $release_type->value,
                    'version'      => $version,
                    'cache_status' => 'HIT',
                    'files'        => $list,
                ])
            );
            return $response
                ->withStatus(200)
                ->withHeader('X-Cache', 'HIT');
        }

        // Get path and make sure it is accessible
        $path = Config::get('storage.path.game-files') . '/' . $release_type->value . '/' . $version;
        if(DirectoryHelper::isAccessible($path) === false){
            throw new HttpNotFoundException($request, "'{$path}' not accessible");
        }

        // Generate an index of the game files for this type and version
        $list = GameFileHandler::generateIndexFromPath($path);

        // Check if the list was made
        if(!$list){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'FAILED_TO_GENERATE_FILE_LIST',
                ])
            );
            return $response->withStatus(500);
        }

        // Store valid file list in cache
        $cache->set($cache_key, $list, (int)$_ENV['APP_GAME_FILE_CACHE_TTL']);

        // Return response
        $response->getBody()->write(
            \json_encode([
                'success'      => true,
                'release_type' => $release_type->value,
                'version'      => $version,
                'cache_status' => 'MISS',
                'files'        => $list,
            ])
        );
        return $response
            ->withStatus(200)
            ->withHeader('X-Cache', 'MISS');
    }
}
