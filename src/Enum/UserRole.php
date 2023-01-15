<?php

namespace App\Enum;

enum UserRole: int {
    case User      = 1;
    case Developer = 5;
    case Admin     = 9;
}
