<?php

namespace App\Controller;

use App\Entity\User;

use App\Account;
use App\FlashMessage;
use Compwright\PhpSession\Session;
use Twig\Environment as TwigEnvironment;
use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmailVerificationController {

    public function verify(
        Request $request,
        Response $response,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Session $session,
        int $user_id,
        string $token,
    ){
        // Get user
        $user = $em->getRepository(User::class)->find($user_id);
        if(!$user){
            $flash->warning('Invalid email verification token.');
            $response->getBody()->write(
                $twig->render('email.verification.html.twig')
            );
            return $response;
        }

        // Make sure that this token does not belong to another user
        if($account->isLoggedIn() && $account->getUser() !== $user){
            $flash->warning('Invalid email verification token.');
            $response->getBody()->write(
                $twig->render('email.verification.html.twig')
            );
            return $response;
        }

        // Get verification
        $verification = $user->getEmailVerification();

        // Verify the token
        if($verification === null || $verification->getToken() !== $token){
            $flash->warning('Invalid email verification token.');
            $response->getBody()->write(
                $twig->render('email.verification.html.twig')
            );
            return $response;
        }

        // Update the DB
        $user->setEmailVerified(true);
        $em->remove($verification);
        $em->flush();

        // If we are not logged in yet we'll log in
        if($account->isLoggedIn() === false){
            $account->setCurrentLoggedInUser($user);
            $session['uid'] = $user->getId();
            $flash->success('Your email address has been verified! You are now logged in.');
        } else {
            $flash->success('Your email address has been verified!');
        }

        $response->getBody()->write(
            $twig->render('email.verification.html.twig')
        );

        return $response;
    }

}
