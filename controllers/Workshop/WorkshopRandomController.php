<?php

namespace App\Controller\Workshop;

use App\Entity\WorkshopItem;
use App\Enum\WorkshopCategory;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;

class WorkshopRandomController
{
    public function navRandomItem(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        string $item_category,
    ) {
        $category = match ($item_category) {
            default    => WorkshopCategory::Map,
            'map'      => WorkshopCategory::Map,
            'campaign' => WorkshopCategory::Campaign,
        };

        $count = $em->getRepository(WorkshopItem::class)
            ->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.category = :category')
            ->andWhere('w.is_published = :is_published')
            ->andWhere('w.is_last_file_broken = :is_last_file_broken')
            ->setParameter('category', $category->value)
            ->setParameter('is_published', true)
            ->setParameter('is_last_file_broken', false)
            ->getQuery()
            ->getSingleScalarResult();

        if ($count === 0) {
            $flash->warning('Random workshop item not found.');
            $response->getBody()->write($twig->render('workshop/alert.workshop.html.twig'));

            return $response;
        }

        $offset = \random_int(0, $count - 1);

        $item = $em->getRepository(WorkshopItem::class)
            ->createQueryBuilder('w')
            ->where('w.category = :category')
            ->andWhere('w.is_published = :is_published')
            ->andWhere('w.is_last_file_broken = :is_last_file_broken')
            ->setParameter('category', $category->value)
            ->setParameter('is_published', true)
            ->setParameter('is_last_file_broken', false)
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $response = $response->withHeader(
            'Location',
            '/workshop/item/' . $item->getId() . '/' . \URLify::slug($item->getName()) . '#nav-top'
        )->withStatus(302);

        return $response;
    }
}
