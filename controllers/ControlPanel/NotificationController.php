<?php

namespace App\Controller\ControlPanel;

use App\Entity\UserNotification;
use App\Entity\UserNotificationSetting;

use App\Account;
use App\FlashMessage;
use Slim\Csrf\Guard as CsrfGuard;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use App\Notifications\NotificationCenter;
use App\Notifications\NotificationSettings;
use App\Notifications\NotificationInterface;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;

class NotificationController
{

    public function read(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        $id,
    ) {
        // Make sure ID is numeric
        if (!\is_numeric($id)) {
            throw new HttpNotFoundException($request);
        }

        // Get notification
        /** @var UserNotification $notification */
        $notification = $em->getRepository(UserNotification::class)->find($id);
        if (!$notification) {
            throw new HttpNotFoundException($request);
        }

        // Make sure this notification belongs to this user
        if ($account->getUser() !== $notification->getUser()) {
            throw new HttpNotFoundException($request);
        }

        // Mark notification as read
        $notification->setRead(true);
        $em->flush();

        // Get URL
        /** @var NotificationInterface $object */
        $object = $nc->createNotificationObject($notification);
        $url = $object->getUri();

        // Add hashbang to scroll into view if the URL does not have a hashbang yet
        if (\stripos($url, '#') === false) {
            $url = $url . '#nav-top';
        }

        // Clear user cache because this notification is read
        $nc->clearUserCache();

        // Redirect user to URL
        $response = $response->withHeader('Location', $url)->withStatus(302);
        return $response;
    }

    public function listIndex(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        TwigEnvironment $twig,
    ) {

        $response->getBody()->write(
            $twig->render('cp/notifications.cp.html.twig', [
                'notifications'             => $nc->getAllNotifications(),
                'unread_notification_count' => \count($nc->getUnreadNotifications()),
            ])
        );

        return $response;
    }

    public function settingsIndex(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        NotificationSettings $ns,
        FlashMessage $flash,
        TwigEnvironment $twig,
    ) {
        // Get user notification settings
        $user_settings = [];
        $notification_settings = $nc->getNotificationSettings();
        foreach ($notification_settings as $class_name => $settings) {
            /** @var NotificationInterface $class */
            $class = new $class_name();

            if ($class->getRequiredUserRole()->value > $account->getUser()->getRole()->value) {
                continue;
            }

            $user_settings[] = [
                'class_name' => $class_name,
                'title'      => $class->getNotificationTitle(),
                'website'    => $settings['website'],
                'email'      => $settings['email'],
                'role'       => $class->getRequiredUserRole(),
            ];
        }

        // Check if email is verified
        if ($account->getUser()->getEmail() !== null && $account->getUser()->isEmailVerified() === false) {
            $flash->info('You have not verified your email address yet. Please verify it to enable additional functionality. You can re-send the activation email on the <a href="/account">Account Settings page</a>.');
        }

        // Response
        $response->getBody()->write(
            $twig->render('cp/notification.settings.cp.html.twig', [
                'settings' => $user_settings
            ])
        );

        return $response;
    }



    public function updateSettings(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        NotificationSettings $ns,
        TwigEnvironment $twig,
        FlashMessage $flash,
    ) {
        // Get the posted data
        $post = $request->getParsedBody();

        // Loop trough all notification settings
        foreach ($nc->getNotificationSettings() as $class => $settings) {

            // Check if the setting already exists in the DB
            /** @var UserNotificationSetting $notification_setting */
            $notification_setting = $em->getRepository(UserNotificationSetting::class)->findOneBy([
                'user'  => $account->getUser(),
                'class' => $class,
            ]);

            // Create the setting in the DB if it doesn't exist yet
            if (!$notification_setting) {
                $notification_setting = new UserNotificationSetting();
                $notification_setting->setClass($class);
                $notification_setting->setUser($account->getUser());
                $em->persist($notification_setting);
            }

            $is_website_enabled = isset($post['settings'][$class]['website']);
            $is_email_enabled = isset($post['settings'][$class]['email']);

            $notification_setting->setWebsiteEnabled($is_website_enabled);
            $notification_setting->setEmailEnabled($is_email_enabled);
        }

        $em->flush();

        $flash->success('Notification settings updated!');
        $response = $response->withHeader('Location', '/account/notifications/settings')->withStatus(302);
        return $response;
    }

    public function markAllAsRead(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        $token_name,
        $token_value,
    ) {
        // Check for valid CSRF token
        if (!$csrf_guard->validateToken($token_name, $token_value)) {
            throw new HttpForbiddenException($request);
        }

        // Update read status of all notifications
        $notifications = $em->getRepository(UserNotification::class)->findBy(['user' => $account->getUser(), 'is_read' => false]);
        foreach ($notifications as $notification) {
            $notification->setRead(true);
        }

        // Save changes to DB
        $em->flush();

        // Clear the notification cache of this user
        $nc->clearUserCache($account->getUser());

        // Show success and navigate to notifications list
        $flash->success('All notifications have been marked as read.');
        $response = $response->withHeader('Location', '/account/notifications')->withStatus(302);
        return $response;
    }
}
