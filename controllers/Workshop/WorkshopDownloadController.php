<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;

use App\Account;
use App\FlashMessage;
use App\Config\Config;

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
        $item_id,
        $file_id,
        $filename
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item has been published
        // Users with a role of moderator or higher can always download workshop items
        if(
            $workshop_item->isPublished() !== true
            && ($account->getUser() === null || $account->getUser()->getRole()->value < UserRole::Moderator->value)
        ){
            throw new HttpNotFoundException($request);
        }

        // Check if file id is found
        $file = $em->getRepository(WorkshopFile::class)->findOneBy(['id' => $file_id, 'item' => $workshop_item]);
        if(!$file){
            throw new HttpNotFoundException($request);
        }

        // Check if filename matches the one in DB
        if($file->getFilename() !== $filename){
            throw new HttpNotFoundException($request);
        }

        $filepath = Config::get('storage.path.workshop') . '/' . $workshop_item->getId() . '/files/' . $file->getStorageFilename();

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request);
        }

        // Increase download count
        if(!isset($request->getQueryParams()['no_download_increment'])){

            // Get anonymized IP hash & cache key
            // This way we can protect against people abusing download numbers yet still be privacy friendly
            $ip           = $request->getAttribute('ip_address');
            $anon_ip      = (new IpAnonymizer())->anonymize($ip ?? '');
            $anon_ip_hash = \sha1($anon_ip);
            $dl_cache_key = 'download-' . $workshop_item->getId() . '-' . $file->getId() . '-' . $anon_ip_hash;

            // If this anonymized data is not in our cache,
            // then this user has not downloaded something recently and should be counted
            if($cache->get($dl_cache_key, null) === null){

                // Increase the download count on the workshop item
                $download_total_count = $workshop_item->getDownloadCount();
                $download_total_count++;
                $workshop_item->setDownloadCount($download_total_count);

                // Increase the download count on the file
                $download_count = $file->getDownloadCount();
                $download_count++;
                $file->setDownloadCount($download_count);

                // Add to DB
                $em->flush();

                // Add the anonymized data to the cache
                $cache->set($dl_cache_key, 1, (int) $_ENV['APP_WORKSHOP_DOWNLOAD_IP_REMEMBER_TIME']);
            }
        }

        // Return download
        return $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Length', $file->getSize())
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $file->getFilename() . '"')
            ->withBody(new LazyOpenStream($filepath, 'r'));
    }
}
