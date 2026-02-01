<?php

namespace App\Controller\AdminCP;

use App\Entity\Mail;
use App\Entity\User;
use App\Entity\UserIpLog;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;
use App\Entity\WorkshopComment;
use App\Entity\GithubAlphaBuild;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminServerInfoController
{

    public function serverInfoIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        // Get PHP upload limits
        $php_max_upload            = (int)(\ini_get('upload_max_filesize')) * 1024 * 1024;
        $php_max_post              = (int)(\ini_get('post_max_size')) * 1024 * 1024;
        $php_memory_limit          = (int)(\ini_get('memory_limit')) * 1024 * 1024;
        $upload_calculated_minimum = \min($php_max_upload, $php_max_post, $php_memory_limit);

        // Get alpha builds size
        $alpha_build_storage_size = 0;
        $alpha_builds = $em->getRepository(GithubAlphaBuild::class)->findAll();
        if ($alpha_builds) {
            foreach ($alpha_builds as $alpha_build) {
                $alpha_build_storage_size += $alpha_build->getSizeInBytes();
            }
        }

        // Get workshop files total storage size
        $workshop_file_storage_size = 0;
        $workshop_files = $em->getRepository(WorkshopFile::class)->findAll();
        if ($workshop_files) {
            foreach ($workshop_files as $workshop_file) {
                $workshop_file_storage_size += $workshop_file->getSize();
            }
        }

        // Response
        $response->getBody()->write(
            $twig->render('admincp/server-info.admincp.html.twig', [
                'alpha_build_count'             => \count($alpha_builds),
                'alpha_build_storage_size'      => $alpha_build_storage_size,
                'workshop_item_count'           => $em->getRepository(WorkshopItem::class)->count([]),
                'workshop_file_count'           => \count($workshop_files),
                'workshop_file_storage_size'    => $workshop_file_storage_size,
                'user_count'                    => $em->getRepository(User::class)->count([]),
                'ip_log_count'                  => $em->getRepository(UserIpLog::class)->count([]),
                'mails_count'                   => $em->getRepository(Mail::class)->count([]),
                'mails_in_queue_count'          => $em->getRepository(Mail::class)->count(['status' => 0]),
                'workshop_comment_count'        => $em->getRepository(WorkshopComment::class)->count([]),
                'php_version'                   => \phpversion(),
                'php_max_upload'                => $php_max_upload,
                'php_max_post'                  => $php_max_post,
                'php_memory_limit'              => $php_memory_limit,
                'upload_calculated_minimum'     => $upload_calculated_minimum,
                'last_user'                     => $em->getRepository(User::class)->findOneBy([], ['created_timestamp' => 'DESC']),
                'last_workshop_item'            => $em->getRepository(WorkshopItem::class)->findOneBy([], ['created_timestamp' => 'DESC']),
                'ipv6_support'                  => \defined('AF_INET6') && @\inet_pton('::1') !== false,
            ])
        );

        return $response;
    }
}
