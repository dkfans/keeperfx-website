<?php

namespace App\Controller\AdminCP;

use App\Entity\UserIpLog;

use App\FlashMessage;
use App\Helper\IpHelper;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;

class AdminIpLookupController {

    public function logsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
    ){
        $ip_logs = $em->getRepository(UserIpLog::class)->findBy([], ['last_seen_timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('admincp/ip-logs.admincp.html.twig', [
                'ip_logs' => $ip_logs
            ])
        );

        return $response;
    }

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

                    // Add IP (it's often set as query)
                    if(IpHelper::isValidIp($json['query'])){
                        $info['ip'] = $json['query'];
                    }

                    // Merge json into info array
                    $info = \array_merge($info, $json);

                    // Remove API lookup data
                    unset($info['query']);
                    unset($info['status']);

                    // Convert some booleans to strings
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
                if(!in_array($ip_log->getUser(), $users)){
                    $users[] = $ip_log->getUser();
                }
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

    public function associationsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em,
    ){
        $associations = [
            'ip'   => [],
            'host' => [],
        ];

        $check_array = [
            'ip'   => [],
            'host' => [],
        ];

        /** @var UserIpLog[] $logs */
        $logs = $em->getRepository(UserIpLog::class)->findAll();

        foreach($logs as $log)
        {
            $ip   = $log->getIp();
            $host = $log->getHostName();
            $user = $log->getUser();

            if(!isset($check_array['ip'][$ip])){
                $check_array['ip'][$ip][] = $user;
            } else {

                foreach($check_array['ip'][$ip] as $check_user){

                    if($check_user != $user){

                        if(!isset($associations['ip'][$ip])){
                            $associations['ip'][$ip][] = $user;
                            $associations['ip'][$ip][] = $check_user;
                        } else {
                            if(!\array_search($user, $associations['ip'][$ip])){
                                $associations['ip'][$ip][] = $user;
                            }

                            if(!\array_search($check_user, $associations['ip'][$ip])){
                                $associations['ip'][$ip][] = $check_user;
                            }

                            if(!\array_search($user, $check_array['ip'][$ip])){
                                $check_array['ip'][$ip][] = $user;
                            }
                        }
                    }
                }
            }

            if($host !== null)
            {

                if(!isset($check_array['host'][$host])){
                    $check_array['host'][$host][] = $user;
                } else {

                    foreach($check_array['host'][$host] as $check_user){

                        if($check_user != $user){

                            if(!isset($associations['host'][$host])){
                                $associations['host'][$host][] = $user;
                                $associations['host'][$host][] = $check_user;
                            } else {

                                if(!\array_search($user, $associations['host'][$host])){
                                    $associations['host'][$host][] = $user;
                                }

                                if(!\array_search($check_user, $associations['host'][$host])){
                                    $associations['host'][$host][] = $check_user;
                                }

                                if(!\array_search($user, $check_array['host'][$host])){
                                    $check_array['host'][$host][] = $user;
                                }

                            }
                        }
                    }
                }
            }
        }

        $response->getBody()->write(
            $twig->render('admincp/ip-associations.admincp.html.twig', [
                'ip_assoc'   => $associations['ip'],
                'host_assoc' => $associations['host'],
            ])
        );

        return $response;
    }

}
