<?php

namespace App;

use App\Entity\User;
use Compwright\PhpSession\Session;
use Doctrine\ORM\EntityManager;

class Account {

    private User|null $user = null;

    public function __construct(Session $session, EntityManager $em)
    {
        if(isset($session['uid']) && !is_null($session['uid'])){
            $user = $em->getRepository(User::class)->find($session['uid']);
            if($user){
                $this->user = $user;
            }
        }
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
