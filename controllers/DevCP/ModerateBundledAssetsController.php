<?php

namespace App\Controller\DevCP;

use App\Entity\CrashReport;

use App\FlashMessage;
use App\Config\Config;

use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class ModerateBundledAssetsController
{

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em
    ) {

        // Get directories
        $game_files_bundle_dir  = Config::get('storage.path.game-files-file-bundle');
        $alpha_patch_bundle_dir = Config::get('storage.path.alpha-patch-file-bundle');
        $prototype_bundle_dir   = Config::get('storage.path.prototype-file-bundle');

        // Make sure game files directory exists and is a dir
        if (!\file_exists($game_files_bundle_dir) || !\is_dir($game_files_bundle_dir)) {
            $flash->error("Configured game files bundle directory does not exist or is not a directory: {$game_files_bundle_dir}");
            $response->getBody()->write(
                $twig->render('cp/_cp_layout.html.twig')
            );
            return $response;
        }

        // Make sure alpha patch directory exists and is a dir
        if (!\file_exists($alpha_patch_bundle_dir) || !\is_dir($alpha_patch_bundle_dir)) {
            $flash->error("Configured alpha patch bundle directory does not exist or is not a directory: {$alpha_patch_bundle_dir}");
            $response->getBody()->write(
                $twig->render('cp/_cp_layout.html.twig')
            );
            return $response;
        }

        // Make sure prototype directory exists and is a dir
        if (!\file_exists($prototype_bundle_dir) || !\is_dir($prototype_bundle_dir)) {
            $flash->error("Configured prototype bundle directory does not exist or is not a directory: {$prototype_bundle_dir}");
            $response->getBody()->write(
                $twig->render('cp/_cp_layout.html.twig')
            );
            return $response;
        }

        // Build file tree data structure for the widget on the output view
        $game_files_tree  = $this->buildWidgetFileTree($game_files_bundle_dir);
        $alpha_patch_tree = $this->buildWidgetFileTree($alpha_patch_bundle_dir);
        $prototype_tree   = $this->buildWidgetFileTree($prototype_bundle_dir);

        // Response
        $response->getBody()->write(
            $twig->render('devcp/bundled-assets.devcp.html.twig', [
                'game_files_tree'  => $game_files_tree,
                'alpha_patch_tree' => $alpha_patch_tree,
                'prototype_tree'   => $prototype_tree,
            ])
        );
        return $response;
    }

    /**
     * This function converts a directory into a `bootstrap-treeview.js` compatible array
     *
     * @param string $dir
     * @return void
     */
    private function buildWidgetFileTree(string $dir)
    {

        $return = [];

        $i = 0;

        // Create nodelist
        foreach (new \DirectoryIterator($dir) as $entry) {

            $node = [
                'text' => $entry->getBasename(),
            ];

            if ($entry->isDot()) {
                continue;
            }

            if ($entry->isDir()) {
                $node['nodes'] = $this->buildWidgetFileTree($dir . '/' . $entry->getBasename());
            }

            $return[$i++] = $node;
        }

        // Sort alphabetically
        usort($return, function ($a, $b) {
            return
                \strtolower($a['text'])
                <=>
                \strtolower($b['text']);
        });

        // Move directories up front
        usort($return, function ($a, $b) {
            return
                (int) !isset($a['nodes'])
                <=>
                (int) !isset($b['nodes']);
        });

        return $return;
    }
}
