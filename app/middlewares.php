<?php

/**
 * Middlewares
 */
return [

    App\Middleware\UserCookieTokenMiddleware::class,

    new \RKA\Middleware\IpAddress(),

];
