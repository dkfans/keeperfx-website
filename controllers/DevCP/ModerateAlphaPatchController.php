<?php

namespace App\Controller\DevCP;

use App\Entity\GithubAlphaBuild;

use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;

class ModerateAlphaPatchController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('devcp/alpha-patch.list.devcp.html.twig', [
                'alpha_builds'   => $em->getRepository(GithubAlphaBuild::class)->findBy([],['id' => 'DESC'])
            ])
        );

        return $response;
    }

    public function disable(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){

        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Check if Alpha Build exists
        $alpha_build = $em->getRepository(GithubAlphaBuild::class)->find($id);
        if(!$alpha_build){
            throw new HttpNotFoundException($request, "alpha build not found");
        }

        // Set unavailable
        $alpha_build->setIsAvailable(false);
        $em->flush();

        // Success
        $flash->success("Alpha Patch '{$alpha_build->getName()}' disabled");
        $response = $response->withHeader('Location', '/dev/alpha-patches/list')->withStatus(302);
        return $response;
    }

    public function enable(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){

        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Check if Alpha Build exists
        $alpha_build = $em->getRepository(GithubAlphaBuild::class)->find($id);
        if(!$alpha_build){
            throw new HttpNotFoundException($request, "alpha build not found");
        }

        // Set unavailable
        $alpha_build->setIsAvailable(true);
        $em->flush();

        // Success
        $flash->success("Alpha Patch '{$alpha_build->getName()}' enabled");
        $response = $response->withHeader('Location', '/dev/alpha-patches/list')->withStatus(302);
        return $response;
    }

    public function edit(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $id
    ){
        // Check if Alpha Build exists
        $alpha_build = $em->getRepository(GithubAlphaBuild::class)->find($id);
        if(!$alpha_build){
            throw new HttpNotFoundException($request, "alpha build not found");
        }

        $post = $request->getParsedBody();

        if(!isset($post['workflow_title']) || !is_string($post['workflow_title'])){
            throw new HttpBadRequestException($request);
        }

        $alpha_build->setWorkflowTitle($post['workflow_title']);
        $em->flush();

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(
            \json_encode(['success' => true])
        );

        return $response;
    }
}
