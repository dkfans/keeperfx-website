<?php

use App\Config\Config;

use App\Twig\TwigGlobalProvider;
use App\Twig\TwigExtensionLoader;

use Psr\Container\ContainerInterface;
use App\Kernel\Exception\ContainerException;

/**
 * Twig container definitions
 */
return [

    \Twig\Environment::class => function(ContainerInterface $container){

        // Make sure Twig is enabled in config
        if(Config::get('twig.is_enabled') === false){
            throw new ContainerException(
                'The container tried to resolve Twig but it\'s disabled. (\\Twig\\Environment) ' .
                'Enable it in: \'' . APP_ROOT . '/config/twig.config.php\''
            );
        }

        // Create Twig environment
        $loader = new \Twig\Loader\FilesystemLoader(Config::get('twig.views_dir'));
        $twig   = new \Twig\Environment($loader, Config::get('twig.options'));

        // Add simple tests
        $twig->addTest(
            new \Twig\TwigTest('string', function($value){
                return \is_string($value);
            })
        );
        $twig->addTest(
            new \Twig\TwigTest('int', function($value){
                return \is_int($value);
            })
        );
        $twig->addTest(
            new \Twig\TwigTest('float', function($value){
                return \is_float($value);
            })
        );
        $twig->addTest(
            new \Twig\TwigTest('array', function($value){
                return \is_array($value);
            })
        );

        // Add Twig extensions
        foreach($container->get(TwigExtensionLoader::class)->getExtensions() as $extension){
            $twig->addExtension($extension);
        }

        // Add Twig globals
        foreach($container->get(TwigGlobalProvider::class)->getGlobals() as $key => $value) {
            $twig->addGlobal($key, $value);
        }

        // Add markdown runtime loader
        $twig->addRuntimeLoader(new \App\Twig\Extension\Markdown\CustomMarkdownRuntimeLoader());

        // Add debug bar collector
        // We do this here so the Twig session extension does not load the session before the request middleware loads it
        if($_ENV['APP_ENV'] === 'dev'){
            $debugbar = $container->get(\DebugBar\StandardDebugBar::class);
            $profile = new \Twig\Profiler\Profile();
            $twig->addExtension(new \Twig\Extension\ProfilerExtension($profile));
            $debugbar->addCollector(new \DebugBar\Bridge\NamespacedTwigProfileCollector($profile, $twig));
        }

        return $twig;
    },
];
