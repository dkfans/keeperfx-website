<?php

namespace App\Controller\DevCP;

use App\Entity\CrashReport;

use App\FlashMessage;
use Directory;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Xenokore\Utility\Helper\DirectoryHelper;

class ModerateBundledAssetsController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em
    ){

        // Get directory
        $dir = $_ENV['APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE'];

        // Make sure directory exists and is a dir
        if(!\file_exists($dir) || !\is_dir($dir)){
            $flash->error("Configured bundle directory does not exist or is not a directory: {$dir}");
            $response->getBody()->write(
                $twig->render('cp/_cp_layout.html.twig')
            );
            return $response;
        }

        $tree = $this->buildWidgetFileTree($dir);

        // Response
        $response->getBody()->write(
            $twig->render('devcp/bundled-assets.devcp.html.twig', [
                'directory_structure' => $tree,
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
    private function buildWidgetFileTree(string $dir) {

        $return = [];

        $i=0;

        // Create nodelist
        foreach (new \DirectoryIterator($dir) as $entry) {

            $node = [
                'text' => $entry->getBasename(),
            ];

            if($entry->isDot()){
                continue;
            }

            if ($entry->isDir()) {
                $node['nodes'] = $this->buildWidgetFileTree($dir . '/' . $entry->getBasename());
            }

            $return[$i++] = $node;
        }

        // Sort alphabetically
        usort($return, function($a, $b){
            return
                \strtolower($a['text'])
                <=>
                \strtolower($b['text'])
            ;
        });

        // Move directories up front
        usort($return, function($a, $b){
            return
                (int) !isset($a['nodes'])
                <=>
                (int) !isset($b['nodes'])
            ;
        });

        return $return;
    }

}
