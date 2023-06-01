<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopCategory;

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

    public function outputImage(
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

        // Handle non published workshop item
        if($workshop_item->getIsPublished() === false){
            if(
                !$account->isLoggedIn()
                || (
                    $account->isLoggedIn()
                    && $workshop_item->getSubmitter() !== $account->getUser()
                    && $account->getUser()->getRole()->value < UserRole::Moderator->value
                )
            ) {
                throw new HttpNotFoundException($request, 'item is not published yet and user is not allowed to access it');
            }
        }

        // Get image dir
        $image_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/images';
        if(!\is_dir($image_dir)){
            throw new HttpNotFoundException($request, 'image dir does not exist');
        }

        // Loop trough screenshot to see if one matches
        $image_filepath = null;
        foreach(\glob($image_dir . '/*') as $path){
            if($filename === \basename($path)){
                $image_filepath = $path;
                break;
            }
        }
        if($image_filepath === null){
            throw new HttpNotFoundException($request, 'screenshot not found');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $image_filepath);
        \finfo_close($finfo);

        // Return screenshot
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        return $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type)
            ->withBody(new LazyOpenStream($image_filepath, 'r'));

        return $response;
    }

}
