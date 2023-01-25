<?php

namespace App\Controller;

use App\Account;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Slim\Psr7\UploadedFile;

use App\Enum\WorkshopType;

use App\Entity\GithubRelease;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopTag;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

class WorkshopController {

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getWorkshopOptions(): array
    {
        $tags   = $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']);
        $builds = $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        return [
            'types'  => WorkshopType::cases(),
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
        Account $account,
        EntityManager $em
    ){

        $success = true;

        $uploaded_files        = $request->getUploadedFiles();
        $post                  = $request->getParsedBody();

        $name                  = (string) ($post['name'] ?? null);
        $description           = (string) ($post['description'] ?? null);
        $install_instructions  = (string) ($post['install_instructions'] ?? null);

        // Check if name is valid
        if(!$name){
            $success = false;
            $flash->warning('Please enter a name for this workshop item');
        }

        // Check if type is valid
        $type = WorkshopType::tryFrom((int) ($post['type'] ?? null));
        if($type === null){
            $flash->warning('Invalid workshop type');
            $success = false;
        }

        // Check if a file was uploaded
        if(empty($uploaded_files['file']) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('You did not submit a file');
            $success = false;
        }

        // Check valid screenshot files
        if(!empty($uploaded_files['screenshots'])){
            /** @var UploadedFile $screenshot_file */
            foreach($uploaded_files['screenshots'] as $screenshot_file){

                // NO screenshots were added
                if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $filename = $screenshot_file->getClientFilename();
                $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
                if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])){
                    $success = false;
                    $flash->warning('One or more screenshots are invalid. Allowed file types: jpg, jpeg, png, gif');
                }
            }
        }

        // Return the page if submission is invalid
        if(!$success){
            // TODO: remove post vars (request twig extension)
            $response->getBody()->write(
                $twig->render('workshop/submit.workshop.html.twig', $this->getWorkshopOptions() + [
                    'name'                 => $name,
                    'description'          => $description,
                    'install_instructions' => $install_instructions,
                ])
            );
            return $response;
        }

        // Create the item in DB
        $workshop_item = new WorkshopItem();
        $workshop_item->setName($name);
        $workshop_item->setAuthor($account->getUser());
        $workshop_item->setType($type);
        $workshop_item->setFilename($uploaded_files['file']->getClientFilename());

        if(!empty($description)){
            $workshop_item->setDescription($description);
        }

        if(!empty($install_instructions)){
            $workshop_item->setInstallInstructions($install_instructions);
        }

        // Set optional minimum game build
        if(isset($post['min_game_build']) && !empty($post['min_game_build'])){
            $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
            if($min_game_build){
                $workshop_item->setMinGameBuild($min_game_build);
            }
        }

        $em->persist($workshop_item);
        $em->flush();

        // Create directories for files
        $workshop_item_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_screenshots_dir = $workshop_item_dir . '/screenshots';
        if(!DirectoryHelper::create($workshop_item_dir)){
            throw new \Exception('Failed to create workshop item storage dir');
        }
        if(!DirectoryHelper::create($workshop_item_screenshots_dir)){
            throw new \Exception('Failed to create workshop item screenshots dir');
        }

        // Store the uploaded file
        // TODO: allow specific files only (archives .7z, .zip, .rar, etc)
        /** @var UploadedFile $file */
        $file = $uploaded_files['file'];
        $filename = $file->getClientFilename();
        $file->moveTo($workshop_item_dir . '/' . $filename);

        // Store any uploaded screenshots
        foreach($uploaded_files['screenshots'] as $screenshot_file){
            // NO screenshots were added
            if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $path = $workshop_item_screenshots_dir . '/' . $screenshot_file->getFilename();
            if(!$screenshot_file->moveTo($path)){
                throw new \Exception('Failed to move workshop item screenshot');
            }
        }

        $response->getBody()->write(
            $twig->render('workshop/submitted.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

}
