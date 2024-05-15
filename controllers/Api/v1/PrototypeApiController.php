<?php

namespace App\Controller\Api\v1;

// use App\Entity\NewsArticle;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;
use App\Entity\GithubRelease;
use App\Entity\GithubAlphaBuild;
use App\Entity\GithubPrototype;

class PrototypeApiController {

    public function getByRunId(
        Request $request,
        Response $response,
        EntityManager $em,
        int $id,
    ){

        $prototype = $em->getRepository(GithubPrototype::class)->findOneBy(['workflow_run_id' => $id]);

        // If prototype is not found
        if($prototype === null){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error' => 'PROTOTYPE_NOT_FOUND'
                ])
            );
            $response = $response->withHeader('Content-Type', 'application/json');
            return $response;
        }

        // Respond with data
        $response->getBody()->write(
            \json_encode([
                'success' => true,
                'prototype' => [
                    'id'              => $prototype->getId(),
                    'artifact_id'     => $prototype->getArtifactId(),
                    'name'            => $prototype->getName(),
                    'workflow_title'  => $prototype->getWorkflowTitle(),
                    'workflow_run_id' => $prototype->getWorkflowRunId(),
                    'filename'        => $prototype->getFilename(),
                    'timestamp'       => $prototype->getTimestamp(),
                    'size_in_bytes'   => $prototype->getSizeInBytes(),
                    'is_available'    => $prototype->getIsAvailable(),
                ],
            ])
        );
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;

    }

}
