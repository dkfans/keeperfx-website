<?php

namespace App\Console\Command\User;

use App\Entity\User;

use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Enum\UserRole;

class CreateUserCommand extends Command
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
        $this->setName("user:create")
                ->setDescription("Create a user")
                ->addArgument('username', InputArgument::REQUIRED, 'Username')
                ->addArgument('password', InputArgument::OPTIONAL, 'Password')
                ->addArgument('role', InputArgument::OPTIONAL, 'Role');
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        // Get arguments
        $username = (string) $input->getArgument('username');
        $password = (string) $input->getArgument('password');
        $role     = $input->getArgument('role');

        // Check if user already exists
        if($em->getRepository(User::class)->findOneBy(['username' => $username])){
            $output->writeln("[-] User '{$username}' already exists");
            return Command::FAILURE;
        }

        // Create user
        $user = new User();
        $user->setUsername($username);

        // Set password
        if ($password) {
            $user->setPassword($password);
        }

        // Set role
        if(\is_numeric($role)){

            $user->setRole($role);

        } elseif (\is_string($role)) {

            // Convert role name to its enum value (int)
            switch(\strtolower($role)){
                // case 'closed':
                //     $role = UserRole::Closed;
                //     break;
                case 'user':
                    $role = UserRole::User;
                    break;
                case 'mod':
                case 'moderator':
                    $role = UserRole::Moderator;
                    break;
                case 'dev':
                case 'developer':
                    $role = UserRole::Developer;
                    break;
                case 'admin':
                    $role = UserRole::Admin;
                    break;
                default:
                    $output->writeln("[-] Invalid role: {$role}");
                    return Command::FAILURE;
                    break;
            }

            $user->setRole($role);
        }

        // Add user to DB
        $em->persist($user);
        $em->flush();

        // Success
        $output->writeln("[+] User '{$username}' added!");
        return Command::SUCCESS;
    }

}
