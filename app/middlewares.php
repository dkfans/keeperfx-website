<?php

/**
 * Middlewares
 *
 * NOTE: Slim Router executes these in reverse order (bottom to top)
 */
return [

    App\Middleware\LinkHeaderMiddleware::class,

    App\Middleware\UserCookieTokenMiddleware::class,
    App\Middleware\MinifyHtmlMiddleware::class,

    App\Middleware\UserIpChangeLoggerMiddleware::class,

];
