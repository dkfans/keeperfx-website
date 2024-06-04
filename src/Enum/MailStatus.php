<?php

namespace App\Enum;

enum MailStatus: int {
    case FAILURE      = -1;
    case NOT_SENT_YET = 0;
    case SENDING      = 1;
    case SENT         = 2;
}
