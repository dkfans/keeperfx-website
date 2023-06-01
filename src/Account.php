<?php

namespace App;

use App\Entity\User;
use App\Entity\UserCookieToken;
use App\Entity\UserOAuthToken;

use Doctrine\ORM\EntityManager;
use Compwright\PhpSession\Session;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\Modifier\SameSite;

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
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
