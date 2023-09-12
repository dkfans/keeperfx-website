<?php

namespace App\Controller;

use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use App\Entity\NewsArticle;

use FeedWriter\RSS2;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\CommonMark\CommonMarkConverter;

class MasterServerController {

    public function list(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('masterserver.list.html.twig')
        );

        return $response;
    }

    public function ajaxList(
        Request $request,
        Response $response,
    )
    {


    }

}
