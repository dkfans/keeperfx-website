<?php

namespace App\Controller\Api\v1;

use App\Twig\Extension\MoonPhaseExtension;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MoonPhaseApiController {

    public function outputInfo(
        Request $request,
        Response $response,
    ){
        // Get moon phase data by using the Twig extension
        $extension = new MoonPhaseExtension();
        $data = $extension->getGlobals()['moon_phase'];

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(
            \json_encode($data)
        );

        // Return output
        return $response;
    }

}
