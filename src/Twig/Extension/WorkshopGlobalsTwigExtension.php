<?php

namespace App\Twig\Extension;

use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\GithubRelease;

use App\Config\Config;
use Doctrine\ORM\EntityManager;
use App\Workshop\WorkshopHelper;

use Psr\SimpleCache\CacheInterface;

class WorkshopGlobalsTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    public function __construct(
        private EntityManager $em,
        private CacheInterface $cache,
    ) {}

    public function getName(): string
    {
        return 'workshop_globals_extension';
    }

    public function getGlobals(): array
    {
        // Create categories map
        $categories_map = [];
        foreach (WorkshopCategory::cases() as $enum) {
            $categories_map[$enum->value] = $enum->name;
        }

        // Get stable builds
        $stable_builds = $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        // Create stable builds map
        $stable_builds_map = [];
        foreach ($stable_builds as $stable_build) {
            $stable_builds_map[$stable_build->getId()] = $stable_build;
        }

        // Get the latest minors of the stable releases
        // Example: 1.2.2, 1.1.5, 1.0.1
        $latest_minor_releases = $this->cache->get('latest-stable-minor-releases', null);
        if (is_null($latest_minor_releases)) {

            /** @var GithubRelease $entity */
            foreach ($stable_builds as $entity) {

                $version_parts = $entity->getVersionParts();

                if ($version_parts) {

                    $major = $version_parts['major'];
                    $minor = $version_parts['minor'];
                    $patch = $version_parts['patch'];

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
                        'id'                => $entity->getId(),
                        'name'              => $entity->getName(),
                        'downloadUrl'       => $entity->getDownloadUrl(),
                        'sizeInBytes'       => $entity->getSizeInBytes(),
                        'timestamp'         => $entity->getTimestamp(),
                        'tag'               => $entity->getTag(),
                        'version'           => $entity->getVersion(),
                        'linkedNewsPost'    => $news_post,
                        'mirrors'           => $mirrors,
                        'commits'           => ['count' => \count($entity->getCommits())],
                        'versionMajorMinor' => "{$major}.{$minor}",
                        'nameMajorMinor'    => "KeeperFX {$major}.{$minor}",
                    ];
                }
            }

            // Order stable releases
            $stable_releases = WorkshopHelper::sortStableReleases($stable_releases, true);

            // Get latest patch for each minor version
            foreach ($stable_releases as $major => $minor_array) {
                foreach ($minor_array as $minor => $patch_array) {
                    $latest_minor_releases[] = \reset($patch_array);
                }
            }

            // Store in cache
            $this->cache->set('latest-stable-minor-releases', $latest_minor_releases, 60); // 1 minute
        }

        return [
            'workshop_globals' => [
                'categories'                    => WorkshopCategory::cases(),
                'categories_without_difficulty' => Config::get('app.workshop.item_categories_without_difficulty'),
                'categories_map'                => $categories_map,
                'tags'                          => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'stable_builds'                 => $stable_builds_map,
                'latest_stable_minor_releases'  => $latest_minor_releases,
            ]
        ];
    }
}
