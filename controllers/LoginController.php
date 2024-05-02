<?php

namespace App\Controller;

use App\Entity\User;

use App\Account;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;
use Twig\Environment as TwigEnvironment;
use Dflydev\FigCookies\FigResponseCookies;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController {

    public function loginIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        FlashMessage $flash
    ){

        // Only logged-out guests allowed
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
        Account $account,
        FlashMessage $flash
    ){

        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            // $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        $post = $request->getParsedBody();
        $username = (string) ($post['username'] ?? '');
        $password = (string) ($post['password'] ?? '');
        $redirect = (string) ($post['redirect'] ?? '');

        if($username && $password){

            $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if($user){

                if(\password_verify($password, $user->getPassword())){

                    // Log user in
                    $account->setCurrentLoggedInUser($user);

                    // Log IP
                    $ip = $request->getAttribute('ip_address');
                    if($ip !== null){
                        $account->logIp($ip);
                    }

                    // Handle 'Remember me'
                    if(isset($post['remember_me'])){

                        // Add cookie to response
                        $response = FigResponseCookies::set($response, $account->createRememberMeSetCookie());
                    }

                    // Determine redirect location
                    $redirect_location = '/';
                    if($redirect && \preg_match('/^\/[a-zA-Z0-9\-\/\.]/', $redirect)){
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
