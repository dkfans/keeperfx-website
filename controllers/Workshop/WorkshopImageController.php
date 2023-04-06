<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopType;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopDifficultyRating;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Entity\WorkshopFile;
use App\UploadSizeHelper;

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopImageController {


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
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        return $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type)
            ->withBody(new LazyOpenStream($screenshot_filepath, 'r'));
    }

    public function outputThumbnail(
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

        // Check if workshop item has thumbnail
        if(!$workshop_item->getThumbnail()){
            throw new HttpNotFoundException($request, 'workshop item does not have thumbnail');
        }

        // Check if thumbnail filename matches
        if($workshop_item->getThumbnail() !== $filename){
            throw new HttpNotFoundException($request, 'workshop item does not have thumbnail with that name');
        }

        // Get thumbnail filepath
        $thumbnail_filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $workshop_item->getThumbnail();

        // Check if file exists
        if(!\file_exists($thumbnail_filepath)){
            throw new HttpNotFoundException($request, 'workshop item thumbnail does not exist');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $thumbnail_filepath);
        \finfo_close($finfo);

        // Return thumbnail
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        return $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type)
            ->withBody(new LazyOpenStream($thumbnail_filepath, 'r'));
    }

    public function deleteThumbnail(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $id,
        $token_name,
        $token_value
    ){
        // Validate against CSRF
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            return $response;
        }

        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Make sure current user owns this workshop item
        if($account->getUser() !== $workshop_item->getSubmitter()){
            return $response;
        }

        // Get thumbnail filename
        $filename = $workshop_item->getThumbnail();
        if(!$filename){
            return $response;
        }

        // Get filepath
        $filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $filename;
        if(!\file_exists($filepath)){
            return $response;
        }

        // Delete file
        if(!@\unlink($filepath)){
            throw new \Exception("failed to remove thumbnail: {$filepath}");
        }

        // Update workshop item
        $workshop_item->setThumbnail(null);
        $em->flush();

        // Return view
        $flash->success('Thumbnail successfully removed');
        $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

    public function deleteScreenshot(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $id,
        $filename,
        $token_name,
        $token_value
    ){
        // Validate CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            return $response;
        }

        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Make sure current user owns this workshop item
        if($account->getUser() !== $workshop_item->getSubmitter()){
            return $response;
        }

        // Get screenshots
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(\is_dir($screenshot_dir)){
            foreach(\glob($screenshot_dir . '/*') as $screenshot_file){

                // Check if filename matches
                if(\basename($screenshot_file) === $filename){

                    // Delete screenshot
                    if(!@\unlink($screenshot_file)){
                        throw new \Exception("failed to remove screenshot: {$screenshot_file}");
                    }

                    $flash->success('Screenshot removed!');
                    $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId())->withStatus(302);
                    return $response;
                }
            }
        }

        $flash->warning('Failed to remove screenshot.');
        $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
