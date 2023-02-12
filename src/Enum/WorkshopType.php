<?php

namespace App\Enum;

enum WorkshopType: int {
    case Map                = 10;
    case MapPack            = 15;
    case Campaign           = 20;
    case MultiplayerMap     = 30;
    case MultiplayerMapPack = 35;
    case Application        = 50;
    case Other              = 100;
}
