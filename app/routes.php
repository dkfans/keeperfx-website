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
$app->get('/screenshots', [ScreenshotController::class, 'screenshotsIndex']);
$app->get('/changelog/{tag}', [ChangelogController::class, 'changelogIndex']);
$app->get('/news/{id:\d+}[/{date_str}[/{slug}]]', [NewsController::class, 'newsArticleIndex']);

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

// Register
$app->get('/register', [RegisterController::class, 'registerIndex']);
$app->post('/register', [RegisterController::class, 'register']);

// LOGGED IN USERS
$app->group('', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/dashboard', [DashboardController::class, 'dashboardIndex']);
    $group->get('/logout/{token_name}/{token_value:.+}', [AccountController::class, 'logout']);

    // AUTH: ADMIN
    $group->group('/admin', function (RouteCollectorProxy $group) use ($container) {

    // Admin: NEWS
    $group->group('/news', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/list', [Admin\AdminNewsController::class, 'newsIndex']);
        $group->get('/add', [Admin\AdminNewsController::class, 'newsAddIndex']);
        $group->post('/add', [Admin\AdminNewsController::class, 'newsAdd']);
        $group->get('/{id:\d+}', [Admin\AdminNewsController::class, 'newsEditIndex']);
        $group->post('/{id:\d+}', [Admin\AdminNewsController::class, 'newsEdit']);
        $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [Admin\AdminNewsController::class, 'newsDelete']);
    });

    // Admin: USERS
    $group->group('/user', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/list', [Admin\AdminUsersController::class, 'usersIndex']);
        $group->get('/add', [Admin\AdminUsersController::class, 'userAddIndex']);
        $group->post('/add', [Admin\AdminUsersController::class, 'userAdd']);
        $group->get('/{id:\d+}', [Admin\AdminUsersController::class, 'userEditIndex']);
        $group->post('/{id:\d+}', [Admin\AdminUsersController::class, 'userEdit']);
        $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [Admin\AdminUsersController::class, 'userDelete']);
    });

    // Admin: WORKSHOP
    $group->group('/workshop', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/list', [Admin\AdminWorkshopController::class, 'listIndex']);
        $group->get('/{id:\d+}', [Admin\AdminWorkshopController::class, 'itemIndex']);
        // $group->post('/{id:\d+}', [Admin\AdminUsersController::class, 'userEdit']);
        // $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [Admin\AdminUsersController::class, 'userDelete']);
    });


    })->add(AuthAdminMiddleware::class);

})->add(LoggedInMiddleware::class);

// Workshop
$app->group('/workshop', function (RouteCollectorProxy $group) use ($container) {
    $group->get('', [WorkshopController::class, 'workshopIndex']);
    $group->get('/item/{id:\d+}', [WorkshopController::class, 'itemIndex']);
    $group->get('/download/{id:\d+}/{filename}', [WorkshopController::class, 'download']);
    // Workshop submit (LOGGED IN)
    $group->get('/submit', [WorkshopController::class, 'submitIndex'])->add(LoggedInMiddleware::class);
    $group->post('/submit', [WorkshopController::class, 'submit'])->add(LoggedInMiddleware::class);
});

// RSS
$app->get('/rss-info', [RSSController::class, 'rssInfoIndex']);
$app->group('/rss', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/news', [RSSController::class, 'newsFeed']);
    $group->get('/stable', [RSSController::class, 'stableBuildFeed']);
    $group->get('/alpha', [RSSController::class, 'alphaPatchFeed']);
});
