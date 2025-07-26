<?php

namespace App\Controller;

use Slim\Psr7\Stream;
use App\Config\Config;
use App\Entity\LauncherRelease;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\LazyOpenStream;
use Twig\Environment as TwigEnvironment;
use Slim\Exception\HttpNotFoundException;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WebInstallerController
{

    public function outputFile(
        Request $request,
        Response $response,
        EntityManager $em,
        $name_hash,
        $filename,
    ) {
        // Get all launcher releases
        $launcher_releases = $em->getRepository(LauncherRelease::class)->findBy([], ['timestamp' => 'DESC']);
        if (\count($launcher_releases) === 0) {
            throw new HttpNotFoundException($request, 'no launcher releases found');
        }

        // Loop trough all launcher releases
        /** @var LauncherRelease $launcher_release */
        foreach ($launcher_releases as $launcher_release) {

            if ($launcher_release->getNameHash() === $name_hash) {

                // Get filepath of web installer
                $filepath = Config::get('storage.path.launcher') . '/' . $launcher_release->getName() . '/' . $filename;

                // Check if file exists
                if (!\file_exists($filepath)) {
                    throw new HttpNotFoundException($request, 'file does not exist');
                }

                // Force file download
                $stream = \fopen($filepath, 'r');
                $cache_time = (int)($_ENV['APP_WEB_INSTALLER_DOWNLOAD_CACHE_TIME'] ?? 1209600);
                $response = $response
                    ->withHeader('Pragma', 'public')
                    ->withHeader('Cache-Control', 'max-age=' . $cache_time)
                    ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
                    ->withHeader('Content-Length', \filesize($filepath))
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Transfer-Encoding', 'Binary')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                return $response->withBody(
                    new Stream($stream)
                );
            }
        }

        throw new HttpNotFoundException($request, 'launcher release not found');
    }
}
