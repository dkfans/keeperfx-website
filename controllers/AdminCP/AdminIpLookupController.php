<?php

namespace App\Controller\AdminCP;

use App\Entity\NewsArticle;

use App\Account;
use App\FlashMessage;
use App\DiscordNotifier;
use App\Entity\UserIpLog;
use App\Helper\IpHelper;
use App\UploadSizeHelper;
use App\Helper\ThumbnailHelper;

use Slim\Csrf\Guard;
use Doctrine\ORM\EntityManager;
use ByteUnits\Binary as BinaryFormatter;
use Doctrine\Common\Collections\ArrayCollection;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

class AdminIpLookupController {

    public function lookup(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em,
        string $type,
        string $string,
    ){

        // Make sure type is valid
        if(!\in_array($type, ['ip', 'host_name', 'isp'])){
            throw new HttpNotFoundException($request);
        }

        // Return data
        $info  = [];
        $users = [];

        // Create a client to do the API lookup
        $client = new \GuzzleHttp\Client(
            ['verify' => false] // Don't verify SSL connection
        );

        // API lookup
        if($type === 'ip' || $type === 'host_name'){

            // Make sure IP is valid IPv4 or IPv6
            if($type === 'ip' && IpHelper::isValidIp($string) === false){
                $flash->warning("Invalid IP: {$string}");
                $response->getBody()->write(
                    $twig->render('cp/_cp_layout.html.twig')
                );
                return $response;
            }

            // Make sure hostname is valid
            if($type === 'host_name' && \filter_var($string, \FILTER_VALIDATE_DOMAIN) === false){
                $flash->warning("Invalid hostname: {$string}");
                $response->getBody()->write(
                    $twig->render('cp/_cp_layout.html.twig')
                );
                return $response;
            }

            // Ignore localhost
            if($type === 'ip' && $string === "127.0.0.1"){
                $flash->info("No lookup performed for 127.0.0.1");
            } else {

                $success = true;
                $url = "http://ip-api.com/json/{$string}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,asname,reverse,mobile,proxy,hosting,query";

                // Get info from API
                $res = $client->request('GET', $url);
                $content = $res->getBody();
                if(!$content){
                    $flash->error("Failed to get API response");
                    $success = false;
                }

                // Decode JSON
                $json = \json_decode($content, true);
                if(!$json){
                    $flash->info("Failed to decode JSON");
                    $success = false;
                }

                // Make sure lookup is successful
                if(!isset($json['status']) || !\is_string($json['status']) || $json['status'] !== 'success'){
                    $flash->info("Failed to get info");
                    $success = false;
                }

                if($success){
                    $info = $json;

                    // Remove API lookup data
                    unset($info['query']);
                    unset($info['status']);

                    // Trick to convert boolean to string
                    $info['mobile']  = \json_encode($json['mobile']);
                    $info['proxy']   = \json_encode($json['proxy']);
                    $info['hosting'] = \json_encode($json['hosting']);
                }

            }
        }

        /** @var null|UserIpLog[] */
        $ip_logs = $em->getRepository(UserIpLog::class)->findBy([$type => $string]);
        if($ip_logs){
            foreach($ip_logs as $ip_log){
                $users[] = $ip_log->getUser();
            }
        }

        $response->getBody()->write(
            $twig->render('admincp/ip-lookup.admincp.html.twig', [
                'type'   => $type,
                'string' => $string,
                'info'   => $info,
                'users'  => $users,
            ])
        );

        return $response;
    }

}