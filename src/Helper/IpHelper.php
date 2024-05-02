<?php

namespace App\Helper;

class IpHelper {

    public static function isValidIp(string $ip): bool
    {
        return (\filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false);
    }
}
