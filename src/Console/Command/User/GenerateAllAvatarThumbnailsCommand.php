<?php

namespace App\Console\Command\User;

use App\Entity\User;
use App\Helper\ThumbnailHelper;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class GenerateAllAvatarThumbnailsCommand extends Command
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
        $this->setName("user:generate-all-avatar-thumbnails")
            ->setDescription("Generate thumbnails for all user avatars");
    }

    protected function execute(Input $input, Output $output)
    {
        // Define workshop storage dir
        if(!empty($_ENV['APP_AVATAR_STORAGE_CLI_PATH'])){
            $storage_dir = $_ENV['APP_AVATAR_STORAGE_CLI_PATH'];
        } elseif (!empty($_ENV['APP_AVATAR_STORAGE'])){
            $storage_dir = $_ENV['APP_AVATAR_STORAGE'];
        } else {
            $output->writeln("[-] Avatar storage directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_AVATAR_STORAGE_CLI_PATH' or 'APP_AVATAR_STORAGE'");
            return Command::FAILURE;
        }


        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $output->writeln("[>] Generating all user avatar thumbnails...");

        $users = $em->getRepository(User::class)->findAll();
        foreach($users as $user){

            $output->writeln("[>] Processing user: <info>{$user->getUsername()}</info>");

            if($user->getAvatar() === null){
                continue;
            }

            if($user->getAvatarSmall() !== null){
                continue;
            }

            $avatar_path = $storage_dir . '/' . $user->getAvatar();

            $thumbnail_filename = ThumbnailHelper::createThumbnail($avatar_path, 128, 128);
            if($thumbnail_filename){
                $user->setAvatarSmall($thumbnail_filename);
                $output->writeln("[+] Created small avatar for user: {$user->getUsername()}");
            }
        }

        $em->flush();

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }

}
