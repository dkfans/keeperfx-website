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

class FixWorkshopRatingsCommand extends Command
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
        $this->setName("workshop:fix-ratings")
                ->setDescription("Fix and recalculate all workshop ratings");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[+] Checking if we need to fix any workshop ratings");

        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);

        // Get workshop items
        /** @var array[WorkshopItem] $items */
        $items = $em->getRepository(WorkshopItem::class)->findAll();
        if(!$items || !is_array($items) || \count($items) <= 0){
            $output->writeln("[?] No workshop items found");
            return Command::INVALID;
        }

        $quality_ratings_updated = 0;
        $difficulty_ratings_updated = 0;

        // Loop trough all workshop items
        foreach($items as $item){

            // Get rating data
            $quality_rating_data    = WorkshopHelper::calculateRatingScore($item, WorkshopHelper::RATING_QUALITY);
            $difficulty_rating_data = WorkshopHelper::calculateRatingScore($item, WorkshopHelper::RATING_DIFFICULTY);

            // Update quality rating
            if($item->getRatingScore() !== $quality_rating_data['score']){
                $item->setRatingScore($quality_rating_data['score']);
                $quality_ratings_updated++;
            }

            // Update difficulty rating
            if($item->getDifficultyRatingScore() !== $difficulty_rating_data['score']){
                $item->setDifficultyRatingScore($difficulty_rating_data['score']);
                $difficulty_ratings_updated++;
            }
        }

        // Save changes to DB
        $em->flush();

        // Show output
        if($quality_ratings_updated > 0){
            $output->writeln("[+] <info>{$quality_ratings_updated}</info> quality ratings updated!");
        }
        if($difficulty_ratings_updated > 0){
            $output->writeln("[+] <info>{$difficulty_ratings_updated}</info> difficulty ratings updated!");
        }

        // Success
        $output->writeln("[+] Done!");
        return Command::SUCCESS;
    }

}
