<?php

namespace App\Twig;

use App\CDN;
use App\UploadSizeHelper;
use Psr\Container\ContainerInterface;

class TwigGlobalProvider
{
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
                'cdn' => [
                    'id'        => $this->container->get(CDN::class)->getCurrentId(),
                    'base_url'  => $this->container->get(CDN::class)->getBaseUrl(),
                    'endpoints' => $this->container->get(CDN::class)->getAll(),
                ],
                'upload_limit' => [
                    'avatar' => [
                        'size'      => $upload_size_helper->getFinalAvatarUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalAvatarUploadSize() > 0) ?
                            \App\Helper\FormatHelper::formatBytes($upload_size_helper->getFinalAvatarUploadSize()) :
                            'N/A',
                    ],
                    'workshop_item' => [
                        'size'      => $upload_size_helper->getFinalWorkshopItemUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalWorkshopItemUploadSize() > 0) ?
                            \App\Helper\FormatHelper::formatBytes($upload_size_helper->getFinalWorkshopItemUploadSize()) :
                            'N/A',
                    ],
                    'workshop_image' => [
                        'size'      => $upload_size_helper->getFinalWorkshopImageUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalWorkshopImageUploadSize() > 0) ?
                            \App\Helper\FormatHelper::formatBytes($upload_size_helper->getFinalWorkshopImageUploadSize()) :
                            'N/A',
                    ],
                    'total' => [
                        'size'      => $upload_size_helper->getMaxCalculatedTotalUpload(),
                        'formatted' => ($upload_size_helper->getMaxCalculatedTotalUpload() > 0) ?
                            \App\Helper\FormatHelper::formatBytes($upload_size_helper->getMaxCalculatedTotalUpload()) :
                            'N/A',
                    ],
                    'file' => [
                        'size'      => $upload_size_helper->getMaxCalculatedFileUpload(),
                        'formatted' => ($upload_size_helper->getMaxCalculatedFileUpload() > 0) ?
                            \App\Helper\FormatHelper::formatBytes($upload_size_helper->getMaxCalculatedFileUpload()) :
                            'N/A',
                    ],
                    'news_image' => [
                        'size'      => $upload_size_helper->getFinalNewsImageUploadSize(),
                        'formatted' => ($upload_size_helper->getFinalNewsImageUploadSize() > 0) ?
                            \App\Helper\FormatHelper::formatBytes($upload_size_helper->getFinalNewsImageUploadSize()) :
                            'N/A',
                    ],
                ],
            ],
        ];
    }
}
