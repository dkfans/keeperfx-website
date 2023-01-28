<?php

namespace App\Enum;

enum WorkshopType: int {
    case Campaign              = 10;
    case FreePlayMap           = 20;
    case MultiplayerMap        = 30;
    case Tool                  = 50;
    case Other                 = 100;
}
