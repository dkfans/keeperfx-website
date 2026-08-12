<?php

namespace App\Controller\AdminCP;

use App\CDN;
use App\Config\Config;
use App\Entity\GithubAlphaBuild;
use App\Entity\Mail;
use App\Entity\User;
use App\Entity\UserIpLog;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopFile;
use App\Entity\WorkshopItem;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;

class AdminServerInfoController
{
    public function serverInfoIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        CDN $cdn,
    ) {
        // Get PHP upload limits
        $php_max_upload            = (int) \ini_get('upload_max_filesize') * 1024 * 1024;
        $php_max_post              = (int) \ini_get('post_max_size')       * 1024 * 1024;
        $php_memory_limit          = (int) \ini_get('memory_limit')        * 1024 * 1024;
        $upload_calculated_minimum = \min($php_max_upload, $php_max_post, $php_memory_limit);

        // Get alpha builds size
        $alpha_build_storage_size = 0;
        $alpha_builds             = $em->getRepository(GithubAlphaBuild::class)->findAll();
        if ($alpha_builds) {
            foreach ($alpha_builds as $alpha_build) {
                $alpha_build_storage_size += $alpha_build->getSizeInBytes();
            }
        }

        // Get workshop files total storage size
        $workshop_file_storage_size = 0;
        $workshop_files             = $em->getRepository(WorkshopFile::class)->findAll();
        if ($workshop_files) {
            foreach ($workshop_files as $workshop_file) {
                $workshop_file_storage_size += $workshop_file->getSize();
            }
        }

        // CDN information
        $cdn_info = [
            'null' => [
                'count' => $em->getRepository(User::class)->count(['cdn' => null]),
                'name'  => Config::get('cdn.endpoints.' . Config::get('cdn.default') . '.name') . ' (custom country rules)',
            ],
        ];
        foreach ($cdn->getAll() as $id => $data) {
            $cdn_info[$id] = [
                'count' => $em->getRepository(User::class)->count(['cdn' => $id]),
                'name'  => $data['name'],
            ];
        }

        // Users
        $users = $em->getRepository(User::class)->findAll();

        // Country counts
        $country_counts = [];
        foreach ($users as $user) {
            $country_code = $user->getCountry();
            if ($country_code !== null) {
                if (\array_key_exists($country_code, $country_counts) === false) {
                    $country_counts[$country_code] = 0;
                }
                ++$country_counts[$country_code];
            }
        }
        \arsort($country_counts);

        $env_vars = [
            'APP_ENV',
            'APP_ROOT_DOMAIN',
            'APP_ROOT_URL',
            'APP_SMTP_FROM_NAME',
            'APP_SMTP_FROM_ADDRESS',
            'APP_SMTP_HOST',
            'APP_SMTP_PORT',
            'APP_WORKSHOP_UNEARTH_URL',
            'APP_DISCORD_INVITE_ID',
            'APP_RAISE_EXCEPTION_ON_WARNING',
            'APP_DB_HOST',
            'APP_DB_PORT',
            'APP_CACHE_DIR',
            'APP_CACHE_ADAPTER',
            'APP_LOG_STORAGE',
            'APP_COOKIE_PATH',
            'APP_COOKIE_DOMAIN',
            'APP_COOKIE_TLS_ONLY',
            'APP_COOKIE_HTTP_ONLY',
            'APP_COOKIE_SAMESITE',
            'APP_REMEMBER_ME_TIME',
            'APP_WORKSHOP_STORAGE',
            'APP_WORKSHOP_ITEM_MAX_UPLOAD_SIZE',
            'APP_WORKSHOP_IMAGE_MAX_UPLOAD_SIZE',
            'APP_WORKSHOP_DOWNLOAD_IP_REMEMBER_TIME',
            'APP_AVATAR_STORAGE',
            'APP_AVATAR_MAX_UPLOAD_SIZE',
            'APP_NEWS_IMAGE_STORAGE',
            'APP_NEWS_IMAGE_MAX_UPLOAD_SIZE',
            'APP_CRASH_REPORT_SAVEFILE_STORAGE',
            'APP_CRASH_REPORT_SAVEFILE_MAX_UPLOAD_SIZE',
            'APP_ALPHA_PATCH_STORAGE',
            'APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE',
            'APP_ALPHA_PATCH_GITHUB_WORKFLOW_ID',
            'APP_PROTOTYPE_STORAGE',
            'APP_PROTOTYPE_FILE_BUNDLE_STORAGE',
            'APP_PROTOTYPE_GITHUB_WORKFLOW_ID',
            'APP_PROTOTYPE_STORAGE_TIME',
            'APP_LAUNCHER_STORAGE',
            'APP_KFX_REPO_URL',
            'APP_KFX_REPO_STORAGE',
            'APP_WIKI_REPO_URL',
            'APP_WIKI_REPO_STORAGE',
            'APP_WEBSITE_REPO_URL',
            'APP_WEBSITE_REPO_STORAGE',
            'APP_ADMIN_UPLOAD_ENABLED',
            'APP_ADMIN_UPLOAD_STORAGE',
            'APP_ADMIN_UPLOAD_OUTPUT_CACHE_TIME',
            'APP_GAME_FILE_STORAGE',
            'APP_GAME_FILE_CACHE_TTL',
            'APP_GAME_FILE_MAX_STABLE_VERSIONS',
            'APP_GAME_FILE_MAX_ALPHA_VERSIONS',
            'APP_GAME_FILE_BUNDLE_STORAGE',
            'APP_GAME_FILE_BUNDLE_WITH_RELEASE',
            'APP_WEB_INSTALLER_DOWNLOAD_CACHE_TIME',
            'APP_POLLING_NOTIFICATIONS',
            'APP_FORUM_ACTIVITY_ENABLED',
            'APP_FORUM_ACTIVITY_THREAD_COUNT',
            'APP_FORUM_ACTIVITY_URL',
            'APP_FORUM_ACTIVITY_IP',
            'APP_GEOIP_DATABASE',
        ];

        // Response
        $response->getBody()->write(
            $twig->render('admincp/server-info.admincp.html.twig', [
                'alpha_build_count'          => \count($alpha_builds),
                'alpha_build_storage_size'   => $alpha_build_storage_size,
                'workshop_item_count'        => \count($users),
                'workshop_file_count'        => \count($workshop_files),
                'workshop_file_storage_size' => $workshop_file_storage_size,
                'user_count'                 => $em->getRepository(User::class)->count([]),
                'ip_log_count'               => $em->getRepository(UserIpLog::class)->count([]),
                'mails_count'                => $em->getRepository(Mail::class)->count([]),
                'mails_in_queue_count'       => $em->getRepository(Mail::class)->count(['status' => 0]),
                'workshop_comment_count'     => $em->getRepository(WorkshopComment::class)->count([]),
                'php_version'                => \PHP_VERSION,
                'php_max_upload'             => $php_max_upload,
                'php_max_post'               => $php_max_post,
                'php_memory_limit'           => $php_memory_limit,
                'upload_calculated_minimum'  => $upload_calculated_minimum,
                'last_user'                  => $em->getRepository(User::class)->findOneBy([], ['created_timestamp' => 'DESC']),
                'last_workshop_item'         => $em->getRepository(WorkshopItem::class)->findOneBy([], ['created_timestamp' => 'DESC']),
                'ipv6_support'               => \defined('AF_INET6') && @\inet_pton('::1') !== false,
                'cdn_info'                   => $cdn_info,
                'cdn_rules'                  => Config::get('cdn.country_defaults'),
                'country_counts'             => $country_counts,
                'env_vars'                   => $env_vars,
            ])
        );

        return $response;
    }
}
