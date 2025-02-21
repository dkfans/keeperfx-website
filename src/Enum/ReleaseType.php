<?php

namespace App\Enum;

enum ReleaseType: string
{
    case STABLE = 'stable';
    case ALPHA = 'alpha';
}
