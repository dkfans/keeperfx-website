<?php

namespace App\Controller\DevCP;

use App\Entity\GithubPrototype;

use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class ModeratePrototypeController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $prototypes = [];
        $prototype_entities = $em->getRepository(GithubPrototype::class)->findBy([],['timestamp' => 'DESC']);

        /** @var GithubPrototype $prototype */
        foreach($prototype_entities as $prototype)
        {
            $title = $prototype->getName();

            $regex = '/keeperfx\-([0-9\_]+)\_Prototype\_([0-9a-f]+)\-patch/';
            if(\preg_match($regex, $title, $matches))
            {
                $title = \str_replace('_', '.', $matches[1]);
                $title .= '-' . $matches[2];
            }

            $prototypes[] = [
                'id'              => $prototype->getId(),
                'filename'        => $prototype->getFilename(),
                'name'            => $prototype->getName(),
                'title'           => $title,
                'artifact_id'     => $prototype->getArtifactId(),
                'workflow_title'  => $prototype->getWorkflowTitle(),
                'workflow_run_id' => $prototype->getWorkflowRunId(),
                'timestamp'       => $prototype->getTimestamp(),
                'size_in_bytes'   => $prototype->getSizeInBytes(),
            ];
        }


        $response->getBody()->write(
            $twig->render('devcp/prototype.list.devcp.html.twig', [
                'prototypes' => $prototypes
            ])
        );

        return $response;
    }
}
