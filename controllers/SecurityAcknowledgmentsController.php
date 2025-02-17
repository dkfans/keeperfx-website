<?php

namespace App\Controller;

use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;

class SecurityIssuesController {

    public function acknowledgments(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        // $acknowledgments = [
        //     'Yani' => [
        //         '2121-05-12: Super hack fix',
        //         '2121-05-12: Super hack fix',
        //         '2121-05-12: Super hack fix',
        //     ],
        // ];

        $acknowledgments = [];

        $response->getBody()->write(
            $twig->render('security-acknowledgments.html.twig', ['acknowledgments' => $acknowledgments])
        );

        return $response;
    }

    public function securityTxt(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $file = \APP_ROOT . '/security.txt';

        if(!\file_exists($file)){
            throw new HttpNotFoundException($request);
        }

        $response->getBody()->write(
            \file_get_contents($file)
        );

        return $response->withHeader('Content-Type', 'text/plain');
    }
}
