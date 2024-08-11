<?php

namespace App\Controller\AdminCP;

use App\Enum\BanType;
use App\Enum\UserRole;

use App\Entity\Ban;
use App\Entity\User;
use App\Entity\UserIpLog;

use App\Account;
use App\BanChecker;
use App\FlashMessage;
use App\DiscordNotifier;

use Slim\Csrf\Guard;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;

use Xenokore\Utility\Helper\StringHelper;

class AdminBanController {

    public function bansIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $bans = $em->getRepository(Ban::class)->findAll();

        $response->getBody()->write(
            $twig->render('admincp/bans/ban.list.admincp.html.twig', [
                'bans' => $bans
            ])
        );

        return $response;
    }

    public function banAddIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){

        $response->getBody()->write(
            $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
        );
        return $response;
    }

    public function banAdd(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        CacheInterface $cache,
    ){
        // Get POST data
        $post    = $request->getParsedBody();
        $pattern = (string) ($post['pattern'] ?? null);
        $reason  = (string) ($post['reason'] ?? null);

        // Get ban type
        $type = BanType::tryFrom((int) ($post['type'] ?? null));
        if($type === null){
            $flash->warning('Missing or invalid type.');
            $response->getBody()->write(
                $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
            );
            return $response;
        }

        // Make sure pattern is set
        if(!$pattern){
            $flash->warning('Missing pattern.');
            $response->getBody()->write(
                $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
            );
            return $response;
        }

        // Make sure IP pattern is not private/protected
        if($type == BanType::IP) {
            foreach([
                '0.0.0.0',
                '127.0.0.1',
                '192.168.0.0',
                '255.255.255.255',
                '::',
                '::1',
                'ff00::',
                'fc00::',
            ] as $protected_ip)
            {
                if(StringHelper::match($protected_ip, $pattern)){
                    $flash->warning('This IP pattern would affect a protected or private IP');
                    $response->getBody()->write(
                        $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
                    );
                    return $response;
                }
            }
        }

        // Loop trough admins and make sure this wouldn't ban one of them
        $admins = $em->getRepository(User::class)->findBy(['role' => UserRole::Admin]);
        /** @var User $admin */
        foreach($admins as $admin)
        {
            // Loop trough IP logs
            $ip_logs = $admin->getIpLogs();
            /** @var UserIpLog $ip_log */
            foreach($ip_logs as $ip_log)
            {
                // Check if this IP log matches the new pattern
                $matches_admin = false;
                if($type == BanType::IP && $ip_log->getIp() !== null) {
                    if(StringHelper::match($ip_log->getIp(), $pattern)){
                        $matches_admin = true;
                    }
                } elseif ($type == BanType::Hostname && $ip_log->getHostName() !== null) {
                    if(StringHelper::match($ip_log->getHostName(), $pattern)){
                        $matches_admin = true;
                    }
                }

                // This would ban an admin
                if($matches_admin === true) {
                    $flash->warning('Unable to create a ban pattern that would affect an admin.');
                    $response->getBody()->write(
                        $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
                    );
                    return $response;
                }
            }
        }

        // Check if we only want to preview this ban
        if(\array_key_exists('preview', $post))
        {
            $matches = [];

            // Loop trough all IP logs
            $ip_logs = $em->getRepository(UserIpLog::class)->findAll();
            /** @var UserIpLog $ip_log */
            foreach($ip_logs as $ip_log)
            {
                if($type == BanType::IP && $ip_log->getIp() !== null) {
                    if(StringHelper::match($ip_log->getIp(), $pattern)){
                        $matches[] = $ip_log;
                    }
                } elseif ($type == BanType::Hostname && $ip_log->getHostName() !== null) {
                    if(StringHelper::match($ip_log->getHostName(), $pattern)){
                        $matches[] = $ip_log;
                    }
                }
            }

            if(\count($matches) === 0)
            {
                $flash->info('No IP logs match this ban pattern');
                $response->getBody()->write(
                    $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
                );
                return $response;
            } else {
                $response->getBody()->write(
                    $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases(), 'ip_logs' => $matches])
                );
                return $response;
            }
        }

        // Create ban
        $ban = new Ban();
        $ban->setType($type);
        $ban->setPattern($pattern);

        // Add optional reason
        if($reason){
            $ban->setReason($reason);
        }

        // Save ban pattern to DB
        $em->persist($ban);
        $em->flush();

        // Refresh ban cache
        $cache->delete(BanChecker::BAN_CACHE_KEY);

        // Success
        $flash->success('Ban pattern created!');
        $response = $response->withHeader('Location', '/admin/ban/list')->withStatus(302);
        return $response;
    }

    public function banEditIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){

        // Get article
        $ban = $em->getRepository(Ban::class)->find($id);
        if(!$ban){
            $flash->warning('Ban pattern not found.');
            $response = $response->withHeader('Location', '/admin/ban/list')->withStatus(302);
            return $response;
        }

        // Output
        $response->getBody()->write(
            $twig->render('admincp/bans/ban.edit.admincp.html.twig', [
                'ban'   => $ban,
                'ban_types' => BanType::cases()
            ])
        );
        return $response;
    }

    public function banEdit(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        CacheInterface $cache,
        $id
    ){
        // Get POST data
        $post    = $request->getParsedBody();
        $pattern = (string) ($post['pattern'] ?? null);
        $reason  = (string) ($post['reason'] ?? null);

        // Get ban entity
        $ban = $em->getRepository(Ban::class)->find($id);
        if(!$ban){
            throw new HttpNotFoundException($request);
        }

        // Get ban type
        $type = BanType::tryFrom((int) ($post['type'] ?? null));
        if($type === null){
            $flash->warning('Missing or invalid type.');
            $response->getBody()->write(
                $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
            );
            return $response;
        }

        // Make sure pattern is set
        if(!$pattern){
            $flash->warning('Missing pattern.');
            $response->getBody()->write(
                $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
            );
            return $response;
        }

        // TODO: loop trough admins and don't allow anything that matches them

        // Update ban
        $ban->setType($type);
        $ban->setPattern($pattern);

        // Update optional reason
        if($reason){
            $ban->setReason($reason);
        } else {
            $ban->setReason(null);
        }

        // Update DB
        $em->flush();

        // Refresh ban cache
        $cache->delete(BanChecker::BAN_CACHE_KEY);

        // Success
        $flash->success('Ban pattern updated!');
        $response = $response->withHeader('Location', '/admin/ban/list')->withStatus(302);
        return $response;
    }

    public function banDelete(
        Request $request,
        Response $response,
        EntityManager $em,
        FlashMessage $flash,
        Guard $csrf_guard,
        CacheInterface $cache,
        $id,
        $token_name,
        $token_value,
    ){
        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpForbiddenException($request);
        }

        // Get ban
        $ban = $em->getRepository(Ban::class)->find($id);
        if(!$ban){
            throw new HttpException($request);
        }

        // Delete ban pattern
        $em->remove($ban);
        $em->flush();
        $flash->success('Ban pattern successfully removed!');

        // Refresh ban cache
        $cache->delete(BanChecker::BAN_CACHE_KEY);

        // Response
        $response = $response->withHeader('Location', '/admin/ban/list')->withStatus(302);
        return $response;
    }

}
