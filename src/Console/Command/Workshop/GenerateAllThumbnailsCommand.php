<?php

namespace App\Console\Command\Workshop;

use App\Entity\User;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopTag;
use App\Workshop\WorkshopHelper;
use Doctrine\ORM\EntityManager;

use Psr\Container\ContainerInterface as Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class GenerateAllThumbnailsCommand extends Command
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
        $this->setName("workshop:generate-all-thumbnails")
            ->setDescription("Generate thumbnails for all workshop items");
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        $output->writeln("[>] Generating all workshop items thumbnails...");

        $items = $em->getRepository(WorkshopItem::class)->findAll();

        foreach($items as $item){

            $output->writeln("[>] Processing workshop item: <info>{$item->getName()}</info>");

            if($item->getThumbnail()){
                $thumbnail_filename = $item->getThumbnail();
                if(WorkshopHelper::removeThumbnail($em, $item)){
                    $output->writeln("[+] Thumbnail removed: <info>{$thumbnail_filename}</info>");
                }
            }

            $images = $item->getImages();
            if(\count($images) > 0) {

                if(WorkshopHelper::generateThumbnail($em, $item)){
                    $output->writeln("[+] Thumbnail generated: <info>{$item->getThumbnail()}</info>");
                }
            }
        }

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }

}
