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
                        'formatted' => ($upload_size_helper->getFinalAvatarUploadSize() > 0) ?
                            BinaryFormatter::bytes($upload_size_helper->getFinalAvatarUploadSize())->format() :
                            'N/A'
                    ],
                    'workshop_item' => [
                        'size'      => $upload_size_helper->getFinalWorkshopItemUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalWorkshopItemUploadSize() > 0) ?
                            BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopItemUploadSize())->format() :
                            'N/A'
                    ],
                    'workshop_image' => [
                        'size'      => $upload_size_helper->getFinalWorkshopImageUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalWorkshopImageUploadSize() > 0) ?
                            BinaryFormatter::bytes($upload_size_helper->getFinalWorkshopImageUploadSize())->format() :
                            'N/A'
                    ],
                    'total' => [
                        'size'      => $upload_size_helper->getMaxCalculatedTotalUpload(),
                        'formatted' => ($upload_size_helper->getMaxCalculatedTotalUpload() > 0) ?
                            BinaryFormatter::bytes($upload_size_helper->getMaxCalculatedTotalUpload())->format() :
                            'N/A'
                    ],
                    'file' => [
                        'size'      => $upload_size_helper->getMaxCalculatedFileUpload(),
                        'formatted' => ($upload_size_helper->getMaxCalculatedFileUpload() > 0) ?
                            BinaryFormatter::bytes($upload_size_helper->getMaxCalculatedFileUpload())->format() :
                            'N/A'
                    ],
                    'news_image' => [
                        'size'      => $upload_size_helper->getFinalNewsImageUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalNewsImageUploadSize() > 0) ?
                            BinaryFormatter::bytes($upload_size_helper->getFinalNewsImageUploadSize())->format() :
                            'N/A'
                    ],
                ]
            ]
        ];
    }
}
