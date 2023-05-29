<?php

namespace App\Controller\Workshop;

use App\Account;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopDifficultyRating;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Twig\Extension\WorkshopRatingTwigExtension;

class WorkshopRatingController {

    public function rateQuality(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        WorkshopRatingTwigExtension $workshop_rating_extension,
        $id
    )
    {
        $post = $request->getParsedBody();
        $score = (int) ($post['score'] ?? 0);

        // Check valid score
        if($score < 1 || $score > 5){
            return $response;
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            return $response;
        }

        // Check if workshop item has been accepted
        if($workshop_item->getIsPublished() !== true){
            return $response;
        }

        if($workshop_item->getSubmitter() === $account->getUser()){
            return $response;
        }

        // Get possible already existing rating
        $rating = $em->getRepository(WorkshopRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account->getUser()
        ]);

        // Set rating or create a new one
        if($rating !== null){
            $rating->setScore($score);
        } else {
            $rating = new WorkshopRating();
            $rating->setItem($workshop_item);
            $rating->setUser($account->getUser());
            $rating->setScore($score);
            $em->persist($rating);
        }

        // Save changes to DB
        $em->flush();

        // Get updated rating
        $rating_score = null;
        $ratings = $workshop_item->getRatings();
        if($ratings && \count($ratings) > 0){
            $rating_scores = [];
            foreach($ratings as $rating){
                $rating_scores[] = $rating->getScore();
            }
            $rating_average =  \array_sum($rating_scores) / \count($rating_scores);
            $rating_score  = \round($rating_average, 2);
        }

        // Set updated rating in item
        // This way we don't always have to calculate the rating when doing stuff like
        //   ordering workshop items by rating score
        $workshop_item->setRatingScore($rating_score);
        $em->flush();

        // Return
        $response->getBody()->write(
            \json_encode([
                'success'      => true,
                'rating_score' => $rating_score,
                'rating_count' => \count($ratings),
                'html'         => $workshop_rating_extension->renderWorkshopQualityRating($id, $rating_score),
                'csrf'         => [
                    'keys' => [
                        'name'  => $csrf_guard->getTokenNameKey(),
                        'value' => $csrf_guard->getTokenValueKey(),
                    ],
                    'name'  => $csrf_guard->getTokenName(),
                    'value' => $csrf_guard->getTokenValue()
                ],
            ])
        );

        return $response;
    }


    public function rateDifficulty(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        WorkshopRatingTwigExtension $workshop_rating_extension,
        $id
    )
    {
        $post = $request->getParsedBody();
        $score = (int) ($post['score'] ?? 0);

        // Check valid score
        if($score < 1 || $score > 5){
            return $response;
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            return $response;
        }

        // Check if workshop item has been accepted
        if($workshop_item->getIsPublished() !== true){
            return $response;
        }

        // Get possible already existing rating
        $rating = $em->getRepository(WorkshopDifficultyRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account->getUser()
        ]);

        // Set rating or create a new one
        if($rating !== null){
            $rating->setScore($score);
        } else {
            $rating = new WorkshopDifficultyRating();
            $rating->setItem($workshop_item);
            $rating->setUser($account->getUser());
            $rating->setScore($score);
            $em->persist($rating);
        }

        // Save changes to DB
        $em->flush();

        // Get updated rating
        $rating_score = null;
        $ratings = $workshop_item->getDifficultyRatings();
        if($ratings && \count($ratings) > 0){
            $rating_scores = [];
            foreach($ratings as $rating){
                $rating_scores[] = $rating->getScore();
            }
            $rating_average =  \array_sum($rating_scores) / \count($rating_scores);
            $rating_score  = \round($rating_average, 2);
        }

        // Set updated rating in item
        // This way we don't always have to calculate the rating when doing stuff like
        //   ordering workshop items by rating score
        $workshop_item->setDifficultyRatingScore($rating_score);
        $em->flush();

        // Return
        $response->getBody()->write(
            \json_encode([
                'success'      => true,
                'rating_score' => $rating_score,
                'rating_count' => \count($ratings),
                'html'         => $workshop_rating_extension->renderWorkshopDifficultyRating($id, $rating_score),
                'csrf'         => [
                    'keys' => [
                        'name'  => $csrf_guard->getTokenNameKey(),
                        'value' => $csrf_guard->getTokenValueKey(),
                    ],
                    'name'  => $csrf_guard->getTokenName(),
                    'value' => $csrf_guard->getTokenValue()
                ],
            ])
        );

        return $response;
    }

    public function removeQualityRating(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        WorkshopRatingTwigExtension $workshop_rating_extension,
        $id
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            return $response;
        }

        // Get rating
        $rating = $em->getRepository(WorkshopRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account->getUser()
        ]);
        if(!$rating) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'Rating not found',
                    'csrf'    => [
                        'keys' => [
                            'name'  => $csrf_guard->getTokenNameKey(),
                            'value' => $csrf_guard->getTokenValueKey(),
                        ],
                        'name'  => $csrf_guard->getTokenName(),
                        'value' => $csrf_guard->getTokenValue()
                    ],
                ])
            );
            return $response;
        }

        // Remove rating
        $em->remove($rating);
        $em->flush();

        // Get updated rating
        $rating_score = null;
        $ratings = $workshop_item->getRatings();
        if($ratings && \count($ratings) > 0){
            $rating_scores = [];
            foreach($ratings as $rating){
                $rating_scores[] = $rating->getScore();
            }
            $rating_average =  \array_sum($rating_scores) / \count($rating_scores);
            $rating_score  = \round($rating_average, 2);
        }

        // Set updated rating in item
        // This way we don't always have to calculate the rating when doing stuff like
        //   ordering workshop items by rating score
        $workshop_item->setRatingScore($rating_score);
        $em->flush();

        // Return
        $response->getBody()->write(
            \json_encode([
                'success'      => true,
                'rating_score' => $rating_score,
                'rating_count' => \count($ratings),
                'html'         => $workshop_rating_extension->renderWorkshopQualityRating($id, $rating_score),
                'csrf'         => [
                    'keys' => [
                        'name'  => $csrf_guard->getTokenNameKey(),
                        'value' => $csrf_guard->getTokenValueKey(),
                    ],
                    'name'  => $csrf_guard->getTokenName(),
                    'value' => $csrf_guard->getTokenValue()
                ],
            ])
        );
        return $response;
    }

    public function removeDifficultyRating(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        WorkshopRatingTwigExtension $workshop_rating_extension,
        $id
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            return $response;
        }

        // Get rating
        $rating = $em->getRepository(WorkshopDifficultyRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account->getUser()
        ]);
        if(!$rating) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'Rating not found',
                    'csrf'    => [
                        'keys' => [
                            'name'  => $csrf_guard->getTokenNameKey(),
                            'value' => $csrf_guard->getTokenValueKey(),
                        ],
                        'name'  => $csrf_guard->getTokenName(),
                        'value' => $csrf_guard->getTokenValue()
                    ],
                ])
            );
            return $response;
        }

        // Remove rating
        $em->remove($rating);
        $em->flush();

        // Get updated rating
        $rating_score = null;
        $ratings = $workshop_item->getDifficultyRatings();
        if($ratings && \count($ratings) > 0){
            $rating_scores = [];
            foreach($ratings as $rating){
                $rating_scores[] = $rating->getScore();
            }
            $rating_average =  \array_sum($rating_scores) / \count($rating_scores);
            $rating_score  = \round($rating_average, 2);
        }

        // Set updated rating in item
        // This way we don't always have to calculate the rating when doing stuff like
        //   ordering workshop items by rating score
        $workshop_item->setDifficultyRatingScore($rating_score);
        $em->flush();

        // Return
        $response->getBody()->write(
            \json_encode([
                'success'      => true,
                'rating_score' => $rating_score,
                'rating_count' => \count($ratings),
                'html'         => $workshop_rating_extension->renderWorkshopDifficultyRating($id, $rating_score),
                'csrf'         => [
                    'keys' => [
                        'name'  => $csrf_guard->getTokenNameKey(),
                        'value' => $csrf_guard->getTokenValueKey(),
                    ],
                    'name'  => $csrf_guard->getTokenName(),
                    'value' => $csrf_guard->getTokenValue()
                ],
            ])
        );
        return $response;
    }
}
