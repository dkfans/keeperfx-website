<?php

namespace App\Twig;

use App\UploadSizeHelper;
use ByteUnits\Binary as BinaryFormatter;

use Psr\Container\ContainerInterface;

class TwigGlobalProvider {

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getGlobals()
    {
        // Get current git commit hash of website project
        $web_git_hash_short = null;
        $web_git_hash_long  = null;
        $web_git_hash_file  = __DIR__ . '/../../.git/refs/heads/master';
        if(\file_exists($web_git_hash_file) && \is_readable($web_git_hash_file)){
            $web_git_hash_long = \file_get_contents($web_git_hash_file);
            $web_git_hash_short      = \substr($web_git_hash_long, 0, 7);
        }

        $upload_size_helper = $this->container->get(UploadSizeHelper::class);

        return [
            'globals' => [
                'web_git_hash' => [
                    'short' => $web_git_hash_short,
                    'long'  => $web_git_hash_long,
                ],
                'upload_limit' => [
                    'avatar' => [
                        'size'      => $upload_size_helper->getFinalAvatarUploadSize(),
                        'formatted' => BinaryFormatter::bytes($upload_size_helper->getFinalAvatarUploadSize())->format()
                    ],
                    'workshop_item' => [
                        'size'      => $upload_size_helper->getFinalWorkshopItemUploadSize(),
                        'formatted' => BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopItemUploadSize())->format()
                    ],
                    'workshop_image' => [
                        'size'      => $upload_size_helper->getFinalWorkshopImageUploadSize(),
                        'formatted' => BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopImageUploadSize())->format()
                    ],
                    'total' => [
                        'size'      => $upload_size_helper->getMaxCalculatedTotalUpload(),
                        'formatted' => BinaryFormatter::bytes($upload_size_helper->getMaxCalculatedTotalUpload())->format()
                    ],
                ]
            ]
        ];
    }
}
