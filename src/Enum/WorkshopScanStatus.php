<?php

namespace App\Enum;

enum WorkshopScanStatus: int
{
    case INVALID         = -1; // if file does not exist
    case NOT_SCANNED_YET = 0;
    case SCANNING        = 1;
    case SCANNED         = 2;
}
