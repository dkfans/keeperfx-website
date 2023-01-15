<?php

/**
 * Session configuration
 *
 * Default Session library is `Compwright\PhpSession`
 */
return [
    'name'                   => 'PHPSESSID',
    'gc_probability'         => 1,
    'gc_divisor'             => 100,
    'gc_maxlifetime'         => 1440,
    'sid_prefix'             => "",
    'sid_length'             => 48,
    'sid_bits_per_character' => 5,
    'lazy_write'             => true,
    'read_and_close'         => false,
    'cookie_lifetime'        => 0,
    'cookie_path'            => "/",
    'cookie_domain'          => "",
    'cookie_secure'          => false,
    'cookie_httponly'        => false,
    'cookie_samesite'        => "Lax",
    'cache_limiter'          => "nocache",
    'cache_expire'           => 180,
    'regenerate_id_interval' => 0,
];
