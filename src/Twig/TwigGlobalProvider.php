<?php

namespace App\Twig;

use App\UploadSizeHelper;

use Psr\Container\ContainerInterface;

class TwigGlobalProvider {

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getGlobals()
    {
        return [
            'globals' => [
                'avatar_max_upload_size'   => $this->container->get(UploadSizeHelper::class)->getFinalAvatarUploadSize(),
                'workshop_max_upload_size' => $this->container->get(UploadSizeHelper::class)->getFinalWorkshopUploadSize(),
            ]
        ];
    }
}
