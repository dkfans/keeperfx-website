<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;

use App\Enum\WorkshopType;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopBrowseController {

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getWorkshopOptions(): array
    {
        // TODO: improve the name of this function
        return [
            'types'  => WorkshopType::cases(),
            'tags'   => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
            'builds' => $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
        ];
    }

    private function getWorkshopItemsAndRating(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ):array {

        // Get workshop items
        $workshop_items = $this->em->getRepository(WorkshopItem::class)->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );

        // Get workshop item ratings
        $workshop_ratings = [];
        if($workshop_items){
            foreach($workshop_items as $item){
                $workshop_ratings[$item->getId()] = null;
                $ratings = $item->getRatings();
                if($ratings && \count($ratings) > 0){
                    $rating_scores = [];
                    foreach($ratings as $rating){
                        $rating_scores[] = $rating->getScore();
                    }
                    $rating_average =  \array_sum($rating_scores) / \count($rating_scores);
                    $workshop_ratings[$item->getId()] = $rating_average;
                }
            }
        }

        return [
            'workshop_items'   => $workshop_items,
            'workshop_ratings' => $workshop_ratings,
        ];
    }

    public function browseIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ){
        $order_by = 'latest';

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/browse.workshop.html.twig', [
                    'order_by' => $order_by,
                ] +
                $this->getWorkshopItemsAndRating(
                    ['is_accepted' => true],
                    ['created_timestamp' => 'DESC']
                ) +
                $this->getWorkshopOptions()
            )
        );

        return $response;
    }

    public function browseMostDownloadedIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ){

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/browse.workshop.html.twig', [
                    'browse_type' => 'latest',
                ] +
                $this->getWorkshopItemsAndRating(
                    ['is_accepted' => true],
                    ['created_timestamp' => 'DESC']
                ) +
                $this->getWorkshopOptions()
            )
        );

        return $response;
    }
}
