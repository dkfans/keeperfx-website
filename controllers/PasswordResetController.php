<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPasswordReset;
use App\Entity\UserPasswordResetToken;

use App\Mailer;
use App\Account;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\StringHelper;

class PasswordResetController {

    public function passwordResetSendIndex(
        Request $request,
        Response $response,
        Account $account,
        TwigEnvironment $twig
    ){
        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            return $response;
        }

        $response->getBody()->write(
            $twig->render('password-reset/password-reset.send.html.twig')
        );

        return $response;
    }

    public function passwordResetSend(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        EntityManager $em,
        Account $account,
        Mailer $mailer,
    ){
        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            return $response;
        }

        // Wait a random amount to protect against timing attacks
        \usleep(\mt_rand(10,500));

        // Get data
        $post = $request->getParsedBody();
        if(!isset($post['identifier']) || !is_string($post['identifier'])){
            throw new \Exception("invalid or missing 'identifier'");
        }

        // Check for email address first
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $post['identifier']]);
        if(!$user){

            // Then check for username
            /** @var User $user */
            $user = $em->getRepository(User::class)->findOneBy(['username' => $post['identifier']]);
        }

        // If we need to send an email
        if($user){

            // Some accounts do not have email addresses
            if($user->getEmail() !== null){

                // Wait a random amount to protect against timing attacks
                \usleep(\mt_rand(10,100));

                // Create Reset TOKEN and add to DB
                $token = StringHelper::generate(32);
                $reset_token = new UserPasswordResetToken();
                $reset_token->setUser($user);
                $reset_token->setToken($token);
                $em->persist($reset_token);
                $em->flush();

                // Create URL
                $reset_url = $_ENV['APP_ROOT_URL'] . '/password-reset/' . $token;

                // Add mail to queue
                $mailer->createMailInQueue($user->getEmail(), "Password Reset",
                    "You can reset your KeeperFX password by visiting the following link: \n{$reset_url}\n\nUsername:{$user->getUsername()}"
                );

            }

        } else {

            // Wait a random amount to protect against timing attacks
            \usleep(\mt_rand(50,200));
        }

        // Show the message that an email might have been sent.
        // We explicitly don't tell the user whether it's been sent or not for privacy reasons.
        // This way we can't get scraped for email addresses registered on this website.
        $flash->info('If this username or email address exists in our database, we will soon send an email with instructions on how to reset the password.');
        $response->getBody()->write(
            $twig->render('password-reset/password-reset.alert.html.twig')
        );
        return $response;
    }

    public function passwordResetIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Account $account,
        $token
    ){
        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            return $response;
        }

        // Get reset token
        $reset_token = $em->getRepository(UserPasswordResetToken::class)->findOneBy(['token' => $token]);
        if(!$reset_token){
            $flash->error('Invalid password reset token.');
            $response->getBody()->write(
                $twig->render('password-reset/password-reset.alert.html.twig')
            );
            return $response;
        }

        $response->getBody()->write(
            $twig->render('password-reset/password-reset.html.twig')
        );

        return $response;
    }

    public function passwordReset(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Session $session,
        Account $account,
        $token
    ){
        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            return $response;
        }

        // Get reset token
        $reset_token = $em->getRepository(UserPasswordResetToken::class)->findOneBy(['token' => $token]);
        if(!$reset_token){
            $flash->error('Invalid password reset token.');
            $response->getBody()->write(
                $twig->render('password-reset/password-reset.alert.html.twig')
            );
            return $response;
        }

        // Get POST data
        $post            = $request->getParsedBody();
        $password        = (string) $post['password'] ?? '';
        $repeat_password = (string) $post['repeat_password'] ?? '';

        $success = true;

        // Make sure a password is given
        if(empty($password)){
            $success = false;
            $flash->warning('You must enter a password.');
        } else {

            // Make sure passwords match
            if($password !== $repeat_password){
                $success = false;
                $flash->warning('The given passwords did not match.');
            }
        }

        // If failed we show a notice
        if(!$success){
            $response->getBody()->write(
                $twig->render('password-reset/password-reset.html.twig')
            );
            return $response;
        }

        // Get the user
        $user = $reset_token->getUser();
        if(!$user){
            throw new \Exception('somehow we failed to get the user');
        }

        // Update the password and remove the password reset token
        $user->setPassword($password);
        $em->remove($reset_token);
        $em->flush();

        // Log the user in
        $account->setCurrentLoggedInUser($user);

        // Redirect to home page
        $flash->success('You have successfully reset your password!');
        $response = $response->withHeader('Location', '/')->withStatus(302);
        return $response;
    }
}
