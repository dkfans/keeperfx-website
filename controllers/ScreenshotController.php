<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ScreenshotController
{

    public function screenshotsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ) {
        $screenshots = [
            [
                'src' => '/screenshots/keeperfx-new-map-scripts.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-new-map-scripts.jpg',
                'width' => 1920,
                'height' => 1080,
                'title' => 'New level script commands',
            ],
            [
                'src' => '/screenshots/keeperfx-customizability.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-customizability.png',
                'width' => 1920,
                'height' => 1080,
                'title' => 'Customizability',
            ],
            [
                'src' => '/screenshots/keeperfx-ctrl-hold-mine.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-ctrl-hold-mine.jpg',
                'width' => 1745,
                'height' => 982,
                'title' => 'Bulk mining',
            ],
            [
                'src' => '/screenshots/keeperfx-drag-rooms.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-drag-rooms.jpg',
                'width' => 1225,
                'height' => 869,
                'title' => 'Place rooms by dragging an area',
            ],
            [
                'src' => '/screenshots/keeperfx-shift-room-creation.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-shift-room-creation.jpg',
                'width' => 1024,
                'height' => 687,
                'title' => 'Smart room placement',
            ],
            [
                'src' => '/screenshots/keeperfx-new-sprites-and-textures.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-new-sprites-and-textures.jpg',
                'width' => 1273,
                'height' => 654,
                'title' => 'Extra textures and sprites',
            ],
            [
                'src' => '/screenshots/keeperfx-higher-render-distance.png',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-higher-render-distance.png',
                'width' => 1609,
                'height' => 943,
                'title' => 'Higher render distance',
            ],
            [
                'src' => '/screenshots/keeperfx-far-zoom.jpg',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-far-zoom.jpg',
                'width' => 1660,
                'height' => 934,
                'title' => 'Far zoom',
            ],
            [
                'src' => '/screenshots/keeperfx-new-hotkeys.jpg',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-new-hotkeys.jpg',
                'width' => 1587,
                'height' => 893,
                'title' => 'New hotkeys',
            ],
            [
                'src' => '/screenshots/keeperfx-multiplayer.jpg',
                'thumbnail_src' => '/screenshots/thumbnail/keeperfx-multiplayer.jpg',
                'width' => 1389,
                'height' => 799,
                'title' => 'Modern multiplayer support',
            ],
        ];

        $response->getBody()->write(
            $twig->render('screenshots.html.twig', [
                'screenshots' => $screenshots
            ])
        );

        return $response;
    }
}
