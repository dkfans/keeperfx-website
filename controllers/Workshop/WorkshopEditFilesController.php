<?php

namespace App\Controller\Workshop;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;

use URLify;
use App\Account;
use App\FlashMessage;
use App\UploadSizeHelper;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Slim\Psr7\UploadedFile;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopEditFilesController {

    public function index(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        $item_id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit the files for this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Show edit page
        $response->getBody()->write(
            $twig->render('workshop/edit.files.workshop.html.twig', [
                'workshop_item' => $workshop_item
            ])
        );
        return $response;
    }

    public function upload(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        UploadSizeHelper $upload_size_helper,
        $item_id
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit the files for this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Get file uploads
        $uploaded_files = $request->getUploadedFiles();

        // Make sure uploaded file is set
        if(empty($uploaded_files['file']) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('No file was uploaded...');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Get variables
        $file     = $uploaded_files['file'];
        $filename = $file->getClientFilename();

        // Make sure upload file does not exceed file size
        if($file->getSize() > $upload_size_helper->getFinalWorkshopItemUploadSize()){
            $flash->warning('File upload size is too big.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Set directories for files
        $workshop_item_dir       = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_files_dir = $workshop_item_dir . '/files';

        // Make sure output directory exists
        if(!is_dir($workshop_item_files_dir)) {
            throw new \Exception("Directory does not exist: '{$workshop_item_files_dir}'");
        }

        // Generate storage filename
        $ext              = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
        $str              = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
        $storage_filename = $str . '__' . $ext;
        $storage_path     = $workshop_item_files_dir . '/' . $storage_filename;

        // Move uploaded file to storage
        $file->moveTo($storage_path);
        if(!\file_exists($storage_path)){
            throw new \Exception('Failed to move workshop item file');
        }

        // Create DB entity
        $workshop_file = new WorkshopFile();
        $workshop_file->setItem($workshop_item);
        $workshop_file->setFilename($filename);
        $workshop_file->setStorageFilename($storage_filename);
        $workshop_file->setSize(\filesize($storage_path));

        // Save to DB
        $em->persist($workshop_file);
        $em->flush();

        $flash->success('File uploaded!');

        // Show edit page
        $response->getBody()->write(
            $twig->render('workshop/edit.files.workshop.html.twig', [
                'workshop_item' => $workshop_item,
                'files'         => $em->getRepository(WorkshopFile::class)->findBy(['item' => $workshop_item]),
            ])
        );
        return $response;
    }

    public function delete(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        UploadSizeHelper $upload_size_helper,
        CsrfGuard $csrf_guard,
        $item_id,
        $file_id,
        $token_name,
        $token_value,
    )
    {
        // Check for valid CSRF check
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            // TODO: change to "not allowed" response
            throw new HttpNotFoundException($request);
        }

        // Check if workshop file exists
        $workshop_file = $em->getRepository(WorkshopFile::class)->find($file_id);
        if(!$workshop_file){
            throw new HttpNotFoundException($request);
        }

        // Get storage variables
        $original_filename       = $workshop_file->getFilename();
        $workshop_item_dir       = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_files_dir = $workshop_item_dir . '/files';
        $workshop_file_path      = $workshop_item_files_dir . '/' . $workshop_file->getStorageFilename();

        // Make sure file exists
        if(!\file_exists($workshop_file_path)){
            throw new \Exception("Workshop file does not exist: '{$workshop_file_path}'");
        }

        // Remove file and double check
        @\unlink($workshop_file_path);
        if(\file_exists($workshop_file_path)){
            throw new \Exception("Workshop file still exists after removal...: '{$workshop_file_path}'");
        }

        // Remove from DB
        $em->remove($workshop_file);
        $em->flush();

        // Show success notice to user
        $flash->success(\sprintf(
            "The file '%s' has been successfully removed!",
            $original_filename
        ));

        // Redirect back to file list
        $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId() . '/files')->withStatus(302);
        return $response;
    }
}
