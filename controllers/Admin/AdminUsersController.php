<?php

namespace App\Controller\Admin;

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
            $twig->render('control-panel/admin/users/users.admin.cp.html.twig', [
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
            $twig->render('control-panel/admin/users/users.add.admin.cp.html.twig')
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

        // Get post variables
        $post     = $request->getParsedBody();
        $username = (string) ($post['username'] ?? null);
        $password = (string) ($post['password'] ?? null);
        $role     = (int) ($post['role'] ?? 1);

        // Check username and password given
        if(!$username || !$password){
            $flash->warning('You need to fill in the username and password');
            $response = $response->withHeader('Location', '/admin/user/add')->withStatus(302);
            return $response;
        }

        // Check valid role
        $role = UserRole::tryFrom($role);
        if($role === null){
            return $response;
        }

        // Username check
        $exists = $em->getRepository(User::class)->findBy(['username' => $username]);
        if($exists){
            $flash->warning('A user with this username already exists');
            $response = $response->withHeader('Location', '/admin/user/add')->withStatus(302);
            return $response;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
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
            $twig->render('control-panel/admin/users/users.edit.admin.cp.html.twig', [
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
        $role     = (int) ($post['role'] ?? 1);

        // Check username and password given
        if(!$username){
            $flash->warning('You need to fill in the username');
            $response = $response->withHeader('Location', '/admin/user/' . $id)->withStatus(302);
            return $response;
        }

        // Check valid role
        $role = UserRole::tryFrom($role);
        if($role === null){
            return $response;
        }

        $user->setUsername($username);
        $user->setRole($role);

        if($password){
            $user->setPassword($password);
        }

        $em->flush();

        $flash->success('User successfully edited!');
        $response = $response->withHeader('Location', '/admin/user/' . $id)->withStatus(302);
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
