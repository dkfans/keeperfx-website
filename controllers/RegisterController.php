<?php

namespace App\Controller;

use App\Account;
use App\Entity\User;
use App\FlashMessage;
use Compwright\PhpSession\Session;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RegisterController {

    public function registerIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account
    ){
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        $response->getBody()->write(
            $twig->render('register.html.twig')
        );

        return $response;
    }

    public function register(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        FlashMessage $flash,
        EntityManager $em,
        Session $session
    ){
        // Only logged out users allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        // Get POST vars
        $post            = $request->getParsedBody();
        $username        = (string) ($post['username'] ?? null);
        $password        = (string) ($post['password'] ?? null);
        $repeat_password = (string) ($post['repeat_password'] ?? null);
        $email           = (string) ($post['email'] ?? null);

        $success = true;

        if(strlen($username) < 3 || strlen($username) > 32){
            $success = false;
            $flash->warning('Username has to be between 3');
        }

        // Make sure passwords match
        if($password !== $repeat_password){
            $success = false;
            $flash->warning('The given passwords did not match.');
        }

        // Check if username already exists
        $user = $em->getRepository(User::class)->findBy(['username' => $username]);
        if($user){
            $success = false;
            $flash->warning('Username already in use.');
        }

        // Check if email address already exists
        $user = $em->getRepository(User::class)->findBy(['email' => $email]);
        if($user){
            $success = false;
            $flash->warning('This email address is already in use.');
        }

        // If user registration is valid
        if($success){

            // Create new user
            $user = new User();
            $user->setUsername($username);
            $user->setPassword($password);
            $user->setEmail($email);
            $em->persist($user);
            $em->flush();

            $flash->success('Successfully registered. You are now logged in.');

            // Log in the user
            $session['uid'] = $user->getId();

            // Navigate to dashboard
            $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        $response->getBody()->write(
            $twig->render('register.html.twig', [
                'username'        => $username,
                'email'           => $email,
                'password'        => $password,
                'repeat_password' => $repeat_password,
            ])
        );

        return $response;
    }

}
