<?php

namespace App\Controller\ControlPanel;

use App\Entity\User;
use App\Entity\UserBio;
use App\Entity\UserCookieToken;
use App\Entity\UserEmailVerification;

use App\Theme;
use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\UploadSizeHelper;
use App\Helper\ThumbnailHelper;
use App\Workshop\WorkshopCache;

use Fgribreau\MailChecker;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Compwright\PhpSession\Session;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;
use Crunz\HttpClient\HttpClientException;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;

class AccountController {

    public function accountSettingsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        FlashMessage $flash,
        EntityManager $em,
        Theme $theme,
    ){
        // Response
        $response->getBody()->write(
            $twig->render('cp/account-settings.cp.html.twig', [
                'user' => $account->getUser(),
                'website_theme' => [
                    'current' => $theme->getCurrentTheme(),
                    'all'     => $theme->getAllThemes(),
                ],
            ])
        );

        return $response;
    }

    public function updateCountry(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash
    ){
        // Get country list [XX => emoji flag]
        $countries = require APP_ROOT . '/config/country.flag.config.php';

        // Get post vars
        $post         = $request->getParsedBody();
        $country_code = (string) $post['country'] ?? '';

        // Update country code
        if (strlen($country_code) === 2 && \array_key_exists($country_code, $countries)){
            $account->getUser()->setCountry($country_code);
        } else {
            $account->getUser()->setCountry(null);
        }

        // Save changes to DB
        $em->flush();

        $flash->success('Your country has been updated!');

        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function updateAboutMe(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        FlashMessage $flash
    ){
        // Get post vars
        $post  = $request->getParsedBody();
        $about_me = (string) ($post['about_me'] ?? '');

        // Check if user has a bio
        $bio = $account->getUser()->getBio();
        if(empty($about_me)){

            // Handle removal
            if($bio){
                $em->remove($bio);
                $em->flush();
            }
        } else {

            // Handle update/creation
            if($bio){
                $bio->setBio($about_me);
            } else {
                $new_bio = new UserBio();
                $new_bio->setBio($about_me);
                $new_bio->setUser($account->getUser());
                $em->persist($new_bio);
            }
            $em->flush();
        }

        $flash->success('About-me updated!.');
        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function updateEmail(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        Session $session,
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

        // Make sure this is not a throwaway email address
        if(!MailChecker::isValid($email)){
            $flash->warning('Invalid email address.');
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
        $account->getUser()->setEmailVerified(false);
        $em->flush();

        // Email verification
        $account->removeExistingEmailVerification();
        $email_id = $account->createEmailVerification();
        // Add the mail ID to the session so it's instantly sent
        if($email_id){
            $session['send_email'] = $email_id;
        }

        // Show message to user
        $flash->success('Your email address has been updated!');

        // Move back to user account page
        $response = $response->withHeader('Location', '/account')->withStatus(302);
        return $response;
    }

    public function resendVerificationEmail(
        Request $request,
        Response $response,
        Account $account,
        CsrfGuard $csrf_guard,
        FlashMessage $flash,
        Session $session,
        CacheInterface $cache,
        $token_name,
        $token_value
    ){
        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Make sure email address is not verified yet
        if($account->getUser()->isEmailVerified()){
            $flash->warning('Your email address has already been verified.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Get the verification
        $verification = $account->getUser()->getEmailVerification();
        if(!$verification){

            // Create new verification
            $email_id = $account->createEmailVerification(); // This also flushes any DB changes
            if(!$email_id){
                throw new \Exception('failed to create new verification');
            }

            // Make the email sent instantly when the user loads the next page
            $session['send_email'] = $email_id;

            // Show sent and return
            $flash->success('Verification email sent!');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Make sure user is not trying to send emails too fast
        $cache_key = 'email_verification:' . $verification->getToken();
        if($cache->has($cache_key)){
            $flash->warning('Please wait a few minutes before resending the verification email.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Make a new email
        $email_id = $account->createEmailVerificationMail($verification);
        if(!$email_id){
            $flash->warning('Something went wrong while sending the verification email. Try again later.');
        } else {
            $flash->success('Verification email sent!');

            // Make the email sent instantly when the user loads the next page
            $session['send_email'] = $email_id;

            // Remember that this mail has been sent
            $cache->set($cache_key, true, 180); // 3 minutes
        }

        // Return
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
        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Check if this user needed to be verified
        $verification = $account->getUser()->getEmailVerification();
        if($verification){
            $em->remove($verification);
        }

        // Update to new email address
        $account->getUser()->setEmail(null);
        $account->getUser()->setEmailVerified(false);
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

    public function updateTheme(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        TwigEnvironment $twig,
        Theme $theme,
        FlashMessage $flash,
    ){
        // Get post vars
        $post     = $request->getParsedBody();
        $theme_id = (string) $post['theme_id'] ?? '';

        // Update the theme on the account
        if($account->updateTheme($theme_id) === false){
            $flash->error('Failed to update theme');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Return success
        $flash->success('Theme updated!');
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
        WorkshopCache $workshop_cache,
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
        if(!\in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
            $flash->warning('Invalid avatar image file. Allowed file types: jpg, jpeg, png, gif, webp');
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
        $avatar_dir = Config::get('storage.path.avatar');
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

        // Remove any existing small avatar
        $existing_avatar_small = $account->getUser()->getAvatarSmall();
        if($existing_avatar_small){
            $existing_avatar_small_path = $avatar_dir . '/' . $existing_avatar_small;
            if(\file_exists($existing_avatar_small_path)){
                if(!\unlink($existing_avatar_small_path)){
                    throw new \Exception("Failed to remove small avatar: '{$existing_avatar_small_path}'");
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

        // Set avatar
        $account->getUser()->setAvatar($avatar_filename);

        // Generate a small avatar for this user
        $avatar_small = ThumbnailHelper::createThumbnail($avatar_path, 128, 128);
        if($avatar_small){
            $account->getUser()->setAvatarSmall($avatar_small);
        }

        // Save changes to DB
        $em->flush();

        // We have to clear the workshop browse cache because our avatar is visible there
        $workshop_cache->clearAllCachedBrowsePageData();

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
        WorkshopCache $workshop_cache,
        $token_name,
        $token_value
    ){
        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Check if user has an avatar
        if($account->getUser()->getAvatar() === null){
            $flash->warning('You do not have an avatar.');
            $response = $response->withHeader('Location', '/account')->withStatus(302);
            return $response;
        }

        // Get avatar dir
        $avatar_dir = Config::get('storage.path.avatar');
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

        // Remove small avatar file
        $avatar_small_path = $avatar_dir . '/' . $account->getUser()->getAvatarSmall();
        if(\file_exists($avatar_path)){
            if(!\unlink($avatar_small_path)){
                throw new \Exception("Failed to remove small avatar: '{$avatar_small_path}'");
            }
        }

        // Update database stuff
        $account->getUser()->setAvatar(null);
        $account->getUser()->setAvatarSmall(null);
        $em->flush();

        // We have to clear the workshop browse cache because our avatar was visible there
        $workshop_cache->clearAllCachedBrowsePageData();

        // Success
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
        Account $account,
        $token_name,
        $token_value,
    ){
        // Check for valid CSRF token
        if(!$csrf_guard->validateToken($token_name, $token_value)){
            throw new HttpForbiddenException($request);
        }

        // Logout user
        $account->clearCurrentLoggedInUser();

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
