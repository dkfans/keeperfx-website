<?php

namespace App\Controller;

use Slim\Routing\RouteCollectorProxy;

use App\Middleware\LoggedInMiddleware;
use App\Middleware\AuthAdminMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
        $group->post('/{id:\d+}', [Admin\AdminWorkshopController::class, 'itemUpdate']);
        // $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [Admin\AdminWorkshopController::class, 'userDelete']);
        $group->get('/screenshot/delete/{id:\d+}/{filename}/{token_name}/{token_value:.+}', [Admin\AdminWorkshopController::class, 'deleteScreenshot']);
    });

    })->add(AuthAdminMiddleware::class);

})->add(LoggedInMiddleware::class);

// Workshop
$app->group('/workshop', function (RouteCollectorProxy $group) use ($container) {

    // Public view and download
    $group->get('/item/{id:\d+}[/{slug}]', [WorkshopController::class, 'itemIndex']);
    $group->get('/download/{id:\d+}/{filename}', [WorkshopController::class, 'download']);

    // Screenshot & thumbnail fallbacks
    // These should be served by the webserver
    $group->get('/screenshot/{id:\d+}/{filename}', [WorkshopController::class, 'outputScreenshot']);
    $group->get('/thumbnail/{id:\d+}/{filename}', [WorkshopController::class, 'outputThumbnail']);

    // Workshop item upload (LOGGED IN)
    $group->get('/upload', [WorkshopController::class, 'uploadIndex'])->add(LoggedInMiddleware::class);
    $group->post('/upload', [WorkshopController::class, 'upload'])->add(LoggedInMiddleware::class);

    // Workshop item edit (LOGGED IN)
    $group->get('/edit/{id:\d+}', [WorkshopController::class, 'editIndex'])->add(LoggedInMiddleware::class);
    $group->post('/edit/{id:\d+}', [WorkshopController::class, 'edit'])->add(LoggedInMiddleware::class);

    // Workshop item rate
    $group->post('/rate/{id:\d+}', [WorkshopController::class, 'rate'])->add(LoggedInMiddleware::class);

    // Browse routes
    $group->group('/browse', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/latest', [WorkshopBrowseController::class, 'browseLatestIndex']);
        // $group->get('/most-downloaded', [WorkshopBrowseController::class, 'browseLatestIndex']);
        // $group->get('/highest-rated', [WorkshopBrowseController::class, 'browseLatestIndex']);
        // $group->get('/staff-picks', [WorkshopBrowseController::class, 'browseLatestIndex']);
    });

    // Redirect '/workshop' to '/workshop/browse/latest'
    $group->get('', function (Request $request, Response $response){
        return $response->withStatus(302)->withHeader('Location', '/workshop/browse/latest');
    });
});

// RSS
$app->get('/rss-info', [RSSController::class, 'rssInfoIndex']);
$app->group('/rss', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/news', [RSSController::class, 'newsFeed']);
    $group->get('/stable', [RSSController::class, 'stableBuildFeed']);
    $group->get('/alpha', [RSSController::class, 'alphaPatchFeed']);
});
