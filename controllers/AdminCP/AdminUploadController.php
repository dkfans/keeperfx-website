<?php

namespace App\Controller\AdminCP;

use App\Account;
use App\DiscordNotifier;
use App\Entity\NewsArticle;
use App\FlashMessage;
use App\UploadSizeHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;
use Directory;
use Slim\Csrf\Guard as CsrfGuard;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpBadRequestException;
use Xenokore\Utility\Helper\DirectoryHelper;

class AdminUploadController {

    public function uploadIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em
    ){
        // Check if uploads are enabled
        if(!isset($_ENV['APP_ADMIN_UPLOAD_ENABLED']) || !$_ENV['APP_ADMIN_UPLOAD_ENABLED']){
            throw new HttpNotFoundException($request);
        }

        // Get directory
        $dir = $_ENV['APP_ADMIN_UPLOAD_STORAGE'];

        // Make sure directory exists and is a dir
        if(!\file_exists($dir) || !\is_dir($dir)){
            $flash->error("Configured upload directory does not exist or is not a directory: {$dir}");
            $response->getBody()->write(
                $twig->render('cp/_cp_layout.html.twig')
            );
            return $response;
        }

        // Make sure directory is writable
        if(!\is_writable($dir)){
            $flash->error("Configured upload directory is not writable: {$dir}");
            $response->getBody()->write(
                $twig->render('cp/_cp_layout.html.twig')
            );
            return $response;
        }

        // Get files
        $files = [];
        foreach(DirectoryHelper::tree($dir, false) as $file){
            $files[] = [
                'filename' => \basename($file),
                'size'     => \filesize($file),
                'date'     => \filemtime($file),
            ];
        }

        // Response
        $response->getBody()->write(
            $twig->render('admincp/upload.admincp.html.twig', [
                'files' => $files
            ])
        );
        return $response;
    }

    public function upload(
        Request $request,
        Response $response,
        FlashMessage $flash,
        UploadSizeHelper $upload_size_helper,
    ) {
        // Check if uploads are enabled
        if(!isset($_ENV['APP_ADMIN_UPLOAD_ENABLED']) || !$_ENV['APP_ADMIN_UPLOAD_ENABLED']){
            throw new HttpNotFoundException($request);
        }

        // Get directory
        $dir = $_ENV['APP_ADMIN_UPLOAD_STORAGE'];

        // Get file
        $uploaded_files = $request->getUploadedFiles();

        // Check if a file was uploaded
        if(empty($uploaded_files['file']) || !($uploaded_files['file'] instanceof UploadedFileInterface) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('You did not submit a file');
            $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
            return $response;
        } else {

            // Check workshop file filesize
            if($uploaded_files['file']->getSize() > $upload_size_helper->getMaxCalculatedFileUpload()){
                $flash->warning(
                    'Maximum upload size exceeded. (' .
                    BinaryFormatter::bytes($upload_size_helper->getMaxCalculatedFileUpload())->format() .
                    ')'
                );
                $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
                return $response;
            }
        }

        // Get file and filename
        $file      = $uploaded_files['file'];
        $filename  = $file->getClientFilename();
        $file_path = $dir . '/' . $filename;

        // Store the uploaded file
        $file->moveTo($file_path);
        if(!\file_exists($file_path)){
            $flash->error('Failed to move uploaded file to configured directory');
            $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
            return $response;
        }

        // Success
        $flash->success('File uploaded!');
        $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
        return $response;
    }

    public function delete(
        Request $request,
        Response $response,
        CsrfGuard $csrf_guard,
        FlashMessage $flash,
        $filename,
        $token_name,
        $token_value,
    ) {
        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Check if uploads are enabled
        if(!isset($_ENV['APP_ADMIN_UPLOAD_ENABLED']) || !$_ENV['APP_ADMIN_UPLOAD_ENABLED']){
            throw new HttpNotFoundException($request);
        }

        // Get directory
        $dir = $_ENV['APP_ADMIN_UPLOAD_STORAGE'];

        // Get avatar filepath
        $filepath = $dir . '/' . $filename;

        // Check if file exists
        if(!\file_exists($filepath)){
            $flash->error("File '{$filename}' can not be deleted because it is not found");
            $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
            return $response;
        }

        // Delete file
        if(!@\unlink($filepath)){
            $flash->error("Failed to delete file '{$filename}'");
            $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
            return $response;
        }

        // Success
        $flash->success("File '{$filename}' deleted");
        $response = $response->withHeader('Location', '/admin/uploads')->withStatus(302);
        return $response;
    }

}
