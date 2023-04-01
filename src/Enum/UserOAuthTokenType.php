<?php

namespace App\Enum;

enum UserOAuthTokenType: string {
    case Discord = 'discord';
    case Twitch  = 'twitch';
}
