<?php

use App\Config\Config;

use App\Twig\TwigGlobalProvider;
use App\Twig\TwigExtensionLoader;

use DebugBar\StandardDebugBar;
use DebugBar\Bridge\NamespacedTwigProfileCollector;

use Twig\RuntimeLoader\RuntimeLoaderInterface;

use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\MarkdownInterface;
use League\CommonMark\GithubFlavoredMarkdownConverter;

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
        $twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load($class) {
                if (MarkdownRuntime::class === $class) {
                    return new MarkdownRuntime(
                        new class (new GithubFlavoredMarkdownConverter) implements MarkdownInterface {
                            public function __construct(private $converter){}
                            public function convert(string $string): string {
                                // Handle a custom spoiler tag
                                $string = \preg_replace('~\|\|(.+?)\|\|~', '<span class="spoiler">$1</span>', $string);
                                // Run the rest of the markdown converter stuff
                                return $this->converter->convert($string);
                            }
                        }
                    );
                }
            }
        });

        // Add profiler to debugbar
        if($_ENV['APP_ENV'] == 'dev'){
            $profile = new \Twig\Profiler\Profile();
            $twig->addExtension(new \Twig\Extension\ProfilerExtension($profile));
            $container->get(StandardDebugBar::class)->addCollector(new NamespacedTwigProfileCollector($profile, $twig));
        }

        return $twig;
    },
];
