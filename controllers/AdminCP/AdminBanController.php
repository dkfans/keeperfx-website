<?php

namespace App\Controller\AdminCP;

use App\Enum\BanType;

use App\Entity\Ban;

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
            if(false == \filter_var($pattern, \FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE)){
                $flash->warning('Invalid IP pattern.');
                $response->getBody()->write(
                    $twig->render('admincp/bans/ban.add.admincp.html.twig', ['ban_types' => BanType::cases()])
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
