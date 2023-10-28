<?php

/**
 * Middlewares
 */
return [

    App\Middleware\UserCookieTokenMiddleware::class,
    App\Middleware\MinifyHtmlMiddleware::class,

    new \RKA\Middleware\IpAddress(),

];
