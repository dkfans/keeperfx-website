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
            $response = $response->withHeader('Location', '/')->withStatus(302);
            // $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        $params = $request->getQueryParams();

        if(isset($params['msg'])){
            switch((string)$params['msg']){
                case 'workshop-rate':
                    $flash->info('You need to be logged in to rate workshop items.');
                    break;
            }
        }

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
        $username = (string) ($post['username'] ?? '');
        $password = (string) ($post['password'] ?? '');
        $redirect = (string) ($post['redirect'] ?? '');

        if($username && $password){

            $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if($user){

                if(\password_verify($password, $user->getPassword())){

                    // Log user in (session)
                    $session['uid'] = $user->getId();

                    // Determine redirect location
                    $redirect_location = '/';
                    // $redirect_location = '/dashboard';
                    if($redirect && \preg_match('/^\/[a-zA-Z]/', $redirect)){
                        $redirect_location = $redirect;
                    }

                    // Show flash message
                    $flash->success('You have successfully logged in!');

                    // Redirect
                    $response = $response->withHeader('Location', $redirect_location)->withStatus(302);
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
