<?php

/**
 * Middlewares
 */
return [

    App\Middleware\UserCookieTokenMiddleware::class,

    Slim\Csrf\Guard::class,

    new \RKA\Middleware\IpAddress(),

];
