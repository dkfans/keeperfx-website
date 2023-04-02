<?php

namespace App\OAuth;

use App\Enum\OAuthProviderType;

class OAuthProviderService {

    public const CLASSES = [
        'discord' => \Wohali\OAuth2\Client\Provider\Discord::class,
        'twitch'  => \Vertisan\OAuth2\Client\Provider\TwitchHelix::class,
    ];

    public const SCOPES = [
        'discord' => ['identify', 'email'],
        'twitch'  => ['user:read:email'],
    ];

    private $providers = [];

    public function getProvider(OAuthProviderType $provider)
    {
        if(isset($this->providers[$provider->value])){
            return $this->providers[$provider];
        }

        if(!isset(self::CLASSES[$provider->value])){
            throw new \Exception("OAuthProviderType '{$provider->value}' does not have a class assigned to it");
        }

        $class_name = self::CLASSES[$provider->value];

        // Create provider class
        $class = new $class_name([
            'clientId'     => $_ENV['APP_OAUTH_' . \strtoupper($provider->value) . '_CLIENT_ID'],
            'clientSecret' => $_ENV['APP_OAUTH_' . \strtoupper($provider->value) . '_CLIENT_SECRET'],
            'redirectUri'  => $_ENV['APP_ROOT_URL'] . '/oauth/connect/' . $provider->value,
        ]);

        // Set a HTTP client that does not verify SSL certs
        $class->setHttpClient(new \GuzzleHttp\Client([
            'defaults' => [
                \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 5,
                \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => true
            ],
            \GuzzleHttp\RequestOptions::VERIFY => false,
        ]));

        $this->providers[$provider->value] = $class;

        return $class;
    }


    public function getScopes(OAuthProviderType $provider): array
    {

        if(!isset(self::SCOPES[$provider->value])){
            throw new \Exception("OAuthProviderType '{$provider->value}' does not have scopes assigned to it");
        }

        return self::SCOPES[$provider->value];
    }
}
