<?php

use App\Helper\PathHelper;

return [
    'path' => [
        'workshop'                      => PathHelper::getAppPathFromEnvVar('APP_WORKSHOP_STORAGE'),
        'avatar'                        => PathHelper::getAppPathFromEnvVar('APP_AVATAR_STORAGE'),
        'news-img'                      => PathHelper::getAppPathFromEnvVar('APP_NEWS_IMAGE_STORAGE'),
        'crash-report-savefile'         => PathHelper::getAppPathFromEnvVar('APP_CRASH_REPORT_SAVEFILE_STORAGE'),
        'alpha-patch'                   => PathHelper::getAppPathFromEnvVar('APP_ALPHA_PATCH_STORAGE'),
        'alpha-patch-file-bundle'       => PathHelper::getAppPathFromEnvVar('APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE'),
        'prototype'                     => PathHelper::getAppPathFromEnvVar('APP_PROTOTYPE_STORAGE'),
        'prototype-file-bundle'         => PathHelper::getAppPathFromEnvVar('APP_PROTOTYPE_FILE_BUNDLE_STORAGE'),
        'kfx-repo'                      => PathHelper::getAppPathFromEnvVar('APP_KFX_REPO_STORAGE'),
        'wiki-repo'                     => PathHelper::getAppPathFromEnvVar('APP_WIKI_REPO_STORAGE'),
        'admin-upload'                  => PathHelper::getAppPathFromEnvVar('APP_ADMIN_UPLOAD_STORAGE'),
        'logs'                          => PathHelper::getAppPathFromEnvVar('APP_LOG_STORAGE'),
        'game-files'                    => PathHelper::getAppPathFromEnvVar('APP_GAME_FILE_STORAGE'),
        'launcher'                      => PathHelper::getAppPathFromEnvVar('APP_LAUNCHER_STORAGE'),
    ],
];
