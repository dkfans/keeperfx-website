<?php

namespace App;

use App\Enum\UserRole;

use App\Entity\User;
use App\Entity\UserIpLog;
use App\Entity\UserOAuthToken;
use App\Entity\UserCookieToken;
use App\Entity\UserEmailVerification;

use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\Modifier\SameSite;

use App\Helper\IpHelper;
use Xenokore\Utility\Helper\StringHelper;

class Account {

    private User|null $user = null;

    public function __construct(
        private Session $session,
        private EntityManager $em,
        private Mailer $mailer,
        private Theme $theme,
        private FlashMessage $flash,
    ) {
        // Check if current user is logged in
        if(isset($session['uid']) && !is_null($session['uid'])){

            // Search this user in the DB
            // In case a user has a session without a valid user account
            $user = $em->getRepository(User::class)->find($session['uid']);
            if($user){

                    // Check if banned
                    if($user->getRole() == UserRole::Banned){

                        // Show message
                        $this->flash->error("You have been banned.");

                        // Don't login
                        $session['uid'] = null;
                        return;
                    }

                // Set the currently logged in user
                $this->user = $user;

                // Set the theme
                $theme->setTheme($user->getTheme());
            }
        }
    }

    public function createRememberMeSetCookie(?UserOAuthToken $oauth_token = null): SetCookie
    {
        // Make sure user is logged in
        if(!$this->user){
            throw new \Exception('user not set');
        }

        // Find unused cookie token
        $cookie_token = null;
        while($cookie_token === null){
            $cookie_token_new = StringHelper::generate(64);
            $existing_token = $this->em->getRepository(UserCookieToken::class)->findOneBy(['token' => $cookie_token_new]);
            if($existing_token === null){
                $cookie_token = $cookie_token_new;
            }
        }

        // Create cookie token entity
        $token = new UserCookieToken();
        $token->setUser($this->user);
        $token->setToken($cookie_token);

        // Add possible OAuth token to cookie token
        if($oauth_token){
            $token->setOAuthToken($oauth_token);
        }

        // Add cookie to database
        $this->em->persist($token);
        $this->em->flush();

        // Add cookie to response
        $max_age      = (int) ($_ENV['APP_REMEMBER_ME_TIME'] ?? 31560000);
        $expires      = \gmdate('D, d M Y H:i:s T', time() + $max_age);
        return SetCookie::create('user_cookie_token', $cookie_token)
            ->withDomain($_ENV['APP_COOKIE_DOMAIN'] ?? APP_HOST_NAME)
            ->withPath($_ENV['APP_COOKIE_PATH'] ?? "/")
            ->withExpires($expires)
            ->withMaxAge($max_age)
            ->withSecure((bool)$_ENV['APP_COOKIE_TLS_ONLY'])
            ->withHttpOnly((bool)$_ENV['APP_COOKIE_HTTP_ONLY'])
            ->withSameSite(SameSite::fromString($_ENV['APP_COOKIE_SAMESITE'])
        );
    }

    /**
     * Create an email verification.
     *
     * Returns false on failure and the email ID on success.
     *
     * @return integer|false Email ID on success or false on failure.
     */
    public function createEmailVerification(): int|false
    {
        // Make sure user is logged in
        if(!$this->isLoggedIn()){
            throw new \Exception("need to be logged in to check if we need email verification");
        }

        // Create the verification in the DB
        $verification = new UserEmailVerification();
        $verification->setUser($this->user);
        $this->em->persist($verification);
        $this->em->flush();

        return $this->createEmailVerificationMail($verification);
    }

    public function createEmailVerificationMail(UserEmailVerification $verification): int|false
    {
        // Create a mail
        // TODO: add template functionality
        $email_body = "Please verify your email address for KeeperFX using the following link: " . PHP_EOL;
        $email_body .= APP_ROOT_URL . '/verify-email/' . $this->user->getId() . '/' . $verification->getToken();

        // Create the mail in the mail queue and return the mail ID or FALSE on failure
        return $this->mailer->createMailForUser(
            $this->user,
            'Verify your email address',
            $email_body,
            null,
            true,
        );
    }

    public function hasPendingEmailVerification(): bool
    {
        if(!$this->isLoggedIn()){
            throw new \Exception("need to be logged in to check if we need email verification");
        }

        $verification = $this->user->getEmailVerification();
        return $verification !== null;
    }

    public function removeExistingEmailVerification(): void
    {
        // User needs to be logged in
        if(!$this->isLoggedIn()){
            throw new \Exception("need to be logged in to check if we need to remove email verification");
        }

        // Check if there is a verification pending
        $verification = $this->user->getEmailVerification();
        if(!$verification){
            return;
        }

        $this->em->remove($verification);
        $this->em->flush();
    }

    public function isLoggedIn(): bool
    {
        return !is_null($this->user);
    }

    /**
     * Get the value of user
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser(?User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function setCurrentLoggedInUser(User $user)
    {
        $this->setUser($user);
        $this->session['uid'] = $user->getId();

        // Make sure theme is instantly loaded
        $this->theme->setTheme($user->getTheme());
    }

    public function clearCurrentLoggedInUser()
    {
        $this->user = null;
        $this->session['uid'] = null;
    }

    public function logIp(string $ip): void
    {
        // Check if user is logged in
        if($this->isLoggedIn() === false){
            return;
        }

        // Make sure IP is valid
        if(IpHelper::isValidIp($ip) === false){
            return;
        }

        // Check if this IP is already logged
        $existing_ip_log = $this->em->getRepository(UserIpLog::class)->findOneBy(['user' => $this->user, 'ip' => $ip]);
        if($existing_ip_log){

            // Update the last seen timestamp
            $existing_ip_log->setLastSeenTimestamp(new \DateTime("now"));
            $this->em->flush();
            return;
        }

        // Make a new IP log
        $ip_log = new UserIpLog();
        $ip_log->setUser($this->user);
        $ip_log->setIp($ip);

        // Add IP log to DB
        $this->em->persist($ip_log);
        $this->em->flush();
    }

    public function updateTheme(string $theme_id = 'default'): bool
    {
        $theme_id = \strtolower($theme_id);

        // Make sure we are logged in
        if($this->isLoggedIn() === false){
            throw new \Exception('user needs to be logged in before we can set their theme');
        }

        // Try and update the theme
        if($this->theme->setTheme($theme_id) === false){
            return false;
        }

        // Check if user needs updating
        if($this->user->getTheme() !== $theme_id){
            // Update user
            $this->user->setTheme($theme_id);
            $this->em->flush();
        }

         return true;
    }

    public function getTheme(): array
    {
        return $this->theme->getCurrentTheme();
    }
}
