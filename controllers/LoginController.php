<?php

namespace App\Controller;

use App\Account;
use App\FlashMessage;
use Compwright\PhpSession\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;
use Doctrine\ORM\EntityManager;
use App\Entity\User;

class LoginController {

    public function loginIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        FlashMessage $flash
    ){

        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        // TODO: make these look better:
        // $flash->info('Something is happening. Testing?.');
        // $flash->success('Something is happening. Testing?.');
        // $flash->warning('Something is happening. Testing?.');
        // $flash->error('Something is happening. Testing?.');

        $response->getBody()->write(
            $twig->render('login.html.twig')
        );

        return $response;
    }


    public function login(
        Request $request,
        Response $response,
        EntityManager $em,
        TwigEnvironment $twig,
        Session $session,
        FlashMessage $flash
    ){

        $post = $request->getParsedBody();
        $username = (string) ($post['username'] ?? null);
        $password = (string) ($post['password'] ?? null);

        if($username && $password){

            $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if($user){

                if(\password_verify($password, $user->getPassword())){

                    $session['uid'] = $user->getId();

                    $flash->success('Successfuly logged in!');

                    $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
                    return $response;

                }
            }
        }

        $flash->error('Invalid login credentials.');

        $response->getBody()->write(
            $twig->render('login.html.twig')
        );

        return $response;
    }
}
