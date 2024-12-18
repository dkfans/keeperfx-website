<?php

namespace App\Controller\Api\v1;

// use App\Entity\NewsArticle;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;
use App\Entity\GithubRelease;
use App\Entity\GithubAlphaBuild;
use App\Entity\NewsArticle;

class ReleaseApiController {

    public function latestStable(
        Request $request,
        Response $response,
        EntityManager $em,
        // TODO: CacheInterface $cache,
    ){
        /** @var GithubRelease $release */
        $release = $em->getRepository(GithubRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            \json_encode(['release' => [
                'name'          => $release->getName(),
                'tag'           => $release->getTag(),
                'timestamp'     => $release->getTimestamp()->format('c'), // ISO 8601 date
                'download_url'  => $release->getDownloadUrl(),
                'size_in_bytes' => $release->getSizeInBytes(),
            ]])
        );

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;
    }

    public function latestAlpha(
        Request $request,
        Response $response,
        EntityManager $em,
        // TODO: CacheInterface $cache,
    ){
        /** @var GithubAlphaBuild $alpha_build */
        $alpha_build = $em->getRepository(GithubAlphaBuild::class)->findOneBy(['is_available' => true], ['workflow_run_id' => 'DESC', 'timestamp' => 'DESC']);

        $response->getBody()->write(
            \json_encode(['alpha_build' => [
                'artifact_id'     => $alpha_build->getArtifactId(),
                'name'            => $alpha_build->getName(),
                'workflow_title'  => $alpha_build->getWorkflowTitle(),
                'workflow_run_id' => $alpha_build->getWorkflowRunId(),
                'filename'        => $alpha_build->getFilename(),
                'timestamp'       => $alpha_build->getTimestamp()->format('c'), // ISO 8601 date
                'size_in_bytes'   => $alpha_build->getSizeInBytes(),
                'download_url'    => APP_ROOT_URL . '/download/alpha/' . \urlencode($alpha_build->getFilename())
            ]])
        );

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;
    }

    public function checkStableUpdate(
        Request $request,
        Response $response,
        EntityManager $em,
        string $version,
    ){
        $response = $response->withHeader('Content-Type', 'application/json');

        // Clean up version, only keep numbers and dots
        // Tags in the DB are in format: 'v1.2.3'
        $version = \preg_replace('/[^0-9.]/', '', $version);
        $version = 'v' . $version;

        // Get the release the user has
        /** @var GithubRelease $release */
        $release = $em->getRepository(GithubRelease::class)->findOneBy(['tag' => $version]);
        if(!$release){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'VERSION_NOT_FOUND',
                ])
            );
            return $response;
        }

        /** @var GithubRelease $release */
        $latest_release = $em->getRepository(GithubRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        // Check if still up to date
        if($release === $latest_release){

            // Still up to date
            $response->getBody()->write(
                \json_encode([
                    'success'     => true,
                    'new_version' => false
                ])
            );
            return $response;
        }

        // New version!

        // Get news for the latest release
        $news = null;
        /** @var NewsArticle $article */
        $article = $latest_release->getLinkedNewsPost();
        if($article){
            $news = [
                'title'     => $article->getTitle(),
                'timestamp' => $article->getCreatedTimestamp()->format('c'),
                'excerpt'   => $article->getExcerpt(),
                'contents'  => $article->getContents(),
                'image'     => $article->getImage(),
            ];
        }

        $response->getBody()->write(
            \json_encode([
                'success'     => true,
                'new_version' => true,
                'release'     => [
                    'name'          => $latest_release->getName(),
                    'tag'           => $latest_release->getTag(),
                    'timestamp'     => $latest_release->getTimestamp()->format('c'), // ISO 8601 date
                    'download_url'  => $latest_release->getDownloadUrl(),
                    'size_in_bytes' => $latest_release->getSizeInBytes(),
                    'news_article'  => $news,
                ],
            ])
        );

        return $response;
    }

    public function checkAlphaUpdate(
        Request $request,
        Response $response,
        EntityManager $em,
        string $version,
    ) {
        $response = $response->withHeader('Content-Type', 'application/json');

        // Clean up version, only keep numbers and dots
        $version = \preg_replace('/[^0-9.]/', '', $version);

        // Get version parts
        $version_parts = \explode('.', $version);
        if(\count($version_parts) != 4){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'INVALID_VERSION',
                ])
            );
            return $response;
        }

        // Get patch name
        $patch_name = \sprintf(
            "keeperfx-%d_%d_%d_%d_Alpha-patch",
            $version_parts[0],
            $version_parts[1],
            $version_parts[2],
            $version_parts[3],
        );

        // Get the alpha patch the user has
        /** @var GithubAlphaBuild $release */
        $alpha_patch = $em->getRepository(GithubAlphaBuild::class)->findOneBy(['name' => $patch_name, 'is_available' => true]);
        if(!$alpha_patch){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'VERSION_NOT_FOUND',
                ])
            );
            return $response;
        }

        // Get all the patches after this one
        $qb = $em->getRepository(GithubAlphaBuild::class)->createQueryBuilder('g');
        $alpha_patches = $qb->where('g.timestamp > :timestamp')
            ->andWhere('g.is_available = true')
            ->setParameter('timestamp', $alpha_patch->getTimestamp())
            ->orderBy('g.timestamp', 'DESC')
            ->getQuery()
            ->getResult();

        // Check if there are no updates
        if(\count($alpha_patches) == 0){
            $response->getBody()->write(
                \json_encode([
                    'success'     => true,
                    'new_version' => false,
                ])
            );
            return $response;
        }

        // Remember the last patch as this one the user wants to update to
        $last_patch = $alpha_patches[0];

        // There are updates, we'll make a list of patches
        // This way any application can show the titles of the alpha patches and such
        $patches = [];
        foreach($alpha_patches as $patch)
        {
            $patches[] = [
                'name'           => $patch->getName(),
                'url'            => APP_ROOT_URL . '/download/alpha/' . \urlencode($patch->getFilename()),
                'timestamp'      => $patch->getTimestamp()->format('c'),
                'workflow_title' => $patch->getWorkflowTitle(),
            ];
        }

        // Return
        $response->getBody()->write(
            \json_encode([
                'success'     => true,
                'new_patch'   => true,
                'new_patches' => $patches,
                'alpha_patch' => [
                    'name'      => $last_patch->getName(),
                    'url'       => APP_ROOT_URL . '/download/alpha/' . \urlencode($last_patch->getFilename()),
                    'timestamp' => $last_patch->getTimestamp()->format('c'),
                ],
            ])
        );
        return $response;
    }

}
