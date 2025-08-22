<?php

namespace App\Controller\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Workshop\WorkshopCache;
use DebugBar\StandardDebugBar;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopBrowseController
{

    public function browseIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        WorkshopCache $workshop_cache,
        Account $account,
        FlashMessage $flash,
        StandardDebugBar $debugbar,
    ) {

        // Get queries
        $q = $request->getQueryParams();

        // Check if this page is already cached
        $cached_view_data = $workshop_cache->getCachedBrowsePageData($q);
        if ($cached_view_data) {
            $response->getBody()->write(
                $twig->render('workshop/browse.workshop.html.twig', $cached_view_data)
            );
            return $response;
        }

        // Start measuring
        $debugbar['time']->startMeasure('browse', 'Workshop browse page query');

        // Remember URL params to return for links
        $url_params = [];

        // Get current page
        $page = $q['page'] ?? 1;
        if (!\is_numeric($page)) {
            $page = 1;
        }
        $page = (int) $page;

        // Create query
        $query = $em->getRepository(WorkshopItem::class)->createQueryBuilder('item')
            ->where('item.is_published = 1');

        // Get show item limit
        // TODO: make this user configurable
        $limit     = 50;

        // Set variables for author so we can show nice pages for them
        $submitter = null;
        $original_author = null;

        // Get order by param
        $order_by_param = $q['order_by'] ?? '';
        if (!\is_string($order_by_param)) {
            $order_by_param = 'latest';
        }

        // Decide 'ORDER BY'
        switch (\strtolower($order_by_param)) {
            case 'name':
                $query = $query->orderBy('item.name', 'ASC');
                $url_params['order_by'] = 'name';
                break;
            case 'most-downloaded':
                $query = $query->orderBy('item.download_count', 'DESC');
                $url_params['order_by'] = 'most-downloaded';
                break;
            case 'least-downloaded':
                $query = $query->orderBy('item.download_count', 'ASC');
                $url_params['order_by'] = 'least-downloaded';
                break;
            case 'highest-rated':
                $query = $query->orderBy('item.rating_score', 'DESC');
                $query = $query->andWhere($query->expr()->isNotNull('item.rating_score'));
                $url_params['order_by'] = 'highest-rated';
                break;
            case 'lowest-rated':
                $query = $query->orderBy('item.rating_score', 'ASC');
                $query = $query->andWhere($query->expr()->isNotNull('item.rating_score'));
                $url_params['order_by'] = 'lowest-rated';
                break;
            case 'most-difficult':
                $query = $query->orderBy('item.difficulty_rating_score', 'DESC');
                $query = $query->andWhere($query->expr()->isNotNull('item.difficulty_rating_score'));
                $query = $query->andWhere('item.difficulty_rating_enabled = 1');
                $url_params['order_by'] = 'most-difficult';
                break;
            case 'least-difficult':
                $query = $query->orderBy('item.difficulty_rating_score', 'ASC');
                $query = $query->andWhere($query->expr()->isNotNull('item.difficulty_rating_score'));
                $query = $query->andWhere('item.difficulty_rating_enabled = 1');
                $url_params['order_by'] = 'least-difficult';
                break;
            case 'oldest':
                $query = $query->orderBy('item.creation_orderby_timestamp', 'ASC');
                $url_params['order_by'] = 'oldest';
                break;
            default:
            case 'latest':
                $query = $query->orderBy('item.creation_orderby_timestamp', 'DESC');
                $url_params['order_by'] = 'latest';
                break;
            case 'last-updated':
                $query = $query->orderBy(
                    $query->expr()->desc(
                        'CASE WHEN item.updated_timestamp > item.creation_orderby_timestamp THEN item.updated_timestamp ELSE item.creation_orderby_timestamp END'
                    )
                );
                $url_params['order_by'] = 'last-updated';
                break;
            case 'highest-rated-wilson':
                // Wilson score ordering
                // Aggregate AVG and COUNT of ratings, then apply Wilson formula.
                // z_score = 1.96 (≈95% confidence)
                $z_score = 1.96;
                $query = $query
                    ->leftJoin('item.ratings', 'r')
                    ->addSelect('AVG(r.score) AS HIDDEN avg_score')
                    ->addSelect('COUNT(r.id) AS HIDDEN rating_count')
                    ->groupBy('item.id')
                    ->orderBy(
                        "(
                        (
                            ((AVG(r.score) / 5) + ($z_score * $z_score) / (2 * COUNT(r.id)))
                            - $z_score * SQRT(
                                ((AVG(r.score) / 5) * (1 - (AVG(r.score) / 5)) + ($z_score * $z_score) / (4 * COUNT(r.id) * COUNT(r.id)))
                                / COUNT(r.id)
                            )
                        ) / (1 + ($z_score * $z_score) / COUNT(r.id))
                        ) * 5",
                        'DESC'
                    );
                $url_params['order_by'] = 'wilson-rated';
                break;
        }

        // Add search criteria
        if (isset($q['search']) && \is_string($q['search'])) {
            $url_params['search'] = $q['search'];
            $query = $query->leftJoin('item.submitter', 'submitter');
            $search_params = \explode(" ", $q['search']);
            foreach ($search_params as $i => $search_param) {
                $query = $query->andWhere($query->expr()->orX(
                    $query->expr()->like('item.name', ':search' . $i),
                    $query->expr()->like('item.original_author', ':search' . $i),
                    $query->expr()->like('item.map_number', ':search' . $i),
                    $query->expr()->like('submitter.username', ':search' . $i)
                ))->setParameter('search' . $i, '%' . $search_param . '%');
            }
        }

        // Add category criteria
        if (isset($q['category']) && \is_numeric($q['category'])) {
            $url_params['category'] = $q['category'];
            $query                  = $query->andWhere('item.category = :category')->setParameter('category', $q['category']);
        }

        // Add user criteria
        if (isset($q['user']) && \is_string($q['user'])) {
            $username = $q['user'];

            if ($username === 'keeperfx-team') {
                $query                 = $query->andWhere('item.submitter IS NULL');
                $submitter             = 'KeeperFX Team';
                $url_params['user']    = 'keeperfx-team';
            } else {

                $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
                if (!$user) {
                    $flash->warning('User not found.');
                    $response->getBody()->write(
                        $twig->render('workshop/alert.workshop.html.twig', [
                            'categories'          => WorkshopCategory::cases(),
                            'tags'           => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                            'builds'         => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
                        ])
                    );
                    return $response;
                }

                $query                       = $query->andWhere('item.submitter = ' . $user->getId());
                $query                       = $query->andWhere('item.original_author IS NULL');
                $submitter                   = $user->getUsername();
                $url_params['user']          = $user->getUsername();

                // When we are checking a single user, we want to hide broken items except if we are looking at our own items
                // NOTE: queries are cached so this is useless. We'll do this in the view instead
                // if($account->isLoggedIn() && $account->getUser()->getUsername() !== $username){
                //     $query = $query->andWhere('item.is_last_file_broken = 0');
                // }
            }
        } else {

            // Always hide broken items when not on a single user page
            // NOTE: queries are cached so this is useless. We'll do this in the view instead
            // $query = $query->andWhere('item.is_last_file_broken = 0');

        }

        // Add original author criteria
        if (!isset($q['user']) && isset($q['original_author']) && \is_string($q['original_author'])) {
            $url_params['original_author'] = $q['original_author'];
            $query                         = $query->andWhere('item.original_author = :original_author')->setParameter('original_author', $q['original_author']);
            $original_author               = $q['original_author'];
        }

        // Get total workshop item count
        // Reset groupBy DQL part for the wilson rating (which uses groups)
        $workshop_item_count = (clone $query)->select('count(DISTINCT item.id)')->resetDQLPart('groupBy')->getQuery()->getSingleScalarResult();

        // Get total pages
        $total_pages = \intval(\ceil($workshop_item_count / $limit));

        // Calculate offset
        $offset = $limit * ($page - 1);

        // Make sure offset (page) is valid
        if ($page <= 0 || $offset > $workshop_item_count) {
            $response = $response->withHeader(
                'Location',
                '/workshop/browse?' . \http_build_query($url_params + ['page' => 1]),
            )->withStatus(302);
            return $response;
        }

        // Create pagination
        $pagination = [];
        if ($total_pages <= 5) {
            for ($i = 1; $i <= $total_pages; $i++) {
                $pagination[] = [
                    'label' => (string) $i,
                    'active' => $page === $i,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $i]),
                ];
            }
        } else {

            if ($page >= 3) {

                $pagination[] = [
                    'label' => '1',
                    'active' => $page === 1,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => 1]),
                ];

                if ($page > 3) {
                    $pagination[] = [
                        'label' => '...',
                        'active' => false,
                        'disabled' => true,
                        'url'    => null,
                    ];
                }
            }

            if ($page !== 1) {
                $pagination[] = [
                    'label' => (string) ($page - 1),
                    'active' => false,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page - 1]),
                ];
            }

            if ($page !== $total_pages) {
                $pagination[] = [
                    'label' => (string) $page,
                    'active' => true,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page]),
                ];
            }

            if ($page < $total_pages - 1) {
                $pagination[] = [
                    'label' => (string) ($page + 1),
                    'active' => false,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page + 1]),
                ];
            }

            if ($page === 1) {
                $pagination[] = [
                    'label' => (string) ($page + 2),
                    'active' => false,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page + 2]),
                ];
            }

            if ($page < $total_pages - 2) {
                $pagination[] = [
                    'label' => '...',
                    'active' => false,
                    'disabled' => true,
                    'url'    => null,
                ];
            }

            $pagination[] = [
                'label' => (string) $total_pages,
                'active' => $page === $total_pages,
                'disabled' => false,
                'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $total_pages]),
            ];
        }

        // Add offset and limit
        $query = $query->setFirstResult($offset)->setMaxResults($limit);

        // Get workshop items
        $workshop_items = [];
        $result = $query->getQuery()->getResult();
        foreach ($result as $workshop_item) {
            $workshop_items[] = [
                'id' => $workshop_item->getId(),
                'name' => $workshop_item->getName(),
                'submitter' => $workshop_item->getSubmitter() === null ? null : [
                    'id'          => $workshop_item->getSubmitter()->getId(),
                    'username'    => $workshop_item->getSubmitter()->getUsername(),
                    'avatar'      => $workshop_item->getSubmitter()->getAvatar(),
                    'avatarSmall' => $workshop_item->getSubmitter()->getAvatarSmall(),
                    'role'        => $workshop_item->getSubmitter()->getRole(),
                ],
                'category'                => $workshop_item->getCategory(),
                'createdTimestamp'        => $workshop_item->getCreatedTimestamp(),
                'updatedTimestamp'        => $workshop_item->getUpdatedTimestamp(),
                'difficultyRatingEnabled' => $workshop_item->isDifficultyRatingEnabled(),
                'downloadCount'           => $workshop_item->getDownloadCount(),
                'originalAuthor'          => $workshop_item->getOriginalAuthor(),
                'originalCreationDate'    => $workshop_item->getOriginalCreationDate(),
                'thumbnail'               => $workshop_item->getThumbnail(),
                'images'                  => \count($workshop_item->getImages()) === 0 ? [] : [
                    0 => [
                        'filename' => $workshop_item->getImages()->first()->getFilename(),
                    ]
                ],
                'ratingScore'             => $workshop_item->getRatingScore(),
                'difficultyRatingScore'   => $workshop_item->getDifficultyRatingScore(),
                'comment_count'           => \count($workshop_item->getComments()),
                'minGameBuild'            => $workshop_item->getMinGameBuild(),
                'isLastFileBroken'        => $workshop_item->isLastFileBroken(),
            ];
        }

        // View data for the Twig template
        $view_data = [
            'workshop_items'                => $workshop_items,
            'categories'                    => WorkshopCategory::cases(),
            'categories_without_difficulty' => Config::get('app.workshop.item_categories_without_difficulty'),
            // 'tags'                          => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
            'pagination'                    => $pagination,
            'submitter'                     => $submitter,
            'original_author'               => $original_author,
        ];

        // Stop measure
        $debugbar['time']->stopMeasure('browse');

        // Cache the view data
        $workshop_cache->setCachedBrowsePageData($q, $view_data);

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/browse.workshop.html.twig', $view_data)
        );

        return $response;
    }
}
