<?php

namespace App\Controller\Api\v1;

use App\CDN;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CdnApiController
{
    public function listEndpoints(
        Request $request,
        Response $response,
        CDN $cdn,
    ) {
        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(
            \json_encode($cdn->getAll())
        );

        // Return output
        return $response;
    }
}
