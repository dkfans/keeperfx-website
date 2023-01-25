<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\WorkshopTag;
use App\Entity\WorkshopType;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopController {

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getWorkshopOptions(): array
    {
        $types  = $this->em->getRepository(WorkshopType::class)->findBy([], ['name' => 'ASC']);
        $tags   = $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']);
        $builds = $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        return [
            'types'  => $types,
            'tags'   => $tags,
            'builds' => $builds,
        ];
    }

    public function workshopIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('workshop/workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function submitIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('workshop/submit.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function submit(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        EntityManager $em
    ){

        $success = true;

        $uploaded_files        = $request->getUploadedFiles();
        $post                  = $request->getParsedBody();

        $name                  = (string) ($post['name'] ?? null);
        $type                  = (int) ($post['type'] ?? null);
        $description           = (string) ($post['description'] ?? null);
        $install_instructions  = (string) ($post['install_instructions'] ?? null);
        $min_game_build = null;

        // Check if name is valid
        if(!$name){
            $success = false;
            $flash->warning('Please enter a name for this workshop item');
        }

        // Check if type is valid
        $ws_type = $em->getRepository(WorkshopType::class)->find($type);
        if(!$ws_type){
            $flash->warning('Invalid workshop type');
            $success = false;
        }

        if(empty($uploaded_files['file'])){
            $flash->warning('You did not submit a file');
            $success = false;
        }

        if(!empty($uploaded_files['screenshots'])){

        }

        if(isset($post['min_game_build']) && !empty($post['min_game_build'])){
            $min_game_build = (int) $post['min_game_build'];
        }

        $response->getBody()->write(
            $twig->render('workshop/submit.workshop.html.twig', $this->getWorkshopOptions() + [
                'name'                 => $name,
                'description'          => $description,
                'install_instructions' => $install_instructions,
            ])
        );

        return $response;
    }

}
