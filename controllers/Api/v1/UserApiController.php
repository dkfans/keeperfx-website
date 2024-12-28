<?php

namespace App\Controller\Api\v1;

use App\Enum\OAuthProviderType;

use App\Entity\User;
use App\Entity\UserOAuthToken;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserApiController {

    public function search(
        Request $request,
        Response $response,
        EntityManager $em,
    ){
        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        // Get queries
        $q = $request->getQueryParams();

        // Check for a Discord ID
        if(!empty($q['discord_id']) && \is_numeric($q['discord_id'])){

            $token = $em->getRepository(UserOAuthToken::class)->findOneBy([
                'provider_type' => OAuthProviderType::Discord->value,
                'uid'           => $q['discord_id'],
            ]);

            if($token){

                /** @var User $user */
                $user = $token->getUser();

                $response->getBody()->write(
                    \json_encode([
                        'success' => true,
                        'user' => [
                            'username'                => $user->getUsername(),
                            'avatar'                  => $user->getAvatar(),
                            'bio'                     => $user->getBio()?->getBio(),
                            'item_count'              => $user->getWorkshopItems()->count(),
                            'rating_count'            => $user->getWorkshopRatings()->count(),
                            'difficulty_rating_count' => $user->getWorkshopDifficultyRatings()->count(),
                            'country'                 => $user->getCountry(),
                        ],
                    ])
                );

                return $response;

            } else {

                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error'   => 'DISCORD_UID_NOT_FOUND',
                    ])
                );

                return $response;
            }
        }

        // Return output
        $response->getBody()->write(
            \json_encode([
                'success' => false,
                'error'   => 'NO_SEARCH_PARAM',
            ])
        );

        return $response;
    }

}
