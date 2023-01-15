<?php

namespace App\Controller;

use Compwright\PhpSession\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Csrf\Guard;

class AccountController {

    public function logout(
        Request $request,
        Response $response,
        Guard $csrf_guard,
        Session $session,
        $token_name,
        $token_value,
    ){
        // Check for valid logout request
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if($valid){
            $session['uid'] = null;
        }

        $response = $response->withHeader('Location', '/')->withStatus(302);
        return $response;
    }

}
