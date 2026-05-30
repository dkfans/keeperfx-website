<?php

namespace App\Controller;

use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use App\Entity\LauncherRelease;

use App\FlashMessage;

use Doctrine\ORM\EntityManager;
use DeviceDetector\DeviceDetector;
use Twig\Environment as TwigEnvironment;
use DeviceDetector\Parser\OperatingSystem;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DownloadController
{

    public function downloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        CacheInterface $cache,
    ) {

        // Get alpha and launcher releases
        $alpha_builds    = $em->getRepository(GithubAlphaBuild::class)->findBy(['is_available' => true], ['workflow_run_id' => 'DESC', 'timestamp' => 'DESC'], 5);
        $launcher        = $em->getRepository(LauncherRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        // Determine old Windows versions
        try {
            $user_agent = $request->getHeaderLine('User-Agent');
            $device_detector = new DeviceDetector($user_agent);
            $device_detector->parse();
            $os = $device_detector->getOs();
            if (!empty($os['name']) && !empty($os['version'])) {
                if ($os['name'] === 'Windows' && ((int) $os['version']) < 10) {
                    $flash->info(
                        "You seem to be using an old Windows version and will be unable to use the web installer and new launcher.<br /><br />" .
                            "Download the portable release below and use the legacy launcher instead."
                    );
                }
            }
        } catch (\Exception $ex) {
        }

        // Output
        $response->getBody()->write(
            $twig->render('downloads.html.twig', [
                'alpha_builds'    => $alpha_builds,
                'launcher'        => $launcher,
            ])
        );
        return $response;
    }

    public function stableDownloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $stable_releases = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('downloads.stable.html.twig', [
                'stable_releases' => $stable_releases,
            ])
        );

        return $response;
    }

    public function alphaDownloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $alpha_builds    = $em->getRepository(GithubAlphaBuild::class)->findBy(['is_available' => true], ['workflow_run_id' => 'DESC', 'timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('downloads.alpha.html.twig', [
                'alpha_builds'    => $alpha_builds,
            ])
        );

        return $response;
    }
}
