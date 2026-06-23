<?php

return [
    'path' => [
        'logs'                    => $_ENV['APP_LOG_STORAGE']                     ?? '/app/log',

        'workshop'                => $_ENV['APP_WORKSHOP_STORAGE']                ?? '/app/storage/workshop',
        'avatar'                  => $_ENV['APP_AVATAR_STORAGE']                  ?? '/app/storage/avatar',
        'news-img'                => $_ENV['APP_NEWS_IMAGE_STORAGE']              ?? '/app/storage/news-img',
        'crash-report-savefile'   => $_ENV['APP_CRASH_REPORT_SAVEFILE_STORAGE']   ?? '/app/storage/crash-report-savefile',
        'alpha-patch'             => $_ENV['APP_ALPHA_PATCH_STORAGE']             ?? '/app/storage/alpha-patch',
        'alpha-patch-file-bundle' => $_ENV['APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE'] ?? '/app/storage/alpha-patch-file-bundle',
        'prototype'               => $_ENV['APP_PROTOTYPE_STORAGE']               ?? '/app/storage/prototype',
        'prototype-file-bundle'   => $_ENV['APP_PROTOTYPE_FILE_BUNDLE_STORAGE']   ?? '/app/storage/prototype-file-bundle',
        'admin-upload'            => $_ENV['APP_ADMIN_UPLOAD_STORAGE']            ?? '/app/storage/admin-upload',
        'game-files'              => $_ENV['APP_GAME_FILE_STORAGE']               ?? '/app/storage/game-files',
        'game-files-file-bundle'  => $_ENV['APP_GAME_FILE_BUNDLE_STORAGE']        ?? '/app/storage/game-files-file-bundle',
        'launcher'                => $_ENV['APP_LAUNCHER_STORAGE']                ?? '/app/storage/launcher',

        'kfx-repo'                => $_ENV['APP_KFX_REPO_STORAGE']                ?? '/app/var/kfx-repo',
        'wiki-repo'               => $_ENV['APP_WIKI_REPO_STORAGE']               ?? '/app/var/wiki-repo',
        'website-repo'            => $_ENV['APP_WEBSITE_REPO_STORAGE']            ?? '/app/var/website-repo',
    ],
];
