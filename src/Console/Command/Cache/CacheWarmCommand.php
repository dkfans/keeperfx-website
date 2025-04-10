<?php

namespace App\Console\Command\Cache;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Twig\Environment as TwigEnvironment;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        $current_user = \exec('whoami');
        $owning_user  = \get_current_user();

        if($current_user !== $owning_user){
            $output->writeln('[!] <error>Current user and script owner do not match!</error>');
            $output->writeln('[>] User executing the command: ' . $current_user);
            $output->writeln('[>] Script owner: ' . $owning_user);
            $output->writeln('[!] Running this command might result in permission errors.');
            /** @var HelperInterface */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('[?] Continue? [y/n] ', false);
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $output->writeln('[>] Warming the cache...');

        // Counters
        $doctrine_proxy_count = 0;
        $twig_template_count = 0;

        // Setup Doctrine and Twig
        $output->writeln('[>] Getting required app modules...');
        // Doctrine
        $orm_config = $this->container->get(\Doctrine\ORM\Configuration::class);
        $orm_config->setAutoGenerateProxyClasses(true);
        $dbal_conn = $this->container->get(\Doctrine\DBAL\Connection::class);
        $em = new EntityManager($dbal_conn, $orm_config);
        // Twig
        $twig = $this->container->get(TwigEnvironment::class);

        // Generate the proxy classes
        $output->writeln("[+] Generating Doctrine proxy classes for entities...");
        foreach(\glob(self::ENTITY_DIR . '/*.php') as $entity_file){
            $entity_name = \explode('.', \basename($entity_file))[0];
            // $output->writeln("[>] Generate proxy class: {$entity_name}");
            $full_entity_class = 'App\\Entity\\' . $entity_name;
            $em->getRepository($full_entity_class)->findBy([], null, 1);
            $doctrine_proxy_count++;
        }

        $output->writeln("[+] <info>{$doctrine_proxy_count}</info> entities handled");


        // Compile Twig templates
        $output->writeln("[+] Compiling Twig templates...");
        foreach(DirectoryHelper::tree(self::VIEWS_DIR, true) as $template){
            $filename = \basename($template);
            // $output->writeln("[>] Compiling twig template: {$filename}");
            try {
                $twig->render($template);
                $twig_template_count++;
            } catch (\Exception $ex){}
        }

        $output->writeln("[+] <info>{$twig_template_count}</info> templates compiled");

        // Done!
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }
}
