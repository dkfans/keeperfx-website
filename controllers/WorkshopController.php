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
use App\Enum\UserRole;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Xenokore\Utility\Helper\DirectoryHelper;

class WorkshopController {

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getWorkshopOptions(): array
    {
        return [
            'types'  => WorkshopType::cases(),
            'tags'   => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
            'builds' => $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
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
        $workshop_item->setSubmitter($account->getUser());
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

        $path = $workshop_item_dir . '/' . $filename;
        $file->moveTo($path);
        if(!\file_exists($path)){
            throw new \Exception('Failed to move workshop item file');
        }

        // Store any uploaded screenshots
        foreach($uploaded_files['screenshots'] as $screenshot_file){
            // NO screenshots were added
            if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $screenshot_filename = \preg_replace('/[^a-zA-Z0-9_-\.]+/', '-', $screenshot_file->getClientFilename());
            $path = $workshop_item_screenshots_dir . '/' . $screenshot_filename;

            $screenshot_file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item screenshot');
            }
        }

        $flash->success(
            'Your workshop item has been submitted and will be reviewed by the KeeperFX team.' .
            'After it has been accepted it will be added to the workshop for others to download.'
        );

        $response->getBody()->write(
            $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function download(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        $id,
        $filename
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Check if workshop item has been accepted
        // Admins can always download workshop items
        if(
            $workshop_item->getIsAccepted() !== true
            && $account->getUser()->getRole()->value < UserRole::Admin->value
        ){
            $flash->warning('The requested workshop item has not been accepted yet.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Check if filename matches the one in DB
        if($filename !== $workshop_item->getFilename()){
            $flash->warning('Invalid workshop download URL.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        $filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $workshop_item->getFilename();

        // Check if file exists
        if(!\file_exists($filepath)){
            $flash->warning('The requested workshop file could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Increase download count
        $downloads = $workshop_item->getDownloads();
        $downloads++;
        $workshop_item->setDownloads($downloads);
        $em->flush();

        // Return download
        $response = $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $workshop_item->getFilename() . '"');
        $response->getBody()->write(
            \file_get_contents($filepath)
        );
        return $response;
    }

    public function outputScreenshot(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        $id,
        $filename)
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Check if workshop item has been accepted
        // Admins can always output screenshots
        if(
            $workshop_item->getIsAccepted() !== true
            && $account->getUser()->getRole()->value < UserRole::Admin->value
        ){
            throw new HttpNotFoundException($request, 'workshop item not accepted');
        }

        // Get screenshot dir
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(!\is_dir($screenshot_dir)){
            throw new HttpNotFoundException($request, 'screenshot dir does not exist');
        }

        // Loop trough screenshot to see if one matches
        $screenshot_filepath = null;
        foreach(\glob($screenshot_dir . '/*') as $path){
            if($filename === \basename($path)){
                $screenshot_filepath = $path;
                break;
            }
        }
        if($screenshot_filepath === null){
            throw new HttpNotFoundException($request, 'screenshot not found');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $screenshot_filepath);
        \finfo_close($finfo);

        // Return screenshot
        $response = $response->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($screenshot_filepath)
        );

        die('controllerd');

        return $response;
    }

}
