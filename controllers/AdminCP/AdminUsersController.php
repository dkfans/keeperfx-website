<?php

namespace App\Controller\AdminCP;

use App\Account;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;
use Slim\Csrf\Guard;
use App\Entity\User;
use App\Enum\UserRole;

class AdminUsersController {

    public function usersIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $users = $em->getRepository(User::class)->findAll();

        $response->getBody()->write(
            $twig->render('admincp/users/users.admincp.html.twig', [
                'users' => $users
            ])
        );

        return $response;
    }

    public function userAddIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('admincp/users/users.add.admincp.html.twig')
        );

        return $response;
    }

    public function userAdd(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash
    ){
        $success = true;

        $post     = $request->getParsedBody();
        $username = (string) ($post['username'] ?? '');
        $password = (string) ($post['password'] ?? '');

        $email = null;

        // Check username and password given
        if(!$username || !$password){
            $flash->warning('You need to fill in a username and password.');
            $success = false;
        } else {

            // Username check
            $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);
            if($user){
                $flash->warning('A user with this username already exists');
                $success = false;
            }

        }

        // Handle email address
        if(isset($post['email']) && is_string($post['email']) && \strlen($post['email']) > 0){

            // Check valid email address
            if(\filter_var($post['email'], \FILTER_VALIDATE_EMAIL) === false){
                $flash->error('Invalid email address.');
                $success = false;
            } else {

                // Check if email address already exists
                $user_with_email = $em->getRepository(User::class)->findOneBy(['email' => $post['email']]);
                if($user_with_email){
                    $flash->warning('A user with this email address already exists');
                    $success = false;
                } else {
                    $email = $post['email'];
                }
            }
        }

        // Check valid role
        $role = UserRole::tryFrom((int) ($post['role'] ?? UserRole::User));
        if($role === null){
            $flash->error('Invalid user role.');
            $success = false;
        }

        // Return errors if one or more checks did not pass
        if(!$success){
            $response->getBody()->write(
                $twig->render('admincp/users/users.add.admincp.html.twig')
            );
            return $response;
        }

        // Create user
        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);
        $user->setRole($role);
        $em->persist($user);
        $em->flush();

        $flash->success('User added!');

        $response = $response->withHeader('Location', '/admin/user/list')->withStatus(302);
        return $response;
    }

    public function userEditIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){

        $user = $em->getRepository(User::class)->find($id);

        if(!$user){
            $flash->warning('User not found.');
            $response = $response->withHeader('Location', '/admin/user/list')->withStatus(302);
            return $response;
        }

        $response->getBody()->write(
            $twig->render('admincp/users/users.edit.admincp.html.twig', [
                'user' => $user,
            ])
        );

        return $response;
    }

    public function userEdit(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){
        $success = true;

        $email = null;

        // Get user
        $user = $em->getRepository(User::class)->find($id);
        if(!$user){
            $flash->warning('User not found.');
            $response = $response->withHeader('Location', '/admin/user/list')->withStatus(302);
            return $response;
        }

        // Get post variables
        $post     = $request->getParsedBody();
        $username = (string) ($post['username'] ?? null);
        $password = (string) ($post['password'] ?? null);

        // Check if username is set
        if(!$username){
            $flash->warning('You need to fill in the username');
            $success = false;
        } else {

            // Handle updated username
            if($username !== $user->getUsername()){

                // Username check
                $user_with_username = $em->getRepository(User::class)->findOneBy(['username' => $username]);
                if($user_with_username){
                    $flash->warning("A user with the username \"{$username}\" already exists");
                    $success = false;
                }
            }
        }

        // Handle email address
        if(isset($post['email']) && is_string($post['email']) && $post['email'] !== ''){
            $email = $post['email'];

            // Check valid email address
            if(\filter_var($email, \FILTER_VALIDATE_EMAIL) === false){
                $flash->error('Invalid email address.');
                $success = false;
            } else {

                // Handle updated email address
                if($email !== $user->getEmail()){

                    // Check if email address already exists
                    $user_with_email = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                    if($user_with_email){
                        $flash->warning("A user with the email address \"{$email}\" already exists");
                        $success = false;
                    }
                }
            }
        }

        // Check valid role
        $role = UserRole::tryFrom((int) ($post['role'] ?? 1));
        if($role === null){
            $flash->error('Invalid user role.');
            $success = false;
        }

        // Output errors if not successful
        if(!$success){
            $response->getBody()->write(
                $twig->render('admincp/users/users.edit.admincp.html.twig', ['user' => $user])
            );
            return $response;
        }

        // Update user
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRole($role);

        // Update password if changed
        if($password){
            $user->setPassword($password);
        }

        $em->flush();

        $flash->success('User successfully updated!');

        // Return view
        $response->getBody()->write(
            $twig->render('admincp/users/users.edit.admincp.html.twig', ['user' => $user])
        );
        return $response;
    }

    public function userDelete(
        Request $request,
        Response $response,
        EntityManager $em,
        FlashMessage $flash,
        Guard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if($valid){

            $user = $em->getRepository(User::class)->find($id);
            if($user){

                $em->remove($user);
                $em->flush();
                $flash->success('User successfully removed!');
            }
        }

        $response = $response->withHeader('Location', '/admin/user/list')->withStatus(302);
        return $response;
    }

}
