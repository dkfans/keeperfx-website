<?php

namespace App\Helper;

class IpHelper
{
    /**
     * Validate an IP address regardless of it being IPv4 or IPv6.
     */
    public static function isValidIp(string $ip): bool
    {
        return \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Masks last part of IPv4 (e.g., 192.168.1.0) and last 4 blocks of IPv6.
     */
    public static function anonymizeIp(string $ip): string
    {
        return \preg_replace(
            ['/(?:\.\d+){1}$/', '/(?::[a-f0-9]{1,4}){4}$/i'],
            ['.0', ':0000:0000:0000:0000'],
            $ip
        );
    }
}
