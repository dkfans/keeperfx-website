<?php

/**
 * Middlewares
 *
 * NOTE: Slim Router executes these in reverse order (bottom to top)
 */
return [

    App\Middleware\UserCookieTokenMiddleware::class,
    App\Middleware\MinifyHtmlMiddleware::class,

    new \RKA\Middleware\IpAddress(),

];
