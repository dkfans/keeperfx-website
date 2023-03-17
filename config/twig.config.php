<?php

/**
 * Twig Configuration
 */
return [

    'is_enabled' => true,

    'views_dir'  => APP_ROOT . '/views',

    /**
     * Twig environment options
     * Reference: https://twig.symfony.com/doc/3.x/api.html#environment-options
     */
    'options'    => [
        'debug'            => $_ENV['APP_ENV'] === 'dev',
        'charset'          => 'utf-8',
        'cache'            => APP_ROOT . '/cache/twig',
        'auto_reload'      => $_ENV['APP_ENV'] === 'dev',
        'strict_variables' => true,
        'autoescape'       => 'html',
        'optimizations'    => -1,
    ],

    /**
     * TwigExtensions to add to the environment.
     * How extensions are added by type:
     * - class: the extension will simply be added
     * - classname:
     *    - if the container has a reference to the classname it will be loaded from there
     *    - if the container does not have a reference to the classname it will be created => new $class();
     * - callable: the function will be called
     */
    'extensions' => [

        \App\Twig\Extension\AccountTwigExtension::class,
        \App\Twig\Extension\AssetTwigExtension::class,
        \App\Twig\Extension\BinaryFormatterTwigExtension::class,
        \App\Twig\Extension\CsrfTwigExtension::class,
        \App\Twig\Extension\EnvironmentTwigExtension::class,
        \App\Twig\Extension\FlashMessageTwigExtension::class,
        \App\Twig\Extension\GithubInteractTwigExtension::class,
        \App\Twig\Extension\RolesTwigExtension::class,
        \App\Twig\Extension\WorkshopRatingTwigExtension::class,
        \App\Twig\Extension\EnumTwigExtension::class,
        \App\Twig\Extension\SlugifyTwigExtension::class,
        \App\Twig\Extension\PathTwigExtension::class,
        \App\Twig\Extension\RequestVarTwigExtension::class,
        \App\Twig\Extension\PregReplaceTwigExtension::class,

        \Twig\Extra\Markdown\MarkdownExtension::class,

        \olivers\Twig\Extension\AvatarExtension::class,
    ],

    /**
     * Debug TwigExtensions to add to the environment.
     * These will only be active when Twig is in debug mode.
     */
    'debug_extensions' => [

        // Twig bundled extensions
        \Twig\Extension\DebugExtension::class,
        # \Twig\Extension\SandboxExtension::class,
        # \Twig\Extension\ProfilerExtension::class,
    ]
];
