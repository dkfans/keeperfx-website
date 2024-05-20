<?php

namespace App\Enum;

enum BanType: int {
    case IP       = 1;
    case Hostname = 2;
    case ISP      = 3;
}
