<?php

namespace App\Controller\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\GithubRelease;
use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;

use Doctrine\Common\Collections\ArrayCollection;

use App\FlashMessage;
use App\Config\Config;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopBrowseController {

    public function browseIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash

    ){
        // Get queries
        $q = $request->getQueryParams();

        // Remember URL params to return for links
        $url_params = [];

        // Get current page
        $page = $q['page'] ?? 1;
        if(!\is_numeric($page)){
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
        if(!\is_string($order_by_param)){
            $order_by_param = 'latest';
        }

        // Decide 'ORDER BY'
        switch(\strtolower($order_by_param)){
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
                $url_params['order_by'] = 'highest-rated';
                break;
            case 'lowest-rated':
                $query = $query->orderBy('item.rating_score', 'ASC');
                $url_params['order_by'] = 'lowest-rated';
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
        }

        // Add search criteria
        if(isset($q['search']) && \is_string($q['search'])){
            $url_params['search'] = $q['search'];
            $query                = $query->andWhere($query->expr()->orX(
                $query->expr()->like('item.name', ':search'),
                $query->expr()->like('item.original_author', ':search'),
                $query->expr()->like('item.map_number', ':search')
            ))->setParameter('search', '%' . $q['search'] . '%');
            // TODO: implement search by submitter username
        }

        // Add category criteria
        if(isset($q['category']) && \is_numeric($q['category'])){
            $url_params['category'] = $q['category'];
            $query                  = $query->andWhere('item.category = :category')->setParameter('category', $q['category']);
        }

        // Add user criteria
        if(isset($q['user']) && \is_string($q['user'])){
            $username = $q['user'];

            if($username === 'keeperfx-team'){
                $query                 = $query->andWhere('item.submitter IS NULL');
                $submitter             = 'KeeperFX Team';
                $url_params['user']    = 'keeperfx-team';

            } else {

                $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
                if(!$user){
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
            }
        }

        // Add original author criteria
        if(!isset($q['user']) && isset($q['original_author']) && \is_string($q['original_author'])){
            $url_params['original_author'] = $q['original_author'];
            $query                         = $query->andWhere('item.original_author = :original_author')->setParameter('original_author', $q['original_author']);
            $original_author               = $q['original_author'];
        }

        // Get total workshop item count
        $workshop_item_count = (clone $query)->select('count(item.id)')->getQuery()->getSingleScalarResult();

        // Get total pages
        $total_pages = \intval(\ceil($workshop_item_count / $limit));

        // Calculate offset
        $offset = $limit * ($page - 1);

        // Make sure offset (page) is valid
        if($page <= 0 || $offset > $workshop_item_count){
            $response = $response->withHeader('Location',
                '/workshop/browse?' . \http_build_query($url_params + ['page' => 1]),
            )->withStatus(302);
            return $response;
        }

        // Create pagination
        $pagination = [];
        if($total_pages <= 5){
            for($i = 1; $i <= $total_pages; $i++){
                $pagination[] = [
                    'label' => (string) $i,
                    'active' => $page === $i,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $i]),
                ];
            }
        } else {

            if($page >= 3){

                $pagination[] = [
                    'label' => '1',
                    'active' => $page === 1,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => 1]),
                ];

                if($page > 3){
                    $pagination[] = [
                        'label' => '...',
                        'active' => false,
                        'disabled' => true,
                        'url'    => null,
                    ];
                }
            }

            if($page !== 1){
                $pagination[] = [
                    'label' => (string) ($page - 1),
                    'active' => false,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page - 1]),
                ];
            }

            if($page !== $total_pages){
                $pagination[] = [
                    'label' => (string) $page,
                    'active' => true,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page]),
                ];
            }

            if($page < $total_pages - 1){
                $pagination[] = [
                    'label' => (string) ($page + 1),
                    'active' => false,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page + 1]),
                ];
            }

            if($page === 1){
                $pagination[] = [
                    'label' => (string) ($page + 2),
                    'active' => false,
                    'disabled' => false,
                    'url'    => '/workshop/browse?' . \http_build_query($url_params + ['page' => $page + 2]),
                ];
            }

            if($page < $total_pages - 2)
            {
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
        $result = $query->getQuery()->getResult();
        $workshop_items = new ArrayCollection($result);

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/browse.workshop.html.twig', [
                'workshop_items'                => $workshop_items,
                'categories'                    => WorkshopCategory::cases(),
                'categories_without_difficulty' => Config::get('app.workshop.item_categories_without_difficulty'),
                'tags'                          => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'builds'                        => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
                'pagination'                    => $pagination,
                'submitter'                     => $submitter,
                'original_author'               => $original_author,
            ])
        );

        return $response;
    }
}
