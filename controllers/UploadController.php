<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class UploadController {

    public function outputFile(
        Request $request,
        Response $response,
        $filename,
    ){
        if(!isset($_ENV['APP_ADMIN_UPLOAD_ENABLED']) || !$_ENV['APP_ADMIN_UPLOAD_ENABLED']){
            throw new HttpNotFoundException($request);
        }

        // Get avatar filepath
        $filepath = $_ENV['APP_ADMIN_UPLOAD_STORAGE'] . '/' . $filename;

        // TODO: loop trough folder, compare filenames
        // This should protect against any possible LFI

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request, 'file does not exist');
        }

        // Force file download
        $cache_time = (int)($_ENV['APP_ADMIN_UPLOAD_OUTPUT_CACHE_TIME'] ?? 86400);
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Length', \filesize($filepath))
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->getBody()->write(
            \file_get_contents($filepath)
        );

        return $response;
    }
}
