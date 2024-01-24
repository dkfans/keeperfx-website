<?php

namespace App\Controller\DevCP;

use App\Entity\GithubPrototype;

use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class ModeratePrototypeController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('devcp/prototype.list.devcp.html.twig', [
                'prototypes'   => $em->getRepository(GithubPrototype::class)->findBy([],['timestamp' => 'DESC'])
            ])
        );

        return $response;
    }
}
