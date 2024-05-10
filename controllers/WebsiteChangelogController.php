<?php

namespace App\Controller;

use App\FlashMessage;
use SebastianFeldmann\Git\Repository;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;

class WebsiteChangelogController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        CacheInterface $cache,
    ){
        $commits = $cache->get("website-changelog-commits");

        if($commits === null)
        {
            $commits = [];

            try {
                // Load git repo and load commits
                $repo = new Repository(APP_ROOT);
                $log  = $repo->getLogOperator();
                $all_commits = $log->getCommitsSince("5ab2556bce81c5e247dcc098c8b0f8150a40747d");

            } catch (\Exception $ex){

                // Failed to get commits
                $flash->error("Failed to get website changelog");
                $response->getBody()->write(
                    $twig->render('website.changelog.html.twig', ['commits' => null])
                );
                return $response;
            }

            // Combine commits by date
            foreach($all_commits as $commit)
            {
                $date_str = $commit->getDate()->format('Y-m-d');

                // Add to commit array
                // Also get all data out of the objects so we can easily store in cache
                $commits[$date_str][] = [
                    'author'  => (string) $commit->getAuthor(),
                    'body'    => (string) $commit->getBody(),
                    'hash'    => (string) $commit->getHash(),
                    'subject' => (string) $commit->getSubject(),
                    'date'    => $commit->getDate(),
                ];
            }

            // Store commits into cache
            $cache->set("website-changelog-commits", $commits);
        }

        // Response
        $response->getBody()->write(
            $twig->render('website.changelog.html.twig', [
                'commits' => $commits,
            ])
        );

        return $response;
    }

}
