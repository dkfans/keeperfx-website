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
        $upload_size_helper = $this->container->get(UploadSizeHelper::class);

        return [
            'globals' => [
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
