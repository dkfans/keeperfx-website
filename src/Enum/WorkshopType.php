<?php

namespace App\Enum;

enum WorkshopType: int {
    case Campaign              = 10;
    case FreePlay              = 20;
    case Multiplayer           = 30;
    case Tool                  = 50;
    case Other                 = 100;
}
