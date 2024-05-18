<?php

namespace App\Controller\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Workshop\WorkshopCache;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class WorkshopUserController {

    public function userIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        WorkshopCache $workshop_cache,
        Account $account,
        FlashMessage $flash,
        string $username,
    ){

        // Get user
        $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
        if(!$user){
            throw new HttpNotFoundException($request);
        }

        // Generate a browse page cache key for this user
        $user_cache_id = ['_user_page_', $username];

        // Check if this page is already cached
        $cached_view_data = $workshop_cache->getCachedBrowsePageData($user_cache_id);
        if($cached_view_data){
            $response->getBody()->write(
                $twig->render('workshop/user.workshop.html.twig',
                    \array_merge($cached_view_data, ['user' => $user]) // User needs to be dynamic
                )
            );
            return $response;
        }

        // Get workshop items
        $workshop_items = $user->getWorkshopItems();

        // Serialize workshop items
        $workshop_items_serialized = [];
        foreach($workshop_items as $workshop_item){

            // Serialize
            $workshop_items_serialized[] = [
                'id' => $workshop_item->getId(),
                'name' => $workshop_item->getName(),
                'submitter' => $workshop_item->getSubmitter() === null ? null : [
                    'id'          => $workshop_item->getSubmitter()->getId(),
                    'username'    => $workshop_item->getSubmitter()->getUsername(),
                    'avatar'      => $workshop_item->getSubmitter()->getAvatar(),
                    'avatarSmall' => $workshop_item->getSubmitter()->getAvatarSmall(),
                    'role'        => $workshop_item->getSubmitter()->getRole(),
                ],
                'category'                => $workshop_item->getCategory(),
                'createdTimestamp'        => $workshop_item->getCreatedTimestamp(),
                'updatedTimestamp'        => $workshop_item->getUpdatedTimestamp(),
                'difficultyRatingEnabled' => $workshop_item->isDifficultyRatingEnabled(),
                'downloadCount'           => $workshop_item->getDownloadCount(),
                'originalAuthor'          => $workshop_item->getOriginalAuthor(),
                'originalCreationDate'    => $workshop_item->getOriginalCreationDate(),
                'thumbnail'               => $workshop_item->getThumbnail(),
                'images'                  => \count($workshop_item->getImages()) === 0 ? [] : [
                    0 => [
                        'filename' => $workshop_item->getImages()->first()->getFilename(),
                    ]
                ],
                'ratingScore'             => $workshop_item->getRatingScore(),
                'difficultyRatingScore'   => $workshop_item->getDifficultyRatingScore(),
                'comment_count'           => \count($workshop_item->getComments()),
                'minGameBuild'            => $workshop_item->getMinGameBuild(),
                'isLastFileBroken'        => $workshop_item->isLastFileBroken(),
            ];
        }

        // View data for the Twig template
        $view_data = [
            'workshop_items'                => $workshop_items_serialized,
            'categories'                    => WorkshopCategory::cases(),
            'categories_without_difficulty' => Config::get('app.workshop.item_categories_without_difficulty'),
            // 'tags'                          => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
        ];

        // Cache the view data
        $workshop_cache->setCachedBrowsePageData($user_cache_id, $view_data);

        // // Render view
        $response->getBody()->write(
            $twig->render('workshop/user.workshop.html.twig',
                \array_merge($view_data, ['user' => $user]) // User needs to be dynamic
            )
        );

        return $response;
    }
}
