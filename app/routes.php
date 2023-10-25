<?php

namespace App\Controller;

use Slim\Routing\RouteCollectorProxy;

use App\Middleware\LoggedInMiddleware;
use App\Middleware\AuthAdminCPMiddleware;
use App\Middleware\AuthModCPMiddleware;
use App\Middleware\AuthDevCPMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////// Application routes
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// CSRF middleware group
// This is made a route group so the CSRF Guard Middleware will only be added to the front-end Application
$app->group('', function (RouteCollectorProxy $group) use ($container) {

    $group->get('/', [IndexController::class, 'index']);
    $group->get('/screenshots', [ScreenshotController::class, 'screenshotsIndex']);
    $group->get('/changelog/{tag}', [ChangelogController::class, 'changelogIndex']);

    $group->get('/news', [NewsController::class, 'newsListIndex']);
    $group->get('/news/{id:\d+}[/{date_str}[/{slug}]]', [NewsController::class, 'newsArticleIndex']);

    $group->get('/downloads', [DownloadController::class, 'downloadsIndex']);
    $group->get('/downloads/stable', [DownloadController::class, 'stableDownloadsIndex']);
    $group->get('/downloads/alpha', [DownloadController::class, 'alphaDownloadsIndex']);
    // TODO: Specific download routes can be created if a download counter is implemented
    // $group->get('/download/alpha/{filename}', [DownloadController::class, 'alphaDownload']);
    // $group->get('/download/stable/{filename}', [DownloadController::class, 'stableDownload']);

    // Wiki
    $group->get('/wiki[/{page}]', [WikiController::class, 'wikiPage']);

    // Login
    $group->get('/login', [LoginController::class, 'loginIndex']);
    $group->post('/login', [LoginController::class, 'login']);

    // Register
    $group->get('/register', [RegisterController::class, 'registerIndex']);
    $group->post('/register', [RegisterController::class, 'register']);

    // Password reset
    $group->get('/password-reset', [PasswordResetController::class, 'passwordResetSendIndex']);
    $group->post('/password-reset', [PasswordResetController::class, 'passwordResetSend']);
    $group->get('/password-reset/{token}', [PasswordResetController::class, 'passwordResetIndex']);
    $group->post('/password-reset/{token}', [PasswordResetController::class, 'passwordReset']);

    // OAuth - Login + Register + Connect / Disconnect account
    $group->get('/oauth/connect/{provider_name}', [OAuthUserController::class, 'connect']);
    $group->get('/oauth/connect/{provider_name}/{token_name}/{token_value:.+}', [OAuthUserController::class, 'connect']);
    $group->get('/oauth/disconnect/{provider_name}/{token_name}/{token_value:.+}', [OAuthUserController::class, 'disconnect'])->add(LoggedInMiddleware::class);
    $group->post('/oauth/register/{provider_name}', [OAuthUserController::class, 'register']);

    // Avatar fallback
    $group->get('/avatar/{filename:[\w\d\-\.]+}', [AvatarController::class, 'outputAvatar']);

    // LOGGED IN USERS
    $group->group('', function (RouteCollectorProxy $group) use ($container) {

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

            // Notifications
            $group->get('/notifications', [ControlPanel\NotificationController::class, 'listIndex']);
            $group->get('/notification/{id}', [ControlPanel\NotificationController::class, 'read']);

            // Notification settings
            $group->get('/notifications/settings', [ControlPanel\NotificationController::class, 'settingsIndex']);
            $group->post('/notifications/settings', [ControlPanel\NotificationController::class, 'updateSettings']);
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
                $group->get('/{id:\d+}/mail', [AdminCP\AdminUsersController::class, 'userMailIndex']);
                $group->post('/{id:\d+}/mail', [AdminCP\AdminUsersController::class, 'userMail']);
            });


            $group->get('/server-info', [AdminCP\AdminServerInfoController::class, 'serverInfoIndex']);

        })->add(AuthAdminCPMiddleware::class);

        // AUTH: MODERATOR
        $group->group('/moderate', function (RouteCollectorProxy $group) use ($container) {

            // Moderate: WORKSHOP
            $group->group('/workshop', function (RouteCollectorProxy $group) use ($container) {
                $group->get('/list', [ModCP\Workshop\ModerateWorkshopController::class, 'listIndex']);

                $group->get('/upload', [ModCP\Workshop\ModerateWorkshopUploadController::class, 'index']);
                $group->post('/upload', [ModCP\Workshop\ModerateWorkshopUploadController::class, 'upload']);

                $group->get('/{id:\d+}', [ModCP\Workshop\ModerateWorkshopEditController::class, 'index']);
                $group->post('/{id:\d+}', [ModCP\Workshop\ModerateWorkshopEditController::class, 'edit']);

                $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [ModCP\Workshop\ModerateWorkshopEditController::class, 'delete']);

                $group->get('/{item_id:\d+}/files', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'index']);
                $group->post('/{item_id:\d+}/files', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'upload']);
                $group->get('/{item_id:\d+}/files/{file_id:\d+}/delete/{token_name}/{token_value:.+}', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'delete']);
                $group->get('/{item_id:\d+}/files/{file_id:\d+}/move/{direction}/{token_name}/{token_value:.+}', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'move']);
                $group->post('/{item_id:\d+}/files/{file_id:\d+}/rename', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'rename']);
            });

        })->add(AuthModCPMiddleware::class);

        // AUTH: DEVELOPER
        $group->group('/dev', function (RouteCollectorProxy $group) use ($container) {

            // Moderate (dev) Alpha Patches
            $group->get('/alpha-patches/list', [DevCP\ModerateAlphaPatchController::class, 'listIndex']);
            $group->get('/alpha-patches/{id:\d+}/enable/{token_name}/{token_value:.+}', [DevCP\ModerateAlphaPatchController::class, 'enable']);
            $group->get('/alpha-patches/{id:\d+}/disable/{token_name}/{token_value:.+}', [DevCP\ModerateAlphaPatchController::class, 'disable']);

            // Moderate (dev) Crash Reports
            $group->get('/crash-report/list', [DevCP\ModerateCrashReportController::class, 'listIndex']);
            $group->get('/crash-report/{id:\d+}', [DevCP\ModerateCrashReportController::class, 'view']);
            $group->get('/crash-report/{id:\d+}/delete', [DevCP\ModerateCrashReportController::class, 'delete']);

        })->add(AuthDevCPMiddleware::class);

    })->add(LoggedInMiddleware::class);

    // Workshop
    $group->group('/workshop', function (RouteCollectorProxy $group) use ($container) {

        // Public view
        $group->get('/item/{id:\d+}[/{slug}]', [Workshop\WorkshopItemController::class, 'itemIndex']);

        // Download file
        $group->get('/download/{item_id:\d+}/{file_id:\d+}/{filename}', [Workshop\WorkshopDownloadController::class, 'download']);

        // Image fallbacks
        // These should be served by the webserver
        $group->get('/image/{id:\d+}/{filename}', [Workshop\WorkshopImageController::class, 'outputImage']);

        // Workshop item upload (LOGGED IN)
        $group->get('/upload', [Workshop\WorkshopUploadController::class, 'uploadIndex'])->add(LoggedInMiddleware::class);
        $group->post('/upload', [Workshop\WorkshopUploadController::class, 'upload'])->add(LoggedInMiddleware::class);
        $group->get('/upload/map_number/{map_number:\d+}', [Workshop\WorkshopUploadController::class, 'checkMapNumber'])->add(LoggedInMiddleware::class);

        // Workshop edit (LOGGED IN)
        $group->group('/edit', function (RouteCollectorProxy $group) use ($container) {

            // Workshop item edit
            $group->get('/{id:\d+}', [Workshop\WorkshopEditController::class, 'editIndex']);
            $group->post('/{id:\d+}', [Workshop\WorkshopEditController::class, 'edit']);
            $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [Workshop\WorkshopEditController::class, 'delete']);

            // Workshop file edit
            $group->get('/{item_id:\d+}/files', [Workshop\WorkshopEditFilesController::class, 'index']);
            $group->post('/{item_id:\d+}/files', [Workshop\WorkshopEditFilesController::class, 'upload']);
            $group->get('/{item_id:\d+}/files/{file_id:\d+}/delete/{token_name}/{token_value:.+}', [Workshop\WorkshopEditFilesController::class, 'delete']);
            $group->get('/{item_id:\d+}/files/{file_id:\d+}/move/{direction}/{token_name}/{token_value:.+}', [Workshop\WorkshopEditFilesController::class, 'move']);
            $group->post('/{item_id:\d+}/files/{file_id:\d+}/rename', [Workshop\WorkshopEditFilesController::class, 'rename']);

        })->add(LoggedInMiddleware::class);

        // Workshop item rate (Ajax)
        $group->post('/rate/{id:\d+}/quality', [Workshop\WorkshopRatingController::class, 'rateQuality'])->add(LoggedInMiddleware::class);
        $group->post('/rate/{id:\d+}/difficulty', [Workshop\WorkshopRatingController::class, 'rateDifficulty'])->add(LoggedInMiddleware::class);
        $group->post('/rate/{id:\d+}/quality/remove', [Workshop\WorkshopRatingController::class, 'removeQualityRating'])->add(LoggedInMiddleware::class);
        $group->post('/rate/{id:\d+}/difficulty/remove', [Workshop\WorkshopRatingController::class, 'removeDifficultyRating'])->add(LoggedInMiddleware::class);

        // My Ratings
        $group->get('/my-ratings', [Workshop\WorkshopRatingController::class, 'myRatingsIndex'])->add(LoggedInMiddleware::class);

        // Workshop item comment
        $group->post('/item/{id:\d+}/comment', [Workshop\WorkshopCommentController::class, 'comment'])->add(LoggedInMiddleware::class);
        $group->post('/item/{item_id:\d+}/comment/{comment_id:\d+}', [Workshop\WorkshopCommentController::class, 'replyComment'])->add(LoggedInMiddleware::class); // AJAX
        $group->put('/item/{item_id:\d+}/comment/{comment_id:\d+}', [Workshop\WorkshopCommentController::class, 'updateComment'])->add(LoggedInMiddleware::class); // AJAX
        $group->delete('/item/{item_id:\d+}/comment/{comment_id:\d+}', [Workshop\WorkshopCommentController::class, 'deleteComment'])->add(LoggedInMiddleware::class); // AJAX

        // Browse items
        $group->get('/browse', [Workshop\WorkshopBrowseController::class, 'browseIndex']);

        // Random workshop item
        $group->get('/random/{item_category}', [Workshop\WorkshopRandomController::class, 'navRandomItem']);

        // Mapnumber lists
        $group->get('/map_number/list/map', [Workshop\WorkshopMapNumberListController::class, 'mapListIndex']);

        // Redirect '/workshop' to '/workshop/browse'
        $group->redirect('[/]', '/workshop/browse', 302);

        // Workshop report
        $group->group('/report', function (RouteCollectorProxy $group) use ($container) {

            // Comment report
            $group->post('/comment/{comment_id:\d+}', [Workshop\WorkshopReportController::class, 'reportComment']); // AJAX
            $group->delete('/comment/{report_id:\d+}', [Workshop\WorkshopReportController::class, 'removeCommentReport'])->add(AuthModCPMiddleware::class); // AJAX

        })->add(LoggedInMiddleware::class);
    });

    // ToS & Privacy Policy
    $group->get('/terms-of-service', [InfoPageController::class, 'termsOfServiceIndex']);
    $group->get('/privacy-policy', [InfoPageController::class, 'privacyPolicyIndex']);

    // RSS
    $group->get('/rss-info', [RSSController::class, 'rssInfoIndex']);
    $group->group('/rss', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/news', [RSSController::class, 'newsFeed']);
        $group->get('/stable', [RSSController::class, 'stableBuildFeed']);
        $group->get('/alpha', [RSSController::class, 'alphaPatchFeed']);
    });

    // History
    $group->get('/history', [HistoryController::class, 'index']);

})->add(\Slim\Csrf\Guard::class);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////// API
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$app->group('/api', function (RouteCollectorProxy $group) use ($container) {

    // API: News
    $group->get('/v1/news/latest', [Api\v1\NewsApiController::class, 'listLatest']);

    // API: Workshop
    $group->get('/v1/workshop/latest', [Api\v1\Workshop\WorkshopBrowseApiController::class, 'listLatest']);
    $group->get('/v1/workshop/item/{id:\d+}', [Api\v1\Workshop\WorkshopItemApiController::class, 'getItem']);
    $group->get('/v1/workshop/comment/{id:\d+}', [Api\v1\Workshop\WorkshopItemApiController::class, 'getComment']);

    // API: Downloads
    $group->get('/v1/stable/latest', [Api\v1\ReleaseApiController::class, 'latestStable']);
    $group->get('/v1/alpha/latest', [Api\v1\ReleaseApiController::class, 'latestAlpha']);

    // API: Crash Report
    $group->post('/v1/crash-report', [Api\v1\CrashReportApiController::class, 'upload']);

});
