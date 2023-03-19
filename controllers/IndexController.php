<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\NewsArticle;
use App\Entity\WorkshopItem;

use App\Enum\UserRole;

use App\Account;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;

class IndexController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        CacheInterface $cache,
        FlashMessage $flash,
        Account $account
    ){
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 3);
        $release = $em->getRepository(GithubRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        // Show a notice to users with a Moderator role or higher if there's new workshop items
        if($account->isLoggedIn() && $account->getUser()->getRole()->value >= UserRole::Moderator->value){
            $open_workshop_submissions = $em->getRepository(WorkshopItem::class)->findBy(['is_accepted' => false]);
            if($open_workshop_submissions && \count($open_workshop_submissions) > 0){
                $flash->info('There are open workshop submissions. Click <a href="/moderate/workshop/list">here</a> to view them.');
            }
        }

        $response->getBody()->write(
            $twig->render('index.html.twig', [
                'articles'      => $articles,
                'release'       => $release,
                'forum_threads' => $cache->get('keeperfx_forum_threads', []),
            ])
        );

        return $response;
    }

}
