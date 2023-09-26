<?php

namespace App\Enum;

enum UserNotificationType: int {
    case NEW_WORKSHOP_COMMENT = 100;
    case NEW_WORKSHOP_REPLY   = 101;

    case NEW_USER             = 500;
    case NEW_CRASH_REPORT     = 501;
}
