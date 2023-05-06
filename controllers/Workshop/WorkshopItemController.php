<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopType;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopDifficultyRating;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Entity\WorkshopFile;
use App\UploadSizeHelper;

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopItemController {

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em,
        Account $account,
        $id,
        $slug = null
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Make sure title slug is in URL and matches
        if(URLify::slug($workshop_item->getName()) !== $slug){
            $response = $response->withHeader('Location',
                '/workshop/item/' . $workshop_item->getId() . '/' . URLify::slug($workshop_item->getName())
            )->withStatus(302);
            return $response;
        }

        // Show non-published notice
        if(!$workshop_item->getIsPublished()){
            $flash->warning('The requested workshop item has not been published.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Get workshop item rating counts
        $rating_count            = \count($workshop_item->getRatings());
        $difficulty_rating_count = \count($workshop_item->getDifficultyRatings());

        // Get user rating
        $user_rating = $em->getRepository(WorkshopRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account?->getUser()
        ])?->getScore();

        // Get user difficulty rating
        $user_difficulty_rating = $em->getRepository(WorkshopDifficultyRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account?->getUser()
        ])?->getScore();

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', [
                'item'                     => $workshop_item,
                'rating_amount'            => $rating_count,
                'user_rating'              => $user_rating,
                'difficulty_rating_amount' => $difficulty_rating_count,
                'user_difficulty_rating'   => $user_difficulty_rating,
            ])
        );

        return $response;
    }
}
