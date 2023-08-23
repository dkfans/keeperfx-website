<?php

namespace App\Controller\Api\v1;

// use App\Entity\NewsArticle;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;
use App\Entity\GithubRelease;
use App\Entity\GithubAlphaBuild;

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
                'download_url'    => $_ENV['APP_ROOT_URL'] . '/download/alpha/' . \urlencode($alpha_build->getFilename())
            ]])
        );

        return $response;
    }

}
