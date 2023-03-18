<?php

namespace App\Enum;

enum UserRole: int {
    case User      = 1;
    case Moderator = 5;
    case Developer = 7;
    case Admin     = 10;
}
