<?php

namespace App;

use App\Entity\User;
use App\Entity\UserCookieToken;
use App\Entity\UserIpLog;
use App\Entity\UserOAuthToken;

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
    ) {
        if(isset($session['uid']) && !is_null($session['uid'])){
            $user = $em->getRepository(User::class)->find($session['uid']);
            if($user){
                $this->user = $user;
            }
        }
    }

    public function createRememberMeSetCookie(?UserOAuthToken $oauth_token = null): SetCookie
    {
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
            ->withDomain($_ENV['APP_COOKIE_DOMAIN'] ?? $_ENV['APP_ROOT_URL'] ?? null)
            ->withPath($_ENV['APP_COOKIE_PATH'] ?? "/")
            ->withExpires($expires)
            ->withMaxAge($max_age)
            ->withSecure((bool)$_ENV['APP_COOKIE_TLS_ONLY'])
            ->withHttpOnly((bool)$_ENV['APP_COOKIE_HTTP_ONLY'])
            ->withSameSite(SameSite::fromString($_ENV['APP_COOKIE_SAMESITE'])
        );
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
}
