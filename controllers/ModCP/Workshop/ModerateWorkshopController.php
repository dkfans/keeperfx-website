<?php

namespace App\Controller\ModCP\Workshop;

use App\Enum\WorkshopCategory;

use App\Entity\User;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use App\Account;
use Slim\Csrf\Guard;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;
use App\Workshop\Exception\WorkshopException;
use App\Entity\WorkshopImage;
use App\UploadSizeHelper;

class ModerateWorkshopController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('modcp/workshop/workshop.modcp.html.twig', [
                'workshop_items'   => $em->getRepository(WorkshopItem::class)->findBy(['is_published' => true], ['id' => 'DESC']),
                'open_submissions' => $em->getRepository(WorkshopItem::class)->findBy(['is_published' => false], ['id' => 'DESC']),
            ])
        );

        return $response;
    }
}
