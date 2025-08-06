<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\LauncherRelease;
use App\Entity\GithubAlphaBuild;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DownloadController
{

    public function downloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        CacheInterface $cache,
    ) {
        // Get the shown stable releases
        $shown_stable_releases = $cache->get('stable-downloads', []);
        if (empty($shown_stable_releases)) {

            // Get stable releases
            $stable_release_entities = $em->getRepository(GithubRelease::class)->findAll();
            /** @var GithubRelease $entity */
            foreach ($stable_release_entities as $entity) {

                if (\preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $entity->getVersion(), $matches)) {
                    $major = (int)$matches[1];
                    $minor = (int)$matches[2];
                    $patch = (int)$matches[3];

                    $news_post = null;
                    $news_post_entity = $entity->getLinkedNewsPost();
                    if ($news_post_entity) {
                        $news_post = [
                            'id'               => $news_post_entity->getId(),
                            'createdTimestamp' => $news_post_entity->getCreatedTimestamp(),
                            'titleSlug'        => $news_post_entity->getTitleSlug(),
                        ];
                    }

                    $mirrors = [];
                    $mirror_entities = $entity->getMirrors();
                    if ($mirror_entities) {
                        foreach ($mirror_entities as $mirror_entity) {
                            $mirrors[] = [
                                'url' => $mirror_entity->getUrl(),
                            ];
                        }
                    }

                    $stable_releases[$major][$minor][$patch] = [
                        'name'           => $entity->getName(),
                        'downloadUrl'    => $entity->getDownloadUrl(),
                        'sizeInBytes'    => $entity->getSizeInBytes(),
                        'timestamp'      => $entity->getTimestamp(),
                        'tag'            => $entity->getTag(),
                        'linkedNewsPost' => $news_post,
                        'mirrors'        => $mirrors,
                        'commits'        => ['count' => \count($entity->getCommits())],
                    ];
                }
            }

            // Order stable releases
            $stable_releases = $this->sortStableReleases($stable_releases, true);

            // Get latest patch for each minor version
            $i = 0;
            foreach ($stable_releases as $major => $minor_array) {
                foreach ($minor_array as $minor => $patch_array) {
                    $shown_stable_releases[] = \reset($patch_array);
                    if (++$i == 3) {
                        break 2;
                    }
                }
            }

            // Store in cache
            $cache->set('stable-downloads', $shown_stable_releases, 60); // 1 minute
        }

        // Get alpha and launcher releases
        $alpha_builds    = $em->getRepository(GithubAlphaBuild::class)->findBy(['is_available' => true], ['workflow_run_id' => 'DESC', 'timestamp' => 'DESC'], 5);
        $launcher        = $em->getRepository(LauncherRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        // Output
        $response->getBody()->write(
            $twig->render('downloads.html.twig', [
                'stable_releases' => $shown_stable_releases,
                'alpha_builds'    => $alpha_builds,
                'launcher'        => $launcher,
            ])
        );
        return $response;
    }

    public function stableDownloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $stable_releases = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('downloads.stable.html.twig', [
                'stable_releases' => $stable_releases,
            ])
        );

        return $response;
    }

    public function alphaDownloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $alpha_builds    = $em->getRepository(GithubAlphaBuild::class)->findBy(['is_available' => true], ['workflow_run_id' => 'DESC', 'timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('downloads.alpha.html.twig', [
                'alpha_builds'    => $alpha_builds,
            ])
        );

        return $response;
    }

    /**
     * Sorts the nested [major][minor][patch] array.
     *
     * @param array $releases The input array: $releases[$major][$minor][$patch] = $entity
     * @param bool  $descending If true, sorts newest-first (e.g., 3.2.1 before 1.0.0)
     * @return array Sorted array with the same nesting.
     */
    function sortStableReleases(array $releases, bool $descending = false): array
    {
        $func = $descending ? 'krsort' : 'ksort';

        $func($releases, SORT_NUMERIC);

        foreach ($releases as &$minors) {

            $func($minors, SORT_NUMERIC);

            foreach ($minors as &$patches) {
                $func($patches, SORT_NUMERIC);
            }

            unset($patches);
        }

        unset($minors);

        return $releases;
    }
}
