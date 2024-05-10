<?php

namespace App\Notifications;

use App\Entity\User;
use App\Entity\UserNotificationSetting;

use Doctrine\ORM\EntityManager;

use Xenokore\Utility\Helper\ClassHelper;
use Xenokore\Utility\Helper\DirectoryHelper;

use App\Notifications\Exception\NotificationException;

class NotificationSettings {

    private EntityManager $em;

    private array $notification_classes = [];

    private array $default_settings = [];

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        // Loop trough all possible notifications
        foreach(DirectoryHelper::tree(__DIR__ . '/Notification') as $file){

            // Get and remember the notification class
            $class_name = ClassHelper::getClassAndNamespace($file);
            $class_name = ltrim($class_name, '\\');
            $this->notification_classes[] = $class_name;

            // Load the default notification settings for this class
            $this->default_settings[$class_name] = (new $class_name())->getDefaultSettings();
        }
    }

    public function getUserSetting(User $user, string $class){

        $user_settings = $user->getNotificationSettings();

        if($user_settings){

            /** @var UserNotificationSetting $user_setting */
            foreach($user_settings as $user_setting){

                // Check if this user has a setting for this notification
                if($user_setting->getClass() === $class){
                    return [
                        'website' => $user_setting->isWebsiteEnabled(),
                        'email'   => $user_setting->isEmailEnabled(),
                    ];
                }
            }
        }

        if(!\array_key_exists($class, $this->default_settings)){
            throw new NotificationException("default user setting for '{$class}' not found");
        }

        return $this->default_settings[$class];
    }

    public function getAllUserSettings(User $user){

        $settings = $this->default_settings;

        $user_settings = $this->em->getRepository(UserNotificationSetting::class)->findBy(['user' => $user]);

        foreach($user_settings as $setting){
            $settings[$setting->getClass()] = [
                'website' => $setting->isWebsiteEnabled(),
                'email'   => $setting->isEmailEnabled(),
            ];
        }

        return $settings;
    }

}
