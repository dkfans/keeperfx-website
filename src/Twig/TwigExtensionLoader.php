<?php

namespace App\Twig;

use Psr\Container\ContainerInterface;
use Twig\Extension\ExtensionInterface;

use App\Twig\Exception\TwigExtensionLoaderException;
use App\Config\Config;

class TwigExtensionLoader {

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getExtensions()
    {
        $extensions = [];

        // Get extensions
        foreach(Config::get('twig.extensions') as $index => $definition) {

            // Convert extension definition to extension class
            $extension = $this->handleExtensionDefinition($definition);
            if(!is_object($extension) || !($extension instanceof ExtensionInterface)){
                throw new TwigExtensionLoaderException("Invalid Twig extension. (index: {$index})");
            }

            $extensions[] = $extension;
        }

        // Get debug extensions if Twig is in debug mode
        if(Config::get('twig.options.debug') === true) {
            foreach(Config::get('twig.debug_extensions') as $index => $definition) {

                // Convert debug extension definition to extension class
                $extension = $this->handleExtensionDefinition($definition);
                if(!is_object($extension) || !($extension instanceof ExtensionInterface)){
                    throw new TwigExtensionLoaderException("Invalid Twig debug extension. (index: {$index})");
                }

                $extensions[] = $extension;
            }
        }

        return $extensions;
    }

    public function handleExtensionDefinition(mixed $definition)
    {
        if(\is_object($definition)) {
            return $definition;

        } elseif(\is_callable($definition)) {
            return $definition();

        } elseif(\is_string($definition)){

            if($this->container->has($definition)){
                return $this->container->get($definition);
            }

            return new $definition();
        }

        return null;
    }
}
