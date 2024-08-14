<?php

namespace App\Controller\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\WorkshopItem;

use App\Account;
use App\FlashMessage;

use URLify;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopMyItemsController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Account $account,
    ){
        // Get users workshop items
        $items = $em->getRepository(WorkshopItem::class)->findBy(['submitter' => $account->getUser()]);

        // Return
        $response->getBody()->write(
            $twig->render('workshop/my-items.html.twig', [
                'workshop_items' => $items,
            ])
        );

        return $response;
    }

}
