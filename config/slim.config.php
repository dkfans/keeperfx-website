<?php

return [

    /**
     * 'Link:' headers
     *
     * These are used to preconnect to domains that serve assets to improve performance.
     */
    'link_headers' => [
        'https://cdnjs.cloudflare.com' => ['rel' => 'preconnect'],
        'https://img.shields.io'       => ['rel' => 'preconnect'],
    ]
];
