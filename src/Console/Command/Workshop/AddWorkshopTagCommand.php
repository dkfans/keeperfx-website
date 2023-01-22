<?php

namespace App\Console\Command\User;

use App\Entity\User;
use App\Entity\WorkshopTag;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class AddWorkshopTagCommand extends Command
{
    /** @var Container $container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("workshop:add-tag")
                ->setDescription("Add a workshop tag")
                ->addArgument('tag_name', InputArgument::REQUIRED, 'Name');
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        // Get arguments
        $tag_name = (string) $input->getArgument('tag_name');

        // Check if tag already exists
        if($em->getRepository(User::class)->findOneBy(['name' => $tag_name])){
            $output->writeln("[-] Workshop tag '{$tag_name}' already exists");
            return Command::FAILURE;
        }

        // Create user
        $tag = new WorkshopTag();
        $tag->setName($tag_name);

        // Add user to DB
        $em->persist($tag);
        $em->flush();

        // Success
        $output->writeln("[+] Workshop tag '{$tag_name}' added!");
        return Command::SUCCESS;
    }

}
