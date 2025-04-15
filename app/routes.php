<?php

namespace App\Controller;

use Slim\Routing\RouteCollectorProxy;

use App\Middleware\LoggedInMiddleware;
use App\Middleware\AuthAdminCPMiddleware;
use App\Middleware\AuthModCPMiddleware;
use App\Middleware\AuthDevCPMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/** @var \Slim\App $app */

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////// Application routes
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// CSRF middleware group
// This is made a route group so the CSRF Guard Middleware will only be added to the front-end Application
$app->group('', function (RouteCollectorProxy $group) use ($container) {

    $group->get('/', [IndexController::class, 'index']);
    $group->get('/screenshots', [ScreenshotController::class, 'screenshotsIndex']);
    $group->get('/commits/{tag}', [CommitsController::class, 'commitsIndex']);

    $group->get('/news', [NewsController::class, 'newsListIndex']);
    $group->get('/news/image/{filename}', [NewsController::class, 'outputNewsImage']);
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

    // Avatar fallback + generator
    $group->get('/avatar/{filename:[\w\d\-\.]+}', [AvatarController::class, 'outputAvatar']);
    $group->get('/avatar-generate/{size:\d+}/{username}.png', [AvatarController::class, 'generateAvatarPng']);

    // Public download route for admin uploads
    $group->get('/uploads/{filename:[\w\d\(\)\_\-\.]+}', [UploadController::class, 'outputFile']);

    // Verify email address
    $group->get('/verify-email/{user_id:\d+}/{token}', [EmailVerificationController::class, 'verify']);

    // Send email Ajax endpoint
    $group->get('/email/send/{id:\d+}', [EmailController::class, 'sendEmail']);

    // Debug Bar assets
    $group->get('/assets/debugbar/{path:.+}', [DebugBarAssetController::class, 'outputAsset']);

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
            $group->post('/country', [ControlPanel\AccountController::class, 'updateCountry']);
            $group->post('/about-me', [ControlPanel\AccountController::class, 'updateAboutMe']);
            $group->post('/theme', [ControlPanel\AccountController::class, 'updateTheme']);
            $group->get('/remove-email/{token_name}/{token_value:.+}', [ControlPanel\AccountController::class, 'removeEmail']);
            $group->get('/remove-avatar/{token_name}/{token_value:.+}', [ControlPanel\AccountController::class, 'removeAvatar']);
            $group->get('/resend-verification-email/{token_name}/{token_value:.+}', [ControlPanel\AccountController::class, 'resendVerificationEmail']);

            // Account Connections
            $group->get('/connections', [ControlPanel\ConnectionController::class, 'index']);

            // Notifications
            $group->get('/notifications', [ControlPanel\NotificationController::class, 'listIndex']);
            $group->get('/notification/{id}', [ControlPanel\NotificationController::class, 'read']);

            // Notification settings
            $group->get('/notifications/settings', [ControlPanel\NotificationController::class, 'settingsIndex']);
            $group->post('/notifications/settings', [ControlPanel\NotificationController::class, 'updateSettings']);

            // Mark all notifications as read
            $group->get('/notifications/mark-all-as-read/{token_name}/{token_value:.+}', [ControlPanel\NotificationController::class, 'markAllAsRead']);
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
                $group->get('/{id:\d+}/delete-image/{token_name}/{token_value:.+}', [AdminCP\AdminNewsController::class, 'imageDelete']);
            });

            // Admin: USERS
            $group->group('/user', function (RouteCollectorProxy $group) use ($container) {
                $group->get('/list', [AdminCP\AdminUsersController::class, 'usersIndex']);
                $group->get('/add', [AdminCP\AdminUsersController::class, 'userAddIndex']);
                $group->post('/add', [AdminCP\AdminUsersController::class, 'userAdd']);
                $group->get('/{id:\d+}', [AdminCP\AdminUsersController::class, 'userViewIndex']);
                $group->post('/{id:\d+}', [AdminCP\AdminUsersController::class, 'userEdit']);
                $group->post('/{id:\d+}/bio', [AdminCP\AdminUsersController::class, 'userBioEdit']);
                $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [AdminCP\AdminUsersController::class, 'userDelete']);
                $group->get('/{id:\d+}/mail', [AdminCP\AdminUsersController::class, 'userMailIndex']);
                $group->post('/{id:\d+}/mail', [AdminCP\AdminUsersController::class, 'userMail']);
            });

            // Admin: UPLOADS
            $group->group('/uploads', function (RouteCollectorProxy $group) use ($container) {
                $group->get('', [AdminCP\AdminUploadController::class, 'uploadIndex']);
                $group->post('/upload', [AdminCP\AdminUploadController::class, 'upload']);
                $group->get('/{filename}/delete/{token_name}/{token_value:.+}', [AdminCP\AdminUploadController::class, 'delete']);
            });

            // Admin: RELEASES
            $group->group('/releases', function (RouteCollectorProxy $group) use ($container) {
                $group->get('/list', [AdminCP\AdminReleasesController::class, 'releasesIndex']);
                $group->get('/{id:\d+}', [AdminCP\AdminReleasesController::class, 'releaseEditIndex']);
                $group->post('/{id:\d+}', [AdminCP\AdminReleasesController::class, 'releaseEdit']);
            });

            // Admin: IP LOOKUP
            $group->group('/ip-lookup', function (RouteCollectorProxy $group) use ($container) {
                $group->get('/logs', [AdminCP\AdminIpLookupController::class, 'logsIndex']);
                $group->get('/associations', [AdminCP\AdminIpLookupController::class, 'associationsIndex']);
                $group->get('/{type}/{string:.+}', [AdminCP\AdminIpLookupController::class, 'lookup']);
            });

            // Admin: BANS
            $group->group('/ban', function (RouteCollectorProxy $group) use ($container) {
                $group->get('/list', [AdminCP\AdminBanController::class, 'bansIndex']);
                $group->get('/add', [AdminCP\AdminBanController::class, 'banAddIndex']);
                $group->post('/add', [AdminCP\AdminBanController::class, 'banAdd']);
                $group->get('/{id:\d+}', [AdminCP\AdminBanController::class, 'banEditIndex']);
                $group->post('/{id:\d+}', [AdminCP\AdminBanController::class, 'banEdit']);
                $group->get('/{id:\d+}/delete/{token_name}/{token_value:.+}', [AdminCP\AdminBanController::class, 'banDelete']);
            });

            // Server info
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
                $group->get('/{item_id:\d+}/files/{file_id:\d+}/mark_as_broken/{token_name}/{token_value:.+}', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'mark_as_broken']);
                $group->get('/{item_id:\d+}/files/{file_id:\d+}/unmark_as_broken/{token_name}/{token_value:.+}', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'unmark_as_broken']);
                $group->get('/{item_id:\d+}/files/{file_id:\d+}/move/{direction}/{token_name}/{token_value:.+}', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'move']);
                $group->post('/{item_id:\d+}/files/{file_id:\d+}/rename', [ModCP\Workshop\ModerateWorkshopEditFilesController::class, 'rename']);
            });

            // Moderate WORKSHOP comments
            $group->get('/workshop-comments/list', [ModCP\Workshop\ModerateWorkshopCommentController::class, 'listIndex']);

        })->add(AuthModCPMiddleware::class);

        // AUTH: DEVELOPER
        $group->group('/dev', function (RouteCollectorProxy $group) use ($container) {

            // Moderate (dev) Alpha Patches
            $group->get('/alpha-patches/list', [DevCP\ModerateAlphaPatchController::class, 'listIndex']);
            $group->get('/alpha-patches/{id:\d+}/enable/{token_name}/{token_value:.+}', [DevCP\ModerateAlphaPatchController::class, 'enable']);
            $group->get('/alpha-patches/{id:\d+}/disable/{token_name}/{token_value:.+}', [DevCP\ModerateAlphaPatchController::class, 'disable']);
            $group->put('/alpha-patches/{id}', [DevCP\ModerateAlphaPatchController::class, 'edit']);

            // Moderate (dev) Prototypes
            $group->get('/prototypes/list', [DevCP\ModeratePrototypeController::class, 'listIndex']);

            // Moderate (dev) Launcher
            $group->get('/launcher/list', [DevCP\ModerateLauncherController::class, 'listIndex']);

            // Moderate (dev) Crash Reports
            $group->get('/crash-report/list', [DevCP\ModerateCrashReportController::class, 'listIndex']);
            $group->get('/crash-report/{id:\d+}', [DevCP\ModerateCrashReportController::class, 'view']);
            $group->get('/crash-report/{id:\d+}/delete', [DevCP\ModerateCrashReportController::class, 'delete']);

            // Bundled Assets
            $group->get('/bundled-assets', [DevCP\ModerateBundledAssetsController::class, 'index']);

            // Game Files Maps
            $group->get('/game-files/list', [DevCP\ModerateGameFilesController::class, 'listIndex']);
            $group->get('/game-files/{type}/{version}', [DevCP\ModerateGameFilesController::class, 'view']);

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

        // My Items & Ratings
        $group->get('/my-items', [Workshop\WorkshopMyItemsController::class, 'index'])->add(LoggedInMiddleware::class);
        $group->get('/my-ratings', [Workshop\WorkshopRatingController::class, 'myRatingsIndex'])->add(LoggedInMiddleware::class);

        // Workshop item comment
        $group->post('/item/{id:\d+}/comment', [Workshop\WorkshopCommentController::class, 'comment'])->add(LoggedInMiddleware::class);
        $group->post('/item/{item_id:\d+}/comment/{comment_id:\d+}', [Workshop\WorkshopCommentController::class, 'replyComment'])->add(LoggedInMiddleware::class); // AJAX
        $group->put('/item/{item_id:\d+}/comment/{comment_id:\d+}', [Workshop\WorkshopCommentController::class, 'updateComment'])->add(LoggedInMiddleware::class); // AJAX
        $group->delete('/item/{item_id:\d+}/comment/{comment_id:\d+}', [Workshop\WorkshopCommentController::class, 'deleteComment'])->add(LoggedInMiddleware::class); // AJAX

        // Browse items
        $group->get('/browse', [Workshop\WorkshopBrowseController::class, 'browseIndex']);

        // User page
        $group->get('/user/{username}', [Workshop\WorkshopUserController::class, 'userIndex']);

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


        // Workshop Tools
        $group->group('/tools', function (RouteCollectorProxy $group) use ($container) {

            $group->get('', [Workshop\Tools\WorkshopToolsController::class, 'index']);

            // Tool: KeeperFX CFG Diff
            $group->get('/kfx-cfg-diff', [Workshop\Tools\WorkshopKfxCfgDiffToolController::class, 'index']);
            $group->post('/kfx-cfg-diff', [Workshop\Tools\WorkshopKfxCfgDiffToolController::class, 'compare']);

            // Tool: KeeperFX Host Checker
            $group->get('/kfx-host-checker', [Workshop\Tools\WorkshopKfxHostCheckerToolController::class, 'index']);
            $group->get('/kfx-host-checker/ping/{ip:.+}', [Workshop\Tools\WorkshopKfxHostCheckerToolController::class, 'ping']);

            // Tool: Free map number checker
            $group->get('/find-available-map-number', [Workshop\Tools\WorkshopFreeMapnumberToolController::class, 'index']);

        });
    });

    // ToS & Privacy Policy
    $group->get('/terms-of-service', [InfoPageController::class, 'termsOfServiceIndex']);
    $group->get('/privacy-policy', [InfoPageController::class, 'privacyPolicyIndex']);

    // Security Issues
    $group->get('/security.txt', [SecurityIssuesController::class, 'securityTxt']);
    $group->get('/.well-known/security.txt', [SecurityIssuesController::class, 'securityTxt']);
    $group->get('/security-acknowledgments', [SecurityIssuesController::class, 'acknowledgments']);

    // RSS
    $group->get('/rss-info', [RSSController::class, 'rssInfoIndex']);
    $group->group('/rss', function (RouteCollectorProxy $group) use ($container) {
        $group->get('/news', [RSSController::class, 'newsFeed']);
        $group->get('/stable', [RSSController::class, 'stableBuildFeed']);
        $group->get('/alpha', [RSSController::class, 'alphaPatchFeed']);
    });

    // History
    $group->get('/history', [HistoryController::class, 'index']);

    // Website Changelog
    $group->get('/website-changelog', [WebsiteChangelogController::class, 'index']);

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
    $group->get('/v1/workshop/search', [Api\v1\Workshop\WorkshopItemApiController::class, 'search']);
    $group->get('/v1/workshop/map_number/{map_number:\d+}', [Api\v1\Workshop\WorkshopItemApiController::class, 'checkMapNumber']);

    // API: User
    $group->get('/v1/user/search', [Api\v1\UserApiController::class, 'search']);

    // API: Releases
    $group->get('/v1/release/stable/latest', [Api\v1\ReleaseApiController::class, 'latestStable']);
    $group->get('/v1/release/alpha/latest', [Api\v1\ReleaseApiController::class, 'latestAlpha']);
    $group->get('/v1/release/stable/check/{version}', [Api\v1\ReleaseApiController::class, 'checkStableUpdate']);
    $group->get('/v1/release/alpha/check/{version}', [Api\v1\ReleaseApiController::class, 'checkAlphaUpdate']);
    $group->get('/v1/release/{type}/{version:[a-zA-Z0-9\.\-]+}/files', [Api\v1\GameFileController::class, 'listFiles']);

    // API: Crash Report
    $group->post('/v1/crash-report', [Api\v1\CrashReportApiController::class, 'upload']);

    // API: Prototype
    $group->get('/v1/prototype/run/{id:\d+}', [Api\v1\PrototypeApiController::class, 'getByRunId']);

    // API: Moon Phase
    $group->get('/v1/moonphase', [Api\v1\MoonPhaseApiController::class, 'outputInfo']);

});


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////// Redirects
/////// - 301 -> Permanently moved
/////// - 302 -> Temporary redirect
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// April fools 2024
$app->redirect('/giveaways/dk3-beta-key', 'https://youtu.be/ceWFU2pBOPo', 302);

// Tools moved to workshop
$app->redirect('/tools/kfx-cfg-diff', '/workshop/tools/kfx-cfg-diff', 301);
$app->redirect('/tools/kfx-host-checker', '/workshop/tools/kfx-host-checker', 301);

// Add '/release/' to stable/alpha API endpoints
$app->redirect('/api/v1/stable/latest', '/api/v1/release/stable/latest', 301);
$app->redirect('/api/v1/alpha/latest', '/api/v1/release/alpha/latest', 301);
$app->redirect('/api/v1/stable/check/{version}', '/api/v1/release/stable/check/{version}', 301);
$app->redirect('/api/v1/alpha/check/{version}', '/api/v1/release/alpha/check/{version}', 301);
