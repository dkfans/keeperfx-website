<?php

namespace App\Console\Command\User;

use App\Entity\UserPasswordResetToken;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface as Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'user:clear-old-password-reset', description: 'Clear old password reset tokens')]
class ClearOldPasswordResetTokensCommand extends Command
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function execute(Input $input, Output $output): int
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $three_days_ago = new \DateTime();
        $three_days_ago->modify('-3 days');

        $result = $em->getRepository(UserPasswordResetToken::class)->createQueryBuilder('e')
            ->where('e.created_timestamp < :three_days_ago')
            ->setParameter('three_days_ago', $three_days_ago)
            ->getQuery()
            ->getResult();

        if ($result) {
            foreach ($result as $entity) {
                $em->remove($entity);
                $output->writeln("[+] Removed: {$entity->getToken()}");
            }

            $em->flush();
        }

        // Success
        $output->writeln('[+] Done!');

        return Command::SUCCESS;
    }
}
