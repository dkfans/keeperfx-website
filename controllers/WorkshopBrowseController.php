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

    public function browseLatestIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        // Get workshop items
        $workshop_items = $em->getRepository(WorkshopItem::class)->findBy(
            ['is_accepted' => true],
            ['created_timestamp' => 'DESC']
        );

        // Get workshop item ratings
        $workshop_ratings = [];
        if($workshop_items){
            foreach($workshop_items as $item){
                $workshop_ratings[$item->getId()] = null;
                $ratings = $item->getRatings();
                if($ratings){
                    $rating_scores = [];
                    foreach($ratings as $rating){
                        $rating_scores[] = $rating->getScore();
                    }
                    $rating_average =  \array_sum($rating_scores) / \count($rating_scores);
                    $workshop_ratings[$item->getId()] = $rating_average;
                }
            }
        }

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/browse.workshop.html.twig', $this->getWorkshopOptions() + [
                'browse_type'      => 'latest',
                'workshop_items'   => $workshop_items,
                'workshop_ratings' => $workshop_ratings,
            ])
        );
        return $response;
    }
}
