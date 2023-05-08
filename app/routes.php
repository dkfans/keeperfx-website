<?php

namespace App\Controller;

use Slim\Routing\RouteCollectorProxy;

use App\Middleware\LoggedInMiddleware;
use App\Middleware\AuthAdminCPMiddleware;
use App\Middleware\AuthModCPMiddleware;

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

// OAuth - Login + Register + Connect / Disconnect account
$app->get('/oauth/connect/{provider_name}', [OAuthUserController::class, 'connect']);
$app->get('/oauth/connect/{provider_name}/{token_name}/{token_value:.+}', [OAuthUserController::class, 'connect']);
$app->get('/oauth/disconnect/{provider_name}/{token_name}/{token_value:.+}', [OAuthUserController::class, 'disconnect'])->add(LoggedInMiddleware::class);
$app->post('/oauth/register/{provider_name}', [OAuthUserController::class, 'register']);

// Avatar fallback
$app->get('/avatar/{filename:[\w\d\-\.]+}', [AvatarController::class, 'outputAvatar']);

// LOGGED IN USERS
$app->group('', function (RouteCollectorProxy $group) use ($container) {

    $group->get('/dashboard', [ControlPanel\DashboardController::class, 'dashboardIndex']);
    $group->get('/logout/{token_name}/{token_value:.+}', [ControlPanel\AccountController::class, 'logout']);

    // Users: Control Panel
    $group->group('/account', function (RouteCollectorProxy $group) use ($container) {

        // Account settings
        $group->get('', [ControlPanel\AccountController::class, 'accountSettingsIndex']);
        $group->post('/email', [ControlPanel\AccountController::class, 'updateEmail']);
        $group->post('/password', [ControlPanel\AccountController::class, 'updatePassword']);
        $group->post('/avatar', [ControlPanel\AccountController::class, 'updateAvatar']);
        $group->get('/remove-email/{token_name}/{token_value:.+}', [ControlPanel\AccountController::class, 'removeEmail']);
        $group->get('/remove-avatar/{token_name}/{token_value:.+}', [ControlPanel\AccountController::class, 'removeAvatar']);

        // Account Connections
        $group->get('/connections', [ControlPanel\ConnectionController::class, 'index']);
    });

    // AUTH: ADMIN
    $group->group('/admin', function (RouteCollectorProxy $group) use ($container) {

        // Admin: NEWS
        $group->group('/news', function (RouteCollectorProxy $group) use ($container) {
            $group->get('/list', [AdminCP\AdminNewsController::class, 'newsIndex']);
            $group->get('/add', [AdminCP\AdminNewsController::class, 'newsAddIndex']);
            $group->post('/add', [AdminCP\AdminNewsController::class, 'newsAdd']);
            $group->get('/{id:\d+}', [AdminCP\AdminNewsController::class, 'newsEditIndex']);
            $group->post('/{id:\d+}', [AdminCP\AdminNewsController::class, 'newsEdit']);
            $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [AdminCP\AdminNewsController::class, 'newsDelete']);
        });

        // Admin: USERS
        $group->group('/user', function (RouteCollectorProxy $group) use ($container) {
            $group->get('/list', [AdminCP\AdminUsersController::class, 'usersIndex']);
            $group->get('/add', [AdminCP\AdminUsersController::class, 'userAddIndex']);
            $group->post('/add', [AdminCP\AdminUsersController::class, 'userAdd']);
            $group->get('/{id:\d+}', [AdminCP\AdminUsersController::class, 'userEditIndex']);
            $group->post('/{id:\d+}', [AdminCP\AdminUsersController::class, 'userEdit']);
            $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [AdminCP\AdminUsersController::class, 'userDelete']);
        });


        $group->get('/server-info', [AdminCP\AdminServerInfoController::class, 'serverInfoIndex']);

    })->add(AuthAdminCPMiddleware::class);

    // AUTH: MODERATOR
    $group->group('/moderate', function (RouteCollectorProxy $group) use ($container) {

        // Moderate: WORKSHOP
        $group->group('/workshop', function (RouteCollectorProxy $group) use ($container) {
            $group->get('/list', [ModCP\ModerateWorkshopController::class, 'listIndex']);
            $group->get('/add', [ModCP\ModerateWorkshopController::class, 'itemAddIndex']);
            $group->post('/add', [ModCP\ModerateWorkshopController::class, 'itemAdd']);
            $group->get('/{id:\d+}', [ModCP\ModerateWorkshopController::class, 'itemIndex']);
            $group->post('/{id:\d+}', [ModCP\ModerateWorkshopController::class, 'itemUpdate']);
            // $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [ModCP\ModerateWorkshopController::class, 'itemDelete']);
            $group->get('/{id:\d+}/screenshot/delete/{filename}/{token_name}/{token_value:.+}', [ModCP\ModerateWorkshopController::class, 'deleteScreenshot']);
            $group->get('/{id:\d+}/thumbnail/delete/{token_name}/{token_value:.+}', [ModCP\ModerateWorkshopController::class, 'deleteThumbnail']);
        });

    })->add(AuthModCPMiddleware::class);

})->add(LoggedInMiddleware::class);

// Workshop
$app->group('/workshop', function (RouteCollectorProxy $group) use ($container) {

    // Public view
    $group->get('/item/{id:\d+}[/{slug}]', [Workshop\WorkshopItemController::class, 'itemIndex']);

    // Download file
    $group->get('/download/{id:\d+}/{filename}', [Workshop\WorkshopDownloadController::class, 'download']);

    // Image fallbacks
    // These should be served by the webserver
    $group->get('/image/{id:\d+}/{filename}', [Workshop\WorkshopImageController::class, 'outputImage']);

    // Workshop item upload (LOGGED IN)
    $group->get('/upload', [Workshop\WorkshopUploadController::class, 'uploadIndex'])->add(LoggedInMiddleware::class);
    $group->post('/upload', [Workshop\WorkshopUploadController::class, 'upload'])->add(LoggedInMiddleware::class);
    $group->get('/upload/map_number/{map_number:\d+}', [Workshop\WorkshopUploadController::class, 'checkMapNumber'])->add(LoggedInMiddleware::class);

    // Workshop item edit (LOGGED IN)
    $group->get('/edit/{id:\d+}', [Workshop\WorkshopController::class, 'editIndex'])->add(LoggedInMiddleware::class);
    $group->post('/edit/{id:\d+}', [Workshop\WorkshopController::class, 'edit'])->add(LoggedInMiddleware::class);
    $group->get('/edit/{id:\d+}/thumbnail/delete/{token_name}/{token_value:.+}', [Workshop\WorkshopController::class, 'deleteThumbnail'])->add(LoggedInMiddleware::class);
    $group->get('/edit/{id:\d+}/screenshot/delete/{filename}/{token_name}/{token_value:.+}', [Workshop\WorkshopController::class, 'deleteScreenshot']);

    // Workshop item rate
    $group->post('/rate/{id:\d+}/quality', [Workshop\WorkshopRatingController::class, 'rateQuality'])->add(LoggedInMiddleware::class);
    $group->post('/rate/{id:\d+}/difficulty', [Workshop\WorkshopRatingController::class, 'rateDifficulty'])->add(LoggedInMiddleware::class);

    // Workshop item comment
    $group->post('/item/{id:\d+}/comment', [Workshop\WorkshopController::class, 'comment'])->add(LoggedInMiddleware::class);

    // Browse items
    $group->get('/browse', [Workshop\WorkshopBrowseController::class, 'browseIndex']);

    // Random workshop item
    $group->get('/random/{item_category}', [Workshop\WorkshopRandomController::class, 'navRandomItem']);

    // Redirect '/workshop' to '/workshop/browse'
    $group->get('', function (Request $request, Response $response){
        return $response->withStatus(302)->withHeader('Location', '/workshop/browse');
    });
});

// ToS & Privacy Policy
$app->get('/terms-of-service', [InfoPageController::class, 'termsOfServiceIndex']);
$app->get('/privacy-policy', [InfoPageController::class, 'privacyPolicyIndex']);

// RSS
$app->get('/rss-info', [RSSController::class, 'rssInfoIndex']);
$app->group('/rss', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/news', [RSSController::class, 'newsFeed']);
    $group->get('/stable', [RSSController::class, 'stableBuildFeed']);
    $group->get('/alpha', [RSSController::class, 'alphaPatchFeed']);
});
