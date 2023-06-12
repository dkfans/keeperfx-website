<?php

namespace App\Enum;

enum MailStatus: int {
    case NOT_SENT_YET = 0;
    case SENDING      = 1;
    case FAILURE      = 2;
}
