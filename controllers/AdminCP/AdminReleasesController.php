<?php

namespace App\Controller\AdminCP;

use App\Entity\NewsArticle;
use App\Entity\GithubRelease;
use App\Entity\ReleaseMirror;

use App\Account;
use App\FlashMessage;
use App\DiscordNotifier;
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

class AdminReleasesController
{

    public function releasesIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
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
    ) {
        // Get the release
        $release = $em->getRepository(GithubRelease::class)->find($id);
        if (!$release) {
            throw new HttpNotFoundException($request);
        }

        // Render
        $response->getBody()->write(
            $twig->render('admincp/releases/releases.edit.admincp.html.twig', [
                'release'       => $release,
                'news_articles' => $em->getRepository(NewsArticle::class)->findBy([], ['id' => 'DESC']),
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
    ) {
        // Get the release
        $github_release = $em->getRepository(GithubRelease::class)->find($id);
        if (!$github_release) {
            throw new HttpNotFoundException($request);
        }

        // Get the post vars
        $post = $request->getParsedBody();

        // Check if we need to update the linked news post
        if (!empty($post['news']) && \is_numeric($post['news'])) {
            $article_id = (int)$post['news'];
            if ($article_id === 0) {
                $github_release->setLinkedNewsPost(null);
            }
            $article = $em->getRepository(NewsArticle::class)->find($article_id);
            if ($article) {
                $github_release->setLinkedNewsPost($article);
            }
        }

        // Remove all existing mirrors
        $mirrors = $github_release->getMirrors();
        if ($mirrors !== null) {
            foreach ($github_release->getMirrors() as $mirror) {
                $em->remove($mirror);
            }
        }

        // Get mirrors and add them again
        if (!empty($post['mirrors']) && is_array($post['mirrors'])) {

            // Loop trough all given mirror strings
            foreach ($post['mirrors'] as $mirror_string) {

                if (!\is_string($mirror_string)) {
                    throw new HttpBadRequestException($request);
                }

                if (\filter_var($mirror_string, FILTER_VALIDATE_URL) === FALSE) {
                    $flash->success("Invalid mirror URL: {$mirror_string}");
                    continue;
                }

                // Create release mirror
                $release_mirror = new ReleaseMirror();
                $release_mirror->setUrl($mirror_string);
                $release_mirror->setRelease($github_release);
                $em->persist($release_mirror);
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
