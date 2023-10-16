<?php

namespace App\Controller\ControlPanel;

use App\Entity\UserNotification;

use App\Account;
use App\Entity\UserNotificationSetting;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use App\Notifications\NotificationCenter;
use App\Notifications\NotificationSettings;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Notifications\NotificationInterface;

class NotificationController {

    public function read(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        $id,
    ){
        if(!\is_numeric($id)){
            throw new HttpNotFoundException($request);
        }

        /** @var UserNotification $notification */
        $notification = $em->getRepository(UserNotification::class)->find($id);
        if(!$notification){
            throw new HttpNotFoundException($request);
        }

        if($account->getUser() !== $notification->getUser()){
            throw new HttpNotFoundException($request);
        }

        $notification->setRead(true);
        $em->flush();

        /** @var NotificationInterface $object */
        $object = $nc->createNotificationObject($notification);

        $nc->clearUserCache();

        $response = $response->withHeader('Location', $object->getUri())->withStatus(302);
        return $response;
    }

    public function listIndex(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        TwigEnvironment $twig,
    ){
        $response->getBody()->write(
            $twig->render('cp/notifications.cp.html.twig', [
                'notifications' => $nc->getAllNotifications()
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
        TwigEnvironment $twig,
    )
    {
        $user_settings = [];

        $notification_settings = $nc->getNotificationSettings();

        foreach($notification_settings as $class_name => $settings)
        {
            $class = new $class_name();

            if($class->getRequiredUserRole()->value > $account->getUser()->getRole()->value){
                continue;
            }

            $user_settings[] = [
                'class_name' => $class_name,
                'title'      => $class->getNotificationTitle(),
                'website'    => $settings['website'],
                'email'      => $settings['email'],
            ];
        }

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
    )
    {
        // Get the posted data
        $post = $request->getParsedBody();

        // Loop trough all notification settings
        foreach($nc->getNotificationSettings() as $class => $settings) {

            // Check if the setting already exists in the DB
            /** @var UserNotificationSetting $notification_setting */
            $notification_setting = $em->getRepository(UserNotificationSetting::class)->findOneBy([
                'user'  => $account->getUser(),
                'class' => $class,
            ]);

            // Create the setting in the DB if it doesn't exist yet
            if(!$notification_setting){
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

}
