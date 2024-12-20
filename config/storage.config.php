<?php

return [
    'path' => [
        'workshop'                      => $_ENV['APP_WORKSHOP_STORAGE'] ?? null,
        'workshop-cli'                  => $_ENV['APP_WORKSHOP_STORAGE_CLI_PATH'] ?? null,

        'avatar'                        => $_ENV['APP_AVATAR_STORAGE'] ?? null,
        'avatar-cli'                    => $_ENV['APP_AVATAR_STORAGE_CLI_PATH'] ?? null,

        'news-img'                      => $_ENV['APP_NEWS_IMAGE_STORAGE'] ?? null,

        'crash-report-savefile'         => $_ENV['APP_CRASH_REPORT_SAVEFILE_STORAGE'] ?? null,

        'alpha-patch'                   => $_ENV['APP_ALPHA_PATCH_STORAGE'] ?? null,
        'alpha-patch-cli'               => $_ENV['APP_ALPHA_PATCH_STORAGE_CLI_PATH'] ?? null,
        'alpha-patch-file-bundle'       => $_ENV['APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE'] ?? null,
        'alpha-patch-file-bundle-cli'   => $_ENV['APP_ALPHA_PATCH_FILE_BUNDLE_STORAGE_CLI_PATH'] ?? null,

        'prototype'                     => $_ENV['APP_PROTOTYPE_STORAGE'] ?? null,
        'prototype-cli'                 => $_ENV['APP_PROTOTYPE_STORAGE_CLI_PATH'] ?? null,
        'prototype-file-bundle'         => $_ENV['APP_PROTOTYPE_FILE_BUNDLE_STORAGE'] ?? null,
        'prototype-file-bundle-cli'     => $_ENV['APP_PROTOTYPE_FILE_BUNDLE_STORAGE_CLI_PATH'] ?? null,

        'kfx-repo'                      => $_ENV['APP_KFX_REPO_STORAGE'] ?? null,
        'wiki-repo'                     => $_ENV['APP_WIKI_REPO_STORAGE'] ?? null,

        'admin-upload'                  => $_ENV['APP_ADMIN_UPLOAD_STORAGE'] ?? null,
        'admin-upload-cli'              => $_ENV['APP_ADMIN_UPLOAD_STORAGE_CLI_PATH'] ?? null,
    ],
];
