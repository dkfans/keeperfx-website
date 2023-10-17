<?php

namespace App\Console\Command\User;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\UserPasswordResetToken;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Enum\UserRole;

class ClearOldNotificationsCommand extends Command
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
        $this->setName("user:clear-old-notifications")
                ->setDescription("Clear old notifications");
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $stale_timestamp = new \DateTime();
        $stale_timestamp->modify('-7 days'); // TODO: make this configurable

        $result = $em->getRepository(UserNotification::class)->createQueryBuilder('e')
            ->where('e.created_timestamp < :stale_timestamp')
            ->andWhere('e.is_read = 1')
            ->setParameter('stale_timestamp', $stale_timestamp)
            ->getQuery()
            ->getResult();

        if($result){
            foreach($result as $entity){
                $em->remove($entity);
                $output->writeln("[+] Removed <info>#{$entity->getId()}</info>");
            }

            $em->flush();
        }

        $stale_timestamp = new \DateTime();
        $stale_timestamp->modify('-3 months'); // TODO: make this configurable

        $result = $em->getRepository(UserNotification::class)->createQueryBuilder('e')
            ->where('e.created_timestamp < :stale_timestamp')
            ->andWhere('e.is_read = 0')
            ->setParameter('stale_timestamp', $stale_timestamp)
            ->getQuery()
            ->getResult();

        if($result){
            foreach($result as $entity){
                $em->remove($entity);
                $output->writeln("[+] Removed <info>#{$entity->getId()}</info> (not read)");
            }

            $em->flush();
        }

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }

}
