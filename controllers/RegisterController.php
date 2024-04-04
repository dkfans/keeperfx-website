<?php

namespace App\Controller;

use App\Enum\UserRole;

use App\Entity\User;

use App\Account;
use App\FlashMessage;
use App\Config\Config;

use App\Notifications\NotificationCenter;
use App\Notifications\Notification\NewUserNotification;

use Fgribreau\MailChecker;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;
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
        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            // $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        // Render view
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
        Session $session,
        NotificationCenter $nc,
    ){
        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            // $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        // Get POST vars
        $post            = $request->getParsedBody();
        $username        = (string) $post['username'] ?? '';
        $password        = (string) $post['password'] ?? '';
        $repeat_password = (string) $post['repeat_password'] ?? '';
        $email           = (string) $post['email'] ?? '';

        $success = true;

        // Validate username length
        if(\strlen($username) < 2 || \strlen($username) > 32){
            $success = false;
            $flash->warning('Username has must be at least 2 characters long and can not exceed 32 characters.');
        } else {

            // Validate username charset
            if(!\preg_match('/^[a-zA-Z0-9]+[a-zA-Z0-9\.\_\-]+$/', $username)){
                $success = false;
                $flash->warning(
                    'Username can only contain the following characters: <strong>a-z A-Z 0-9 _ . -</strong>' .
                    '<br />It also must start with a letter or number.'
                );
            }

            // Check if username already exists
            $user = $em->getRepository(User::class)->findBy(['username' => $username]);
            if($user){
                $success = false;
                $flash->warning('Username already in use.');
            }

        }

        // Check if username contains disallowed words
        foreach(Config::get('app.disallowed_username_words') as $word){
            if(strpos($username, $word) !== false){
                $success = false;
                $flash->warning("Username contains a disallowed word: {$word}");
                break;
            }
        }

        // Check if user wants to add an email address
        if(!empty($email)){

            // Validate email address
            if(!filter_var($email, \FILTER_VALIDATE_EMAIL)){
                $success = false;
                $flash->warning('Invalid email address.');
            }

            // Make sure this is not a throwaway email address
            if(!MailChecker::isValid($email)){
                $success = false;
                $flash->warning('Invalid email address.');
            }

            // Check if email address already exists
            $user_with_email = $em->getRepository(User::class)->findBy(['email' => $email]);
            if($user_with_email){
                $success = false;
                $flash->warning('This email address is already in use.');
            }

        } else {

            // Make sure email is set to NULL if no email is given
            $email = null;
        }

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

        // Make sure ToS and privacy policy have been read and accepted
        if(!isset($post['accepted_tos_and_privacy_policy'])){
            $success = false;
            $flash->warning('You did not accept the Terms of Service and Privacy Policy.');
        }

        // Given details must be valid before creating a user
        if(!$success){

            // Render register page
            $response->getBody()->write(
                $twig->render('register.html.twig')
            );

            return $response;
        }

        // Create new user
        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);
        $em->persist($user);
        $em->flush();

        // Immediately log in the user
        $session['uid'] = $user->getId();

        // Notify the admins
        $nc->sendNotificationToAllWithRole(UserRole::Admin, NewUserNotification::class, ['id' => $user->getId(), 'username' => $username]);

        // Navigate to account page
        $flash->success('Successfully registered. You are now logged in.');
        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }
}
