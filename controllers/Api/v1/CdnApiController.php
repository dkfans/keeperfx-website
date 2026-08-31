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
            \json_encode([
                'success'            => true,
                'endpoints'          => $cdn->getAll(),
                'suggested_endpoint' => $cdn->getCurrentId(),   // This will pass trough the middleware which will check the country
            ])
        );

        // Return output
        return $response;
    }
}
