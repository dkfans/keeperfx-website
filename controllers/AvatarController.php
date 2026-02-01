<?php

namespace App\Controller;

use App\Config\Config;

use \LasseRafn\InitialAvatarGenerator\InitialAvatar as AvatarGenerator;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;

/**
 * The avatar controller is used to output avatars.
 * Updating an avatar is done in the AccountController.
 */
class AvatarController
{

    public function outputAvatar(
        Request $request,
        Response $response,
        $filename,
    ) {
        // Get avatar filepath
        $filepath = Config::get('storage.path.avatar') . '/' . $filename;

        // Check if file exists
        if (!\file_exists($filepath)) {
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

    /**
     * Avatar generation endpoint
     *
     * @link https://packagist.org/packages/lasserafn/php-initial-avatar-generator
     *
     * @param Request $request
     * @param Response $response
     * @param string $username
     * @return Response $response
     */
    public function generateAvatarPng(
        Request $request,
        Response $response,
        $size,
        $username,
    ): Response {
        // Make sure username is legit
        if (!\preg_match('/^[a-zA-Z0-9]+[a-zA-Z0-9\.\_\-]+$/', $username)) {
            throw new HttpBadRequestException($request);
        }

        // Make sure size is not too small or too big
        if (!is_int($size) || $size < 1 || $size > 512) {
            $size = 256;
        }

        // Create avatar
        $avatar = new AvatarGenerator();
        $image = $avatar
            ->name($username)
            ->font(APP_ROOT . '/public/font/nunito/static/Nunito-ExtraBold.ttf')
            ->size($size)
            ->autoColor()
            ->generate();

        // Set output headers
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', 'image/png');

        // Output PNG data
        $response->getBody()->write(
            $image->toPng()->toString()
        );
        return $response;
    }
}
