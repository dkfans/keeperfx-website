<?php

namespace App\Controller\Admin;

use App\Account;
use App\Entity\NewsArticle;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;
use Slim\Csrf\Guard;


class AdminNewsController {

    public function newsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('control-panel/admin/news.admin.cp.html.twig', [
                'articles' => $articles
            ])
        );

        return $response;
    }

    public function newsAddIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('control-panel/admin/news.add.admin.cp.html.twig')
        );

        return $response;
    }

    public function newsAdd(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        FlashMessage $flash
    ){

        $post     = $request->getParsedBody();
        $title    = (string) ($post['title'] ?? null);
        $contents = (string) ($post['contents'] ?? null);

        if($contents && $title){
            $article = new NewsArticle();
            $article->setTitle($title);
            $article->setText($contents);
            $article->setShortText($contents);
            $article->setAuthor($account->getUser());

            $em->persist($article);
            $em->flush();

            $flash->success('News article posted!');
        }

        $response = $response->withHeader('Location', '/admin/news')->withStatus(302);
        return $response;
    }

    public function newsEditIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){

        $article = $em->getRepository(NewsArticle::class)->find($id);

        if(!$article){
            $flash->warning('News article not found.');
            $response = $response->withHeader('Location', '/admin/news')->withStatus(302);
            return $response;
        }


        $response->getBody()->write(
            $twig->render('control-panel/admin/news.edit.admin.cp.html.twig', [
                'article' => $article,
            ])
        );

        return $response;

    }

    public function newsEdit(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){

        $article = $em->getRepository(NewsArticle::class)->find($id);

        $post     = $request->getParsedBody();
        $title    = (string) ($post['title'] ?? null);
        $contents = (string) ($post['contents'] ?? null);

        if(!$article){
            $flash->warning('News article not found.');
        } else {

            // Update article in DB
            $article->setTitle($title);
            $article->setText($contents);
            $article->setShortText($contents);
            $em->flush();

            $flash->success('News article updated!');
        }

        $response = $response->withHeader('Location', '/admin/news')->withStatus(302);
        return $response;
    }

    public function newsDelete(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Guard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if($valid){

            $article = $em->getRepository(NewsArticle::class)->find($id);
            if($article){

                $em->remove($article);
                $em->flush();
                $flash->success('News article successfully removed!');
            }
        }

        $response = $response->withHeader('Location', '/admin/news')->withStatus(302);
        return $response;
    }

}
