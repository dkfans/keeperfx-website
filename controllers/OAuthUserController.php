<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserOAuthToken;

use App\Enum\OAuthProviderType;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Entity\UserCookieToken;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Compwright\PhpSession\Session;
use App\OAuth\OAuthProviderService;
use Twig\Environment as TwigEnvironment;
use Dflydev\FigCookies\FigResponseCookies;

use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;
use Vertisan\OAuth2\Client\Provider\TwitchHelixResourceOwner;

class OAuthUserController {

    public function connect(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Session $session,
        EntityManager $em,
        Account $account,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        OAuthProviderService $provider_service,
        $provider_name,
        $token_name = null,
        $token_value = null,
    ){

        // Make sure provider is valid
        $provider_type = OAuthProviderType::tryFrom($provider_name);
        if($provider_type === null){
            throw new HttpNotFoundException($request);
        }

        // Check if user is logged in and already has a connection for this provider
        if($account->isLoggedIn()){
            $existing_oauth_token = $em->getRepository(UserOAuthToken::class)->findOneBy([
                'provider_type' => $provider_type,
                'user'          => $account->getUser(),
            ]);
            if($existing_oauth_token && $existing_oauth_token->getToken() !== null){
                $flash->error("This user account is already connected to {$provider_type->name}");
                $response = $response->withHeader('Location', '/account/connections')->withStatus(302);
                return $response;
            }
        }

        $provider = $provider_service->getProvider($provider_type);

        $query_params = $request->getQueryParams();

        // Step 1. Get authorization code
        if(!isset($query_params['code']) || !\is_string($query_params['code'])){

            // Check for valid request (anti CSRF)
            $valid = $csrf_guard->validateToken($token_name, $token_value);
            if(!$valid){
                throw new HttpNotFoundException($request);
            }

            // Redirect user to OAuth Provider's login page
            $auth_url = $provider->getAuthorizationUrl([
                'scope' => $provider_service->getScopes($provider_type),
            ]);
            $session['oauth_state'] = $provider->getState();
            $response = $response->withHeader('Location', $auth_url)->withStatus(302);
            return $response;
        }

        // Check given state against previously stored one to mitigate CSRF attack
        if(
            !isset($query_params['state'])
            || !is_string($query_params['state'])
            || !isset($session['oauth_state'])
            || $query_params['state'] !== $session['oauth_state']
        ) {
            return $response;
        }

        // Step 2. Get an access token using the provided authorization code
        try {
            /** @var AccessToken $token */
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $query_params['code']
            ]);
        } catch (\Exception $ex) {
            die('Your access token has expired. Try again.');
            return $response;
        }

        // Step 3. Get user profile
        try {
            /** @var ResourceOwnerInterface|DiscordResourceOwner|TwitchHelixResourceOwner $resource_owner */
            $resource_owner = $provider->getResourceOwner($token);
        } catch (\Exception $ex) {
            die('Failed to grab user details from OAuth provider.');
            return $response;
        }

        // Check if resource owner is linked to a local account
        $user_oauth_token = $em->getRepository(UserOAuthToken::class)->findOneBy([
            'provider_type' => $provider_type,
            'uid'           => $resource_owner->getId()
        ]);
        if($user_oauth_token){

            // Check if users tries to add a token when it should already be connected
            if($account->isLoggedIn()){
                // TODO: make sure this works as expected
                return $response;
            }

            // handle invalidated oauth token
            if($user_oauth_token->getToken() === null || $user_oauth_token->getRefreshToken() === null){
                $user_oauth_token->setToken($token->getToken());
                $user_oauth_token->setRefreshToken($token->getRefreshToken());
                $user_oauth_token->setExpiresTimestamp(
                    \DateTime::createFromFormat('U', (int) $token->getExpires())
                );
                $em->flush();
            }

            // Refresh expired OAuth Token
            if($user_oauth_token->getExpiresTimestamp()->getTimestamp() < time()){

                try {

                    // Get new OAuth Token from provider
                    $new_access_token = $provider->getAccessToken('refresh_token', [
                        'refresh_token' => $user_oauth_token->getRefreshToken()
                    ]);

                    // Update OAuth Token in DB
                    $user_oauth_token->setToken($new_access_token->getToken());
                    $user_oauth_token->setRefreshToken($new_access_token->getRefreshToken());
                    $user_oauth_token->setExpiresTimestamp(
                        \DateTime::createFromFormat('U', (int) $new_access_token->getExpires())
                    );
                    $em->persist($user_oauth_token);
                    $em->flush();

                } catch (\Exception $ex){

                    // Invalidate token in DB
                    $user_oauth_token->setToken(null);
                    $user_oauth_token->setRefreshToken(null);
                    $user_oauth_token->setExpiresTimestamp(null);
                    $em->persist($user_oauth_token);
                    $em->flush();

                    // Show notice on error
                    $flash->warning('Something went wrong while refreshing your OAuth token. You will have to log in again.');
                    // TODO: check session for redirect-after-login
                    $response = $response->withHeader('Location', '/login')->withStatus(302);
                    return $response;
                }
            }

            // Login the user
            $account->setUser($user_oauth_token->getUser());
            $session['uid'] = $user_oauth_token->getUser()->getId();
            $flash->success('You have successfully logged in!');

            // Add a 'remember me' cookie for OAuth login
            $response = FigResponseCookies::set($response, $account->createRememberMeSetCookie($user_oauth_token));

            // Redirect user
            // TODO: check session for redirect-after-login
            $response = $response->withHeader('Location', '/')->withStatus(302);
            return $response;
        }

        // If the user is logged in we will add the token to this account
        // and move them back to the connections page
        if($account->isLoggedIn()){

            // Create OAuth token for user
            $user_oauth_token = new UserOAuthToken();
            $user_oauth_token->setUser($account->getUser());
            $user_oauth_token->setProviderType($provider_type);
            $user_oauth_token->setUid($resource_owner->getId());
            $user_oauth_token->setToken($token->getToken());
            $user_oauth_token->setRefreshToken($token->getRefreshToken());
            $user_oauth_token->setExpiresTimestamp(
                \DateTime::createFromFormat('U', (int) $token->getExpires())
            );
            $em->persist($user_oauth_token);

            // Make changes to DB
            $em->flush();

            // Redirect & show message
            $flash->success('Authorization successful. Your account has been connected.');
            $response = $response->withHeader('Location', '/account/connections')->withStatus(302);
            return $response;
        }

        // At this point we need to register a local account
        $flash->success('Authorization successful. Fill in the rest of the form to register your account.');

        // These variables are used to put some details about the OAuth user on the register page
        $username = null;
        $email    = null;

        // Check if we can use the OAuth users' username for a local account
        if(\method_exists($resource_owner, 'getUsername')){
            $user_with_username = $em->getRepository(User::class)->findOneBy(['username' => $resource_owner->getUsername()]);
            if($user_with_username){
                $flash->info("There is already an account with the username '{$resource_owner->getUsername()}'. Please choose another one.");
            } else {
                $username = $resource_owner->getUsername();
            }
        }

        // Check if we can use the OAuth users' email address for a local account
        if(\method_exists($resource_owner, 'getEmail')){
            $user_with_email = $em->getRepository(User::class)->findOneBy(['email' => $resource_owner->getEmail()]);
            if($user_with_email){
                $flash->info("There is already an account with the email address '{$resource_owner->getEmail()}'. Please choose another one.");
            } else {
                $email = $resource_owner->getEmail();
            }
        }

        // Remember details in session
        $session['oauth_register'] = [
            'uid'           => $resource_owner->getId(),
            'token'         => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires'       => $token->getExpires(),
            'provider_type' => $provider_type,
        ];

        // Add possible Discord avatar hash
        if($provider_type === OAuthProviderType::Discord && \method_exists($resource_owner, 'getAvatarHash')){
            $session['oauth_register']['discord_avatar_hash'] = $resource_owner->getAvatarHash();
        }

        // Render REGISTER view
        $response->getBody()->write(
            $twig->render('register.oauth.html.twig', [
                'username'            => $username,
                'email'               => $email,
                'oauth_provider_type' => $provider_type,
            ])
        );

        return $response;
    }

    public function disconnect(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Session $session,
        EntityManager $em,
        Account $account,
        FlashMessage $flash,
        CsrfGuard $csrf_guard,
        $provider_name,
        $token_name = null,
        $token_value = null,
    ){
        // Make sure provider is valid
        $provider_type = OAuthProviderType::tryFrom($provider_name);
        if($provider_type === null){
            throw new HttpNotFoundException($request);
        }

        // Check for valid request (anti CSRF)
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // TODO: MAKE SURE THAT OUR ACCOUNT WILL NOT GET DELETED !
        // - CHECK IF THE ACCOUNT HAS A NORMAL PASSWORD
        // - OR THERE MUST BE OTHER CONNECTIONS!

        $oauth_token = $em->getRepository(UserOAuthToken::class)->findOneBy([
            'provider_type' => $provider_type,
            'user'          => $account->getUser(),
        ]);

        if(!$oauth_token){
            $flash->error("Failed to remove OAuth connection");
            $response = $response->withHeader('Location', '/account/connections')->withStatus(302);
            return $response;
        }

        $cookie_tokens = $em->getRepository(UserCookieToken::class)->findBy([
            'oauth_token' => $oauth_token,
        ]);

        $user_has_been_logged_out = false;

        if($cookie_tokens){
            foreach($cookie_tokens as $cookie_token) {

                // Check if we need to logout
                $cookies = $request->getCookieParams();
                $token = (string) ($cookies['user_cookie_token'] ?? '');
                if($token && \preg_match('~^[a-zA-Z0-9]+$~', $token) && $token === $cookie_token->getToken()){

                    // Log out user from the current session
                    $account->setUser(null);
                    $session['uid'] = null;
                    $user_has_been_logged_out = true;
                }

                $em->remove($cookie_token);
            }
        }

        $em->remove($oauth_token);
        $em->flush();

        $flash->success("Successfully removed {$provider_type->name} account connection.");

        if($user_has_been_logged_out){
            $flash->info('You have been logged out because the OAuth connection was used for your login session.');
            $response = $response->withHeader('Location', '/')->withStatus(302);
        } else {
            $response = $response->withHeader('Location', '/account/connections')->withStatus(302);
        }

        return $response;
    }

    public function register(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        FlashMessage $flash,
        EntityManager $em,
        Session $session,
        $provider_name
    ){
        // Check if flow is valid. User should have this populated
        if(empty($session['oauth_register'])){
            return $response;
        }

        // Only logged-out guests allowed
        if($account->isLoggedIn()){
            $response = $response->withHeader('Location', '/')->withStatus(302);
            // $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
            return $response;
        }

        // Make sure the user does not try to register multiple times
        $existing_token = $em->getRepository(UserOAuthToken::class)->findOneBy([
            'provider_type' => $session['oauth_register']['provider_type'],
            'uid'           => $session['oauth_register']['uid'],
        ]);
        if($existing_token){
            die('oauth connection already exists');
            return $response;
        }

        $success = true;

        $post = $request->getParsedBody();

        $username = (string) ($post['username'] ?? null);
        $email    = (string) ($post['email'] ?? null);

        // Validate username length
        if(\strlen($username) < 2 || \strlen($username) > 32){
            $success = false;
            $flash->warning('Username has must be at least 2 characters long and can not exceed 32 characters.');
        } else {

            // Validate username charset
            if(!\preg_match('/^[a-zA-Z0-9]+[a-zA-Z0-9\.\_\-]+$/', $username)){
                $success = false;
                $flash->warning(
                    'Username can only contain the following characters: <strong>a-z A-Z 0-9 _ . -</strong>' .
                    '<br />It also must start with a letter or number.'
                );
            }

            // Check if username already exists
            $user = $em->getRepository(User::class)->findBy(['username' => $username]);
            if($user){
                $success = false;
                $flash->warning('Username already in use.');
            }

        }

        // Check if username contains disallowed words
        foreach(Config::get('app.disallowed_username_words') as $word){
            if(strpos($username, $word) !== false){
                $success = false;
                $flash->warning("Username contains a disallowed word: {$word}");
                break;
            }
        }

        // Check if user wants to add an email address
        if(!empty($email)){

            // Validate email address
            if(!filter_var($email, \FILTER_VALIDATE_EMAIL)){
                $success = false;
                $flash->warning('Invalid email address.');
            }

            // Check if email address already exists
            $user_with_email = $em->getRepository(User::class)->findBy(['email' => $email]);
            if($user_with_email){
                $success = false;
                $flash->warning('This email address is already in use.');
            }
        }

        // Make sure ToS and privacy policy have been read and accepted
        if(!isset($post['accepted_tos_and_privacy_policy'])){
            $success = false;
            $flash->warning('You did not accept the Terms of Service and Privacy Policy.');
        }

        // Given details must be valid before creating a user
        if(!$success){

            // Render register page
            $response->getBody()->write(
                $twig->render('register.oauth.html.twig')
            );
            return $response;
        }

        // TODO: discord_avatar_hash

        // Create new user
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $em->persist($user);

        // Create OAuth token for user
        $user_oauth_token = new UserOAuthToken();
        $user_oauth_token->setUser($user);
        $user_oauth_token->setProviderType($session['oauth_register']['provider_type']);
        $user_oauth_token->setUid($session['oauth_register']['uid']);
        $user_oauth_token->setToken($session['oauth_register']['token']);
        $user_oauth_token->setRefreshToken($session['oauth_register']['refresh_token']);
        $user_oauth_token->setExpiresTimestamp(
            \DateTime::createFromFormat('U', (int) $session['oauth_register']['expires'])
        );
        $em->persist($user_oauth_token);

        // Make changes to DB
        $em->flush();

        // Remove oauth register session data
        unset($session['oauth_register']);

        // Immediately log in the user
        $account->setUser($user);
        $session['uid'] = $user->getId();

        // Add a 'remember me' cookie for OAuth login
        $response = FigResponseCookies::set($response, $account->createRememberMeSetCookie($user_oauth_token));

        // Show message and navigate the user
        $flash->success('Successfully registered. You are now logged in.');
        $response = $response->withHeader('Location', '/account')->withStatus(302);
        // $response = $response->withHeader('Location', '/dashboard')->withStatus(302);
        return $response;
    }
}
