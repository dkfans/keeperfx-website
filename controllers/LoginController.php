<?php

namespace App\Controller;

use App\Entity\User;

use App\Account;
use App\Entity\UserCookieToken;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;
use Twig\Environment as TwigEnvironment;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\FigResponseCookies;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Xenokore\Utility\Helper\StringHelper;

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

                    // Handle 'Remember me'
                    if(isset($post['remember_me'])){

                        // Find unused cookie token
                        $cookie_token = null;
                        while($cookie_token === null){
                            $cookie_token_new = StringHelper::generate(64);
                            $existing_token = $em->getRepository(UserCookieToken::class)->findOneBy(['token' => $cookie_token_new]);
                            if($existing_token === null){
                                $cookie_token = $cookie_token_new;
                            }
                        }

                        // Create cookie token in DB
                        $token = new UserCookieToken();
                        $token->setUser($user);
                        $token->setToken($cookie_token);
                        $em->persist($token);
                        $em->flush();

                        // Add cookie to response
                        $max_age      = (int) ($_ENV['APP_REMEMBER_ME_TIME'] ?? 31560000);
                        $expires      = \gmdate('D, d M Y H:i:s T', time() + $max_age);
                        $response = FigResponseCookies::set($response,
                            SetCookie::create('user_cookie_token', $cookie_token)
                                ->withDomain($_ENV['APP_COOKIE_DOMAIN'] ?? $_ENV['APP_ROOT_URL'] ?? null)
                                ->withPath($_ENV['APP_COOKIE_PATH'] ?? "/")
                                ->withExpires($expires)
                                ->withMaxAge($max_age)
                                ->withSecure((bool)$_ENV['APP_COOKIE_TLS_ONLY'])
                                ->withHttpOnly((bool)$_ENV['APP_COOKIE_HTTP_ONLY'])
                                ->withSameSite(SameSite::fromString($_ENV['APP_COOKIE_SAMESITE'])
                            )
                        );
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
