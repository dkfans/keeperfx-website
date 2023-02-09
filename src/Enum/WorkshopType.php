<?php

namespace App\Enum;

enum WorkshopType: int {
    case FreePlay              = 10;
    case Campaign              = 20;
    case Multiplayer           = 30;
    case Tool                  = 50;
    case Other                 = 100;
}
