<?php

namespace App\Kernel;

use Monolog\ErrorHandler;

use Psr\Log\LoggerInterface;

class ErrorLogger {

    public static function register(LoggerInterface $logger)
    {
        $handler = new ErrorHandler($logger);
        $handler->registerErrorHandler([], true);
        $handler->registerExceptionHandler();
        $handler->registerFatalHandler();
    }

}
