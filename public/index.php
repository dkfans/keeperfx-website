<?php

use App\Config\Config;
use App\Middleware\ErrorMiddleware;

// Check for maintenance mode and show notice
// This should be the very first check that is ran.
// That way every part of the application can update without an end user executing any code.
if(\file_exists(__DIR__ . '/../__MAINTENANCE_MODE_ACTIVE')){
    \header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    \header('Pragma: no-cache');
    $content_type = $_SERVER["CONTENT_TYPE"] ?? '';
    if($content_type == 'application/json') {
        \header('Content-Type: application/json');
        require 'maintenance.json';
    } else {
        require 'maintenance.html';
    }
    exit;
}

// Bootstrap application
require __DIR__ . '/../app/bootstrap/bootstrap.php';

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

// Create Slim App (with PHP-DI bridge)
$app = \DI\Bridge\Slim\Bridge::create($container);

// Add Global Whoops handler
if(Config::get('app.whoops.is_enabled') === true){
    $whoops = new \Whoops\Run;
    $pretty_page_handler = new \Whoops\Handler\PrettyPageHandler();
    $pretty_page_handler->setEditor(Config::get('app.whoops.editor'));
    $whoops->pushHandler($pretty_page_handler);
    $whoops->register();
}

// Add default body parsing middlewares
// Example: converts 'application/json' POST data
$app->addBodyParsingMiddleware();

// Add App middlewares
foreach ((require APP_ROOT . '/app/middlewares.php') as $middleware_class) {
    // Middleware will be autowired
    $app->add($middleware_class);
}

// Add default error handler (for end users)
if(Config::get('app.whoops.is_enabled') === false){
    $app->add(ErrorMiddleware::class);
}

// Add Whoops Middleware
if(Config::get('app.whoops.is_enabled') === true){
    $app->add(new Zeuxisoo\Whoops\Slim\WhoopsMiddleware([
        // Set IDE to open the source file from the error page
        'editor' => Config::get('app.whoops.editor')
    ]));
}

// Add debug bar collectors
// The Twig collector is found in the container definition (otherwise it will try to load the session before the request middlewares)
if($_ENV['APP_ENV'] === 'dev'){

    // Get the debugbar
    $debugbar = $container->get(\DebugBar\StandardDebugBar::class);

    // Monolog collector
    $debugbar->addCollector(new DebugBar\Bridge\MonologCollector($logger));

    // Doctrine collector
    $em = $container->get(\Doctrine\ORM\EntityManager::class);
    $debug_stack = new Doctrine\DBAL\Logging\DebugStack();
    $em->getConnection()->getConfiguration()->setSQLLogger($debug_stack);
    $debugbar->addCollector(new \DebugBar\Bridge\DoctrineCollector($debug_stack));
}

// Add Session (Compwright\PhpSession) middlewares.
// We need to add these last because the middlewares are executed in reverse order. (Slim router)
// The order here is really important.
// $app->add(\Compwright\PhpSession\Middleware\SessionCacheControlMiddleware::class);
$app->add(\Compwright\PhpSession\Middleware\SessionMiddleware::class);
$app->add(\Compwright\PhpSession\Middleware\SessionCookieMiddleware::class);
$app->add(\Compwright\PhpSession\Middleware\SessionBeforeMiddleware::class);

// Add routes
require APP_ROOT . '/app/routes.php';

// Enable Slim route caching
if($_ENV['APP_ENV'] === 'prod'){
    $routeCollector = $app->getRouteCollector();
    $routeCollector->setCacheFile(APP_ROOT . '/cache/router.cache');
}

// Start Slim App
$app->run();
