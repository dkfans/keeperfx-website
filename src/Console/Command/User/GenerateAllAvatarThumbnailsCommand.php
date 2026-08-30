<?php

namespace App\Console\Command\User;

use App\Config\Config;
use App\Entity\User;
use App\Helper\ThumbnailHelper;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface as Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'user:generate-all-avatar-thumbnails', description: 'Generate thumbnails for all user avatars')]
class GenerateAllAvatarThumbnailsCommand extends Command
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
        // Define workshop storage dir
        $storage_dir = Config::get('storage.path.avatar');
        if ($storage_dir == null) {
            $output->writeln('[-] Avatar storage directory is not set');
            $output->writeln("[>] ENV VAR: 'APP_AVATAR_STORAGE'");

            return Command::FAILURE;
        }

        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $output->writeln('[>] Generating all user avatar thumbnails...');

        $users = $em->getRepository(User::class)->findAll();
        foreach ($users as $user) {

            $output->writeln("[>] Processing user: <info>{$user->getUsername()}</info>");

            if ($user->getAvatar() === null) {
                continue;
            }

            if ($user->getAvatarSmall() !== null) {
                continue;
            }

            $avatar_path = $storage_dir . '/' . $user->getAvatar();

            $thumbnail_filename = ThumbnailHelper::createThumbnail($avatar_path, 128, 128);
            if ($thumbnail_filename) {
                $user->setAvatarSmall($thumbnail_filename);
                $output->writeln("[+] Created small avatar for user: {$user->getUsername()}");
            }
        }

        $em->flush();

        // Success
        $output->writeln('[+] Done!');

        return Command::SUCCESS;
    }
}
