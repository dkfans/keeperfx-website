<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;

use App\Enum\WorkshopType;

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
        $q = $request->getQueryParams();

        $url_params = [];

        $criteria  = ['is_accepted' => true];
        $order_by  = null;
        $offset    = 0;
        $limit     = 40;
        $submitter = null;

        $page = (int)($q['page'] ?? 1);

        // Decide 'ORDER BY'
        switch(\strtolower((string)($q['order_by'] ?? ''))){
            case 'name':
                $order_by = ['name' => 'ASC'];
                $url_params['order_by'] = 'name';
                break;
            case 'most-downloaded':
                $order_by = ['download_count' => 'DESC'];
                $url_params['order_by'] = 'most-downloaded';
                break;
            case 'highest-rated':
                $order_by  = ['rating_score' => 'DESC'];
                $url_params['order_by'] = 'highest-rated';
                break;
            default:
            case 'latest':
                $order_by = ['created_timestamp' => 'DESC'];
                $url_params['order_by'] = 'latest';
                break;
        }

        // Create query for total workshop item count
        $query = $em->getRepository(WorkshopItem::class)->createQueryBuilder('a')
            ->where('a.is_accepted = 1');

        // Add user criteria
        if(isset($q['user']) && \is_string($q['user'])){
            $username = $q['user'];

            if($username === 'keeperfx-team'){

                $criteria['submitter'] = null;
                $query                 = $query->andWhere('a.submitter IS NULL');
                $submitter             = 'KeeperFX Team';
                $url_params['user']    = 'keeperfx-team';

            } else {

                $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
                if(!$user){
                    $flash->warning('User not found.');
                    $response->getBody()->write(
                        $twig->render('workshop/alert.workshop.html.twig', [
                            'types'          => WorkshopType::cases(),
                            'tags'           => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                            'builds'         => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
                        ])
                    );
                    return $response;
                }

                $criteria['submitter'] = $user;
                $query                 = $query->andWhere('a.submitter = ' . $user->getId());
                $submitter             = $user->getUsername();
                $url_params['user']    = $user->getUsername();
            }
        }

        // Get total workshop item count
        $workshop_item_count = $query->select('count(a.id)')->getQuery()->getSingleScalarResult();

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

        // Get workshop items
        $workshop_items = $em->getRepository(WorkshopItem::class)->findBy(
            $criteria,
            $order_by,
            $limit,
            $offset
        );

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/browse.workshop.html.twig', [
                'workshop_items'           => $workshop_items,
                'types'                    => WorkshopType::cases(),
                'types_without_difficulty' => Config::get('app.workshop.item_types_without_difficulty'),
                'tags'                     => $em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'builds'                   => $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
                'pagination'               => $pagination,
                'submitter'                => $submitter,
            ])
        );

        return $response;
    }
}
