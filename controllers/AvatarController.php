<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class AvatarController {

    public function outputAvatar(
        Request $request,
        Response $response,
        $filename,
    ){
        // Get thumbnail filepath
        $filepath = $_ENV['APP_AVATAR_STORAGE'] . '/' . $filename;

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request, 'avatar not found');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $filepath);
        \finfo_close($finfo);

        // Return screenshot
        $response = $response->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($filepath)
        );

        return $response;
    }
}
