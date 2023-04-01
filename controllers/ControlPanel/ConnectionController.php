<?php

namespace App\Controller\ControlPanel;

use App\Account;
use App\Entity\UserOAuthToken;
use App\Enum\UserOAuthTokenType;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConnectionController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        Account $account,
    ){
        $connections = [
            'discord' => [
                'uid'          => null,
                'is_connected' => false,
            ],
            'twitch' => [
                'uid'          => null,
                'is_connected' => false,
            ],
        ];

        $discord_token = $em->getRepository(UserOAuthToken::class)->findOneBy([
            'user' => $account->getUser(),
            'type' => UserOAuthTokenType::Discord,
        ]);

        if($discord_token){
            $connections['discord'] = [
                'uid'          => $discord_token->getUid(),
                'is_connected' => true,
            ];
        }

        $twitch_token = $em->getRepository(UserOAuthToken::class)->findOneBy([
            'user' => $account->getUser(),
            'type' => UserOAuthTokenType::Twitch,
        ]);

        if($twitch_token){
            $connections['twitch'] = [
                'uid'          => $twitch_token->getUid(),
                'is_connected' => true,
            ];
        }

        $response->getBody()->write(
            $twig->render('cp/connections.cp.html.twig', [
                'connections' => $connections
            ])
        );

        return $response;
    }

}
