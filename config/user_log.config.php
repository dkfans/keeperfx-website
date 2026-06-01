<?php

/**
 * Twig Configuration
 */
return [

    // User account actions
    'login'                        => "Logged in",
    'login_cookie'                 => "Logged in using cookie",
    'login_oauth'                  => "Logged in using <oauth_type> (OAuth)",
    'register'                     => "Registered",
    'register_oauth'               => "Registered using <oauth_type> (OAuth)",
    'change_email'                 => "Changed email address from <from_email> to <to_email>",
    'change_password'              => "Changed password",
    'change_country'               => "Changed country from <from_country> to <to_country>",
    'change_about_me'              => "Changed About Me",
    'change_website_theme'         => "Changed website theme from <from_theme> to <to_theme>",
    'change_avatar'                => "Changed avatar",
    'send_email_verification'      => "Sent email verification to <email>",
    'verify_email'                 => "Verified email address: <email>",
    'change_notification_settings' => "Changed notification settings",
    'add_oauth'                    => "Connected <oauth_type> account (OAuth)",
    'remove_oauth'                 => "Disconnected <oauth_type> account (OAuth)",
    'logout'                       => "Logged out",
    'workshop_item_submit'         => "Submitted workshop item <workshop_item>",
    'workshop_comment'             => "Commented on <workshop_item>",                            // + text
    'workshop_comment_edit'        => "Edited comment on <workshop_item>",                       // + from_text, to_text
];
