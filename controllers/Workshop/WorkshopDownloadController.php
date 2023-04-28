<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;

use App\Entity\WorkshopItem;

use App\Account;
use App\FlashMessage;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopDownloadController {


    public function download(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        CacheInterface $cache,
        $id,
        $filename
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item has been accepted
        // Admins can always download workshop items
        if(
            $workshop_item->setIsPublished() !== true
            && $account->getUser()->getRole()->value < UserRole::Admin->value
        ){
            throw new HttpNotFoundException($request);
        }

        // Check if filename matches the one in DB
        if($filename !== $workshop_item->getFilename()){
            throw new HttpNotFoundException($request);
        }

        $filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $workshop_item->getFilename();

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request);
        }

        // Increase download count
        if(!isset($request->getQueryParams()['no_download_increment'])){

            // Get anonymized IP hash & cache key
            $ip           = $request->getAttribute('ip_address');
            $anon_ip      = (new IpAnonymizer())->anonymize($ip ?? '');
            $anon_ip_hash = \sha1($anon_ip);
            $dl_cache_key = 'download-' . $workshop_item->getId() . '-' . $anon_ip_hash;

            // Increase download counter if IP has not downloaded this item (in the last 7 days)
            if($cache->get($dl_cache_key, null) === null){
                $download_count = $workshop_item->getDownloadCount();
                $download_count++;
                $workshop_item->setDownloadCount($download_count);
                $em->flush();

                // Remember download for 7 days
                $cache->set($dl_cache_key, 1, (int) $_ENV['APP_WORKSHOP_DOWNLOAD_IP_REMEMBER_TIME']);
            }
        }

        // Return download
        return $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $workshop_item->getFilename() . '"')
            ->withBody(new LazyOpenStream($filepath, 'r'));
    }
}
