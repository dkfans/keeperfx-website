<?php

namespace App\Controller;

use \LasseRafn\InitialAvatarGenerator\InitialAvatar as AvatarGenerator;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;

/**
 * The avatar controller is used to output avatars.
 * Updating an avatar is done in the AccountController.
 */
class DebugBarAssetController {

    public function outputAsset(
        Request $request,
        Response $response,
        $path,
    ){
        // Only allowed in dev environment
        if($_ENV['APP_ENV'] !== 'dev'){
            throw new HttpForbiddenException($request);
        }

        // Get asset filepath
        $filepath = APP_ROOT . '/vendor/php-debugbar/php-debugbar/src/DebugBar/Resources/' . $path;

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request, 'debugbar asset not found');
        }

        // Get content type of file based on file extension
        switch(\pathinfo($filepath, \PATHINFO_EXTENSION)){
            case 'css':
                $content_type = 'text/css';
                break;
            case 'js':
                $content_type = 'application/javascript';
                break;
            default:
                $content_type = 'text/plain';
                break;
        }

        // Return file
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=0')
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', 0))
            ->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($filepath)
        );

        return $response;
    }
}
