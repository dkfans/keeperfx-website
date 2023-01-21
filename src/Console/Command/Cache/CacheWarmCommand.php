<?php

namespace App\Console\Command\Cache;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Twig\Environment as TwigEnvironment;
use Xenokore\Utility\Helper\DirectoryHelper;

class CacheWarmCommand extends Command
{
    public const CACHE_DIR  = APP_ROOT . '/cache';
    public const ENTITY_DIR = APP_ROOT . '/src/Entity';
    public const VIEWS_DIR  = APP_ROOT . '/views';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("cache:warm")
            ->setDescription("Warm the cache");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('[>] Warming the cache...');

        // Auto generate Doctrine proxy classes
        $orm_config = $this->container->get(\Doctrine\ORM\Configuration::class);
        $orm_config->setAutoGenerateProxyClasses(true);
        $dbal_conn = $this->container->get(\Doctrine\DBAL\Connection::class);
        $em = new EntityManager($dbal_conn, $orm_config);
        foreach(\glob(self::ENTITY_DIR . '/*.php') as $entity_file){
            $entity_name = \explode('.', \basename($entity_file))[0];
            $output->writeln("[>] Generate proxy classes for: {$entity_name}");
            $full_entity_class = 'App\\Entity\\' . $entity_name;
            $em->getRepository($full_entity_class)->findBy([], null, 1);
        }

        // Compile Twig templates
        $twig = $this->container->get(TwigEnvironment::class);
        foreach(DirectoryHelper::tree(self::VIEWS_DIR, true) as $template){
            $output->writeln("[>] Compiling twig template: {$template}");
            try {
                $twig->render($template);
            } catch (\Exception $ex){}
        }

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
