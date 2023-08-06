<?php

use App\Config\Config;

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

// Create Slim App (with PHP-DI bridge)
$app = \DI\Bridge\Slim\Bridge::create($container);

// Add whoops error handler
if(Config::get('app.whoops.is_enabled') === true){

    // Add Whoops Middleware
    $app->add(new Zeuxisoo\Whoops\Slim\WhoopsMiddleware([
        // Set IDE to open the source file from the error page
        'editor' => Config::get('app.whoops.editor')
    ]));

    // Add Global Whoops handler
    $whoops = new \Whoops\Run;
    $pretty_page_handler = new \Whoops\Handler\PrettyPageHandler();
    $pretty_page_handler->setEditor(Config::get('app.whoops.editor'));
    $whoops->pushHandler($pretty_page_handler);
    $whoops->register();
}

// Add default error handler (for end users)
if(Config::get('app.whoops.is_enabled') === false){
    $errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);
    $errorHandler    = $errorMiddleware->getDefaultErrorHandler();
    // TODO: add json errorcontroller
    $errorHandler->registerErrorRenderer('text/html', App\Controller\Error\HtmlErrorController::class);
}

// Add default body parsing middlewares
// Example: converts 'application/json' POST data
$app->addBodyParsingMiddleware();

// Add App middlewares
foreach ((require APP_ROOT . '/app/middlewares.php') as $middleware_class) {
    // Middleware will be autowired
    $app->add($middleware_class);
}

// Add Session (Compwright\PhpSession) middlewares
\Compwright\PhpSession\Frameworks\Slim\registerSessionMiddleware($app);

// Add routes
require APP_ROOT . '/app/routes.php';

// Start Slim App
$app->run();
