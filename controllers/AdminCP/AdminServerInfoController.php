<?php

namespace App\Controller\AdminCP;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminServerInfoController {

    public function serverInfoIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        // Get PHP upload limits
        $php_max_upload            = (int)(\ini_get('upload_max_filesize')) * 1024 * 1024;
        $php_max_post              = (int)(\ini_get('post_max_size')) * 1024 * 1024;
        $php_memory_limit          = (int)(\ini_get('memory_limit')) * 1024 * 1024;
        $upload_calculated_minimum = \min($php_max_upload, $php_max_post, $php_memory_limit);

        // Alpha build vars
        $alpha_build_count = 0;
        $alpha_build_storage_size = 0;
        $alpha_build_storage_path = $_ENV['APP_ALPHA_PATCH_STORAGE'];

        // Check if alpha build dir exists
        if(!\is_dir($alpha_build_storage_path)){
            return $response;
        }

        // Get count and size of alpha builds
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($alpha_build_storage_path)) as $file) {
            $alpha_build_count++;
            $alpha_build_storage_size += $file->getSize();
        }

        $response->getBody()->write(
            $twig->render('admincp/server-info.admincp.html.twig', [
                'alpha_build_count'             => $alpha_build_count,
                'alpha_build_storage_size'      => $alpha_build_storage_size,
                'php_max_upload'                => $php_max_upload,
                'php_max_post'                  => $php_max_post,
                'php_memory_limit'              => $php_memory_limit,
                'upload_calculated_minimum'     => $upload_calculated_minimum,
            ])
        );

        return $response;
    }

}
