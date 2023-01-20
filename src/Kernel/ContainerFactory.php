<?php

namespace App\Kernel;

use DI\ContainerBuilder;
use function DI\autowire;

use Psr\Container\ContainerInterface;

use Xenokore\Utility\Helper\StringHelper;
use Kir\StringUtils\Matching\Wildcards\Pattern as WildcardPattern;

class ContainerFactory {

    public const CONTAINER_COMPILE_CLASS = 'CompiledContainer';

    public static function create(array $config, array $definitions = []): ContainerInterface
    {
        // Setup builder
        $builder = new ContainerBuilder;
        $builder->useAutowiring($config['autowire']['is_enabled']);

        // Get path to compiled container file
        $compiled_container_file = sprintf(
            "%s/%s.php",
            $config['compilation']['output_dir'],
            self::CONTAINER_COMPILE_CLASS
        );

        // Enable container compilation
        // If we're in the 'dev' environment it shouldn't
        if ($config['compilation']['is_enabled'] && $_ENV['APP_ENV'] !== 'dev') {
            $builder->enableCompilation(
                $config['compilation']['output_dir'],
                self::CONTAINER_COMPILE_CLASS
            );
        }

        // Add definitions:
        // - If in 'dev' environment
        // - If compiling is disabled
        // - If compiling is enabled and the container is not compiled yet
        // This is done so that adding definitions only happens when the container is created
        if (
            $_ENV['APP_ENV'] === 'dev' ||
            $config['compilation']['is_enabled'] === false ||
            ($config['compilation']['is_enabled'] === true && !\file_exists($compiled_container_file))
        ) {

            // Add autowires
            if($config['autowire']['is_enabled'] === true && \is_array($config['autowire']['paths'])){

                $autowire_definitions = [];

                // Loop trough autowire dirs.
                // The namespace is used to append to the relative filepaths.
                foreach($config['autowire']['paths'] as $namespace => $dir){

                    $dir = \rtrim($dir, ' \\/');
                    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file_info) {
                        if ($file_info->isFile()) {

                            if(StringHelper::endsWith($file_info->getFilename(), '.php') === false){
                                continue;
                            }

                            // Handle ignores (using wildcards)
                            foreach($config['autowire']['ignore'] as $ignore_pattern){
                                if(WildcardPattern::create($ignore_pattern)->match($file_info->getFilename())){
                                    continue 2; // jump out parent loop too ;)
                                }
                            }

                            // Get namespace and classname
                            $relative_path   = StringHelper::subtract($file_info->getRealPath(), StringHelper::length($dir) + 1);
                            $class_full_name = $namespace . '\\' . explode('.', $relative_path)[0];
                            $class_full_name = str_replace(['\\\\', '/'], '\\', $class_full_name);

                            if(\enum_exists($class_full_name)) {
                                continue;
                            }

                            if(StringHelper::startsWith($class_full_name, 'App\\Entity\\')){
                                continue;
                            }

                            // Setup class container definition as autowire
                            $autowire_definitions[$class_full_name] = autowire();
                        }
                    }
                }

                if(\count($autowire_definitions) > 0){
                    $builder->addDefinitions($autowire_definitions);
                }
            }

            // Add custom App container definitions.
            // These *OVERWRITE* existing definitions.
            if($config['definition_dir'] && \is_dir($config['definition_dir'])){
                foreach (\glob($config['definition_dir'] . '/container.*.php') as $path) {
                    $builder->addDefinitions($path);
                }
            }

        }

        // Add passed definitions
        $builder->addDefinitions($definitions);

        return $builder->build();
    }

}
