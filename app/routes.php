<?php

namespace App\Controller;

use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use App\Middleware\LoggedInMiddleware;
use App\Middleware\AuthAdminMiddleware;

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////// Application routes
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$app->get('/', [IndexController::class, 'index']);
$app->get('/screenshots', [StaticPageController::class, 'screenshotsIndex']);
$app->get('/changelog/{tag}', [StaticPageController::class, 'changelogIndex']);

$app->get('/downloads', [DownloadController::class, 'downloadsIndex']);
$app->get('/downloads/stable', [DownloadController::class, 'stableDownloadsIndex']);
$app->get('/downloads/alpha', [DownloadController::class, 'alphaDownloadsIndex']);
// TODO: Specific download routes can be created if a download counter is implemented
// $app->get('/download/alpha/{filename}', [DownloadController::class, 'alphaDownload']);
// $app->get('/download/stable/{filename}', [DownloadController::class, 'stableDownload']);

// Wiki
$app->get('/wiki[/{page}]', [WikiController::class, 'wikiPage']);

// Login
$app->get('/login', [LoginController::class, 'loginIndex']);
$app->post('/login', [LoginController::class, 'login']);

// LOGGED IN USERS
$app->group('', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/dashboard', [DashboardController::class, 'dashboardIndex']);
    $group->get('/logout/{token_name:.+}/{token_value:.+}', [AccountController::class, 'logout']);

    // AUTH: ADMIN
    $group->group('/admin', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/news', [Admin\AdminNewsController::class, 'newsIndex']);
        $group->get('/news/add', [Admin\AdminNewsController::class, 'newsAddIndex']);
        $group->post('/news/add', [Admin\AdminNewsController::class, 'newsAdd']);
        $group->get('/news/{id}', [Admin\AdminNewsController::class, 'newsEditIndex']);
        $group->post('/news/{id}', [Admin\AdminNewsController::class, 'newsEdit']);

    })->add(AuthAdminMiddleware::class);

})->add(LoggedInMiddleware::class);


