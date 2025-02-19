<?php

use App\Config\Config;
use App\Kernel\ContainerFactory;
use App\Kernel\ErrorLogger;

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;

use Psr\Log\LoggerInterface;

/**
 * App bootstrap file
 *
 * This file loads the application.
 * No HTTP parts are loaded yet. These are setup in: `/public/index.php`.
 * Every tool and endpoint should load this file and utilize the application.
 */

// Set app project root
define('APP_ROOT', \realpath(__DIR__ . '/../../'));

// Load composer libraries
require APP_ROOT . '/vendor/autoload.php';

// Load .env
Dotenv::createImmutable(APP_ROOT)->safeLoad();

// Load app config
Config::loadDir(APP_ROOT . '/config');

// Custom error handler to convert warnings and notices into exceptions
if(!empty($_ENV['APP_RAISE_EXCEPTION_ON_WARNING']) && (int)$_ENV['APP_RAISE_EXCEPTION_ON_WARNING'] === 1){
    \set_error_handler(function ($severity, $message, $file, $line) {
        if (!(\error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
}

// Prepare the storage
// This should also work for docker volumes
foreach(Config::get('storage.path') as $storage_name => $storage_path){
    if(!empty($storage_path)){
        try {
            \Xenokore\Utility\Helper\DirectoryHelper::createIfNotExist($storage_path, 0777, true);
        } catch (\Exception $ex){}
    }
}

// Create logger
$logger = new Logger('app');
foreach(Config::get('logger.logs') as $log){
    if($log['is_enabled']){
        $logger->pushHandler(new StreamHandler($log['path'], $log['level']));
    }
    unset($log);
}

// Register the logger
// Makes sure all warnings/errors/etc.. are logged
// ErrorLogger::register($logger);

// Create DI container
$container = ContainerFactory::create(Config::get('container'), [
    LoggerInterface::class => $logger,
    'logger'               => $logger,
]);
