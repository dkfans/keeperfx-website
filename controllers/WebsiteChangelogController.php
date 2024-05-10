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

            // Run 'git log'
            $result = \shell_exec("git log");
            if(!$result){
                throw new \Exception("no 'git log' result");
            }

            // Get all commits
            $preg_result = \preg_match_all("/commit\s([a-f0-9]+)\nAuthor\:\s(.+)\nDate\:\s+(.+)\n\n\s+(.+)/", $result, $matches, \PREG_SET_ORDER);
            if(!$preg_result){
                throw new \Exception("No preg result");
            }

            // Loop trough commits
            foreach($matches as $match){

                // Get date
                $date_time = new \DateTime($match[3]);
                $date_str  = $date_time->format('Y-m-d');

                // Add to commits list
                $commits[$date_str][] = [
                    'hash'    => $match[1],
                    'author'  => $match[2],
                    'date'    => $date_time,
                    'subject' => $match[4],
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
