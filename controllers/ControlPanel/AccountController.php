<?php

namespace App\Controller\ControlPanel;

use App\Entity\User;
use App\Entity\UserCookieToken;

use App\Account;
use App\FlashMessage;
use App\UploadSizeHelper;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Compwright\PhpSession\Session;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class AccountController {

    public function accountSettingsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em
    ){

        $response->getBody()->write(
            $twig->render('cp/account-settings.cp.html.twig', [
                'user' => $account->getUser()
            ])
        );

        return $response;
    }

    public function updateEmail(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash
    ){
        // Get post vars
        $post  = $request->getParsedBody();
        $email = (string) $post['new_email_address'] ?? '';

        // Check for valid email address
        if(empty($email) || !\filter_var($email, \FILTER_VALIDATE_EMAIL)){
            $flash->error('Invalid email address.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Check if email address already exists
        $existing_email = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if($existing_email){
            $flash->warning('This email address is already in use.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Update to new email address
        $account->getUser()->setEmail($email);
        $em->flush();

        $flash->success('Your email address has been updated!');

        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function removeEmail(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        $token_name,
        $token_value
    ){
        // Check for valid logout request
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            $flash->error('Invalid CSRF token.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Update to new email address
        $account->getUser()->setEmail(null);
        $em->flush();

        $flash->success('Your email address has been removed!');

        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function updatePassword(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash
    ){
        // Get post vars
        $post             = $request->getParsedBody();
        $current_password = (string) $post['current_password'] ?? '';
        $new_password     = (string) $post['new_password'] ?? '';
        $repeat_password  = (string) $post['repeat_password'] ?? '';

        // Check if current password is correct
        if(!\password_verify($current_password, $account->getUser()->getPassword())){
            $flash->error('Your current password is not correct.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Make sure passwords match
        if($new_password !== $repeat_password){
            $flash->warning('The given passwords did not match.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Update to new password
        $account->getUser()->setPassword($new_password);
        $em->flush();

        $flash->success('You successfully updated your password!');
        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function updateAvatar(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash,
        UploadSizeHelper $upload_size_helper,
    ){
        // Get avatar file
        $files = $request->getUploadedFiles();
        $file  = $files['avatar'] ?? null;

        // Check if avatar file is valid uploaded file
        if(!($file instanceof UploadedFileInterface) || $file->getError() === \UPLOAD_ERR_NO_FILE){
            $flash->error('Missing or invalid avatar file upload.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Check file extension
        $filename = $file->getClientFilename();
        $file_extension = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
        if(!\in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])){
            $flash->warning('Invalid avatar image file. Allowed file types: jpg, jpeg, png, gif');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Check filesize
        if($file->getSize() > $upload_size_helper->getFinalAvatarUploadSize()){
            $flash->warning(
                'Maximum upload filesize for avatar exceeded. (' .
                BinaryFormatter::bytes($upload_size_helper->getFinalAvatarUploadSize())->format() .
                ')'
            );
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Check if avatar upload directory exists
        // Create it if it doesn't
        $avatar_dir = $_ENV['APP_AVATAR_STORAGE'] ?? null;
        if(empty($avatar_dir)){
            throw new \Exception('Avatar storage directory not set: \'APP_AVATAR_STORAGE\'');
        }
        if(!is_dir($avatar_dir)){
            if(!\mkdir($avatar_dir)){
                throw new \Exception('Failed to create avatar storage directory: \'' . $avatar_dir . '\'');
            }
        }

        // Remove any existing avatar
        $existing_avatar = $account->getUser()->getAvatar();
        if($existing_avatar){
            $existing_avatar_path = $avatar_dir . '/' . $existing_avatar;
            if(\file_exists($existing_avatar_path)){
                if(!\unlink($existing_avatar_path)){
                    throw new \Exception("Failed to remove avatar: '{$existing_avatar_path}'");
                }
            }
        }

        // Create avatar filename & path
        $avatar_filename = $account->getUser()->getId() . '-' . md5(\microtime(true) . $filename) . '.' . $file_extension;
        $avatar_path = $avatar_dir . '/' . $avatar_filename;

        // Move screenshot
        $file->moveTo($avatar_path);
        if(!\file_exists($avatar_path)){
            throw new \Exception('Failed to move uploaded avatar');
        }

        $account->getUser()->setAvatar($avatar_filename);
        $em->flush();

        $flash->success('You have successfully updated your avatar!');
        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function removeAvatar(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        $token_name,
        $token_value
    ){
        // Check for valid logout request
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            $flash->error('Invalid CSRF token.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Check if user has an avatar
        if($account->getUser()->getAvatar() === null){
            $flash->warning('You do not have an avatar.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Get avatar dir
        $avatar_dir = $_ENV['APP_AVATAR_STORAGE'] ?? null;
        if(empty($avatar_dir)){
            throw new \Exception('Avatar storage directory not set: \'APP_AVATAR_STORAGE\'');
        }
        if(!is_dir($avatar_dir)){
            throw new \Exception("Avatar storage directory does not exist: '{$avatar_dir}'");
        }

        // Remove avatar file
        $avatar_path = $avatar_dir . '/' . $account->getUser()->getAvatar();
        if(\file_exists($avatar_path)){
            if(!\unlink($avatar_path)){
                throw new \Exception("Failed to remove avatar: '{$avatar_path}'");
            }
        }

        // Update user
        $account->getUser()->setAvatar(null);
        $em->flush();

        $flash->success('Your avatar has been successfully removed!');

        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function logout(
        Request $request,
        Response $response,
        CsrfGuard $csrf_guard,
        Session $session,
        EntityManager $em,
        $token_name,
        $token_value,
    ){
        // Check for valid logout request
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if($valid){
            $session['uid'] = null;
        }

        // Check if 'remember me' token is set (and valid)
        $cookies = $request->getCookieParams();
        $token = (string) ($cookies['user_cookie_token'] ?? '');
        if($token && \preg_match('~^[a-zA-Z0-9]+$~', $token)){

            // Find token in DB
            $cookieToken = $em->getRepository(UserCookieToken::class)->findOneBy(['token' => $token]);
            if($cookieToken){

                // Remove token
                $em->remove($cookieToken);
                $em->flush();
            }
        }

        // Remove possible 'remember me' cookie
        $response = FigResponseCookies::remove($response, 'user_cookie_token');

        // Redirect back to homepage
        $response = $response->withHeader('Location', '/')->withStatus(302);
        return $response;
    }

}
