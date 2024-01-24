<?php

namespace App\Console\Command\KeeperFX;

use App\Entity\GithubPrototype;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class ClearOldPrototypesCommand extends Command
{
    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("kfx:clear-old-prototypes")
            ->setDescription("Clear old build prototypes");
    }

    protected function execute(Input $input, Output $output)
    {
        // Make sure an output directory is set
        if(!empty($_ENV['APP_PROTOTYPE_STORAGE_CLI_PATH'])){
            $storage_dir = $_ENV['APP_PROTOTYPE_STORAGE_CLI_PATH'];
        } elseif (!empty($_ENV['APP_PROTOTYPE_STORAGE'])){
            $storage_dir = $_ENV['APP_PROTOTYPE_STORAGE'];
        } else {
            $output->writeln("[-] Prototype download directory is not set");
            $output->writeln("[>] ENV VAR: 'APP_PROTOTYPE_STORAGE_CLI_PATH' or 'APP_PROTOTYPE_STORAGE'");
            return Command::FAILURE;
        }

        $stale_timestamp = new \DateTime();
        $stale_timestamp->modify('-' . $_ENV['APP_PROTOTYPE_STORAGE_TIME'] . ' seconds');

        $result = $this->em->getRepository(GithubPrototype::class)->createQueryBuilder('e')
            ->where('e.timestamp < :stale_timestamp')
            ->setParameter('stale_timestamp', $stale_timestamp)
            ->getQuery()
            ->getResult();

        if($result){
            foreach($result as $entity){

                $file_path = $storage_dir . '/' . $entity->getFilename();
                if(\file_exists($file_path)){
                    if(!\unlink($file_path)){
                        $output->writeln("[-] Failed to remove file <error>{$file_path}</error>");
                    }
                }

                $this->em->remove($entity);
                $output->writeln("[+] Removed <info>{$entity->getName()}</info>");
            }

            $this->em->flush();
        }

        $output->writeln("[+] Done!");

        return Command::SUCCESS;
    }
}
