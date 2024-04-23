<?php

namespace App\Controller\AdminCP;

use App\Entity\NewsArticle;

use App\Account;
use App\FlashMessage;
use App\DiscordNotifier;
use App\Entity\GithubRelease;
use App\UploadSizeHelper;
use App\Helper\ThumbnailHelper;

use Slim\Csrf\Guard;
use Doctrine\ORM\EntityManager;
use ByteUnits\Binary as BinaryFormatter;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

class AdminReleasesController {

    public function releasesIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $releases = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('admincp/releases/releases.admincp.html.twig', [
                'releases' => $releases
            ])
        );

        return $response;
    }

    public function releaseEditIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $id
    ){
        // Get the release
        $release = $em->getRepository(GithubRelease::class)->find($id);
        if(!$release){
            throw new HttpNotFoundException($request);
        }

        // Get news articles
        $news_articles = $em->getRepository(NewsArticle::class)->findBy([], ['id'=>'DESC']);

        // Render
        $response->getBody()->write(
            $twig->render('admincp/releases/releases.edit.admincp.html.twig', [
                'release'       => $release,
                'news_articles' => $news_articles,
            ])
        );
        return $response;
    }

    public function releaseEdit(
        Request $request,
        Response $response,
        FlashMessage $flash,
        EntityManager $em,
        $id
    ){
        // Get the release
        $github_release = $em->getRepository(GithubRelease::class)->find($id);
        if(!$github_release){
            throw new HttpNotFoundException($request);
        }

        // Get the post vars
        $post = $request->getParsedBody();

        // Check if we need to update the linked news post
        if(!empty($post['news']) && \is_numeric($post['news'])){
            $article_id = (int)$post['news'];
            if($article_id === 0){
                $github_release->setLinkedNewsPost(null);
            }
            $article = $em->getRepository(NewsArticle::class)->find($article_id);
            if($article){
                $github_release->setLinkedNewsPost($article);
            }
        }

        // Save changes to DB
        $em->flush();

        // Show message and go back to the page
        $flash->success("Release updated");
        $response = $response->withHeader('Location', '/admin/releases/' . $id)->withStatus(302);
        return $response;
    }
}
