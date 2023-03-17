<?php

/**
 * Middlewares
 */
return [

    Slim\Csrf\Guard::class,

    new \RKA\Middleware\IpAddress(),

];
