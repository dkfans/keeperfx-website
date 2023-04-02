<?php

namespace App\Enum;

enum OAuthProviderType: string {
    case Discord     = 'discord';
    case Twitch      = 'twitch';
}
