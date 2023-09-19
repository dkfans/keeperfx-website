<?php

namespace App;

use App\Enum\UserNotificationType;

use App\Entity\User;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Collection;

class AccountNotifier {

    private User $current_user;

    public function __construct(
        Account $account,
    ) {
        // Check if we are currently logged in
        if($account->isLoggedIn()){
            $this->current_user = $account->getUser();
        }
    }

    public function notify(User $user, UserNotificationType $type, string $message): bool
    {

        return false;
    }

    public function notifySelf(UserNotificationType $type, string $message): bool
    {
        if(!$this->current_user){
            throw new \Exception("can't notify self because there is no current user defined");
        }

        return $this->notify($this->current_user, $type, $message);
    }

}
