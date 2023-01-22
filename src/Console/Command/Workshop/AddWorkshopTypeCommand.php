<?php

namespace App\Console\Command\Workshop;

use App\Entity\User;
use App\Entity\WorkshopType;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class AddWorkshopTypeCommand extends Command
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
        $this->setName("workshop:add-type")
                ->setDescription("Add a workshop type")
                ->addArgument('type_name', InputArgument::REQUIRED, 'Name');
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        // Get arguments
        $type_name = (string) $input->getArgument('type_name');

        // Check if type already exists
        if($em->getRepository(User::class)->findOneBy(['name' => $type_name])){
            $output->writeln("[-] Workshop type '{$type_name}' already exists");
            return Command::FAILURE;
        }

        // Create user
        $type = new WorkshopType();
        $type->setName($type_name);

        // Add user to DB
        $em->persist($type);
        $em->flush();

        // Success
        $output->writeln("[+] Workshop type '{$type_name}' added!");
        return Command::SUCCESS;
    }

}
