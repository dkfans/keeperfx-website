<?php

namespace App\Enum;

enum WorkshopType: int {
    case Free_Play   = 1;
    case Campaign    = 2;
    case Multiplayer = 3;
    case Tool        = 4;
    case Asset       = 5;
}
