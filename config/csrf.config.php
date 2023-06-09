<?php

/**
 * CSRF configuration
 * Default CSRF library is `slim/csrf`
 *
 * Reference: https://github.com/slimphp/Slim-Csrf
 */
return [

    /**
     * Prefix to append to the CSRF key
     */
    'prefix' => 'csrf',

    /**
     * Strength of CSRF name
     */
    'strength' => 16,

    /**
     * This is the maximum amount of tokens that are remembered.
     * This setting is ignored when using persistent tokens.
     */
    'storage_limit' => 200,

    /**
     * Whether or not to keep the same CSRF token throughout the whole session.
     * Created tokens up will be remembered (max = 'storage_limit').
     * This means that if a form is submitted, other open tabs will still work.
     */
    'persistent_token_mode' => true,

    /**
     * A handler for CSRF validation failures. Must be a callable.
     * This callable has the same signature as middleware:
     *      function($request, $handler) and must return a Response.
     * Setting this to null (default) will simply return a page saying 'Invalid CSRF token'
     */
    'failure_handler' => null,
];
