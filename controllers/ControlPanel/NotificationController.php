<?php

namespace App\Controller\ControlPanel;

use App\Entity\UserNotification;

use App\Account;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use App\Notifications\NotificationCenter;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;

class NotificationController {

    public function read(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        $id,
    ){
        if(!\is_numeric($id)){
            throw new HttpNotFoundException($request);
        }

        /** @var UserNotification $notification */
        $notification = $em->getRepository(UserNotification::class)->find($id);
        if(!$notification){
            throw new HttpNotFoundException($request);
        }

        if($account->getUser() !== $notification->getUser()){
            throw new HttpNotFoundException($request);
        }

        $notification->setRead(true);
        $em->flush();

        /** @var NotificationInterface $object */
        $object = $nc->createNotificationObject($notification);

        $nc->clearUserCache();

        $response = $response->withHeader('Location', $object->getUri())->withStatus(302);
        return $response;
    }

    public function listIndex(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        TwigEnvironment $twig,
        )
    {
        $response->getBody()->write(
            $twig->render('cp/notifications.cp.html.twig', [
                'notifications' => $nc->getAllNotifications()
            ])
        );

        return $response;
    }

}
