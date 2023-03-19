<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * The avatar controller is used to output avatars.
 * Updating an avatar is done in the AccountController.
 */
class AvatarController {

    public function outputAvatar(
        Request $request,
        Response $response,
        $filename,
    ){
        // Get avatar filepath
        $filepath = $_ENV['APP_AVATAR_STORAGE'] . '/' . $filename;

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request, 'avatar not found');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $filepath);
        \finfo_close($finfo);

        // Return avatar
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($filepath)
        );

        return $response;
    }
}
