<?php

namespace App\Enum;

enum WorkshopScanStatus: int {
    case NOT_SCANNED_YET = 0;
    case SCANNING        = 1;
    case SCANNED         = 2;
}
