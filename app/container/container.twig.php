<?php

use App\Twig\TwigExtensionLoader;
use App\Twig\TwigGlobalProvider;

use Psr\Container\ContainerInterface;

use App\Kernel\Exception\ContainerException;
use App\Config\Config;

use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

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

        // Add Twig extensions
        foreach($container->get(TwigExtensionLoader::class)->getExtensions() as $extension){
            $twig->addExtension($extension);
        }

        // Add Twig globals
        foreach($container->get(TwigGlobalProvider::class)->getGlobals() as $key => $value) {
            $twig->addGlobal($key, $value);
        }

        // Add markdown runetime loader
        $twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load($class) {
                if (MarkdownRuntime::class === $class) {
                    return new MarkdownRuntime(new DefaultMarkdown());
                }
            }
        });

        return $twig;
    },
];
