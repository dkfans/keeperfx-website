<?php

namespace App\Twig\Extension;

use App\Account;

/**
 * Account Twig Extension.
 */
class AccountTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    public function __construct(
        private Account $account,
    ) {}

    public function getName(): string
    {
        return 'account_extension';
    }

    public function getGlobals(): array
    {
        if(!$this->account->isLoggedIn()){
            return [
                'account' => null,
            ];
        }

        return [
            'account' => [
                'id'                => $this->account->getUser()->getId(),
                'username'          => $this->account->getUser()->getUsername(),
                'role'              => $this->account->getUser()->getRole()->value,
                'avatar'            => $this->account->getUser()->getAvatar(),
                'avatar_small'      => $this->account->getUser()->getAvatarSmall(),
                'email'             => $this->account->getUser()->getEmail(),
                'is_email_verified' => $this->account->getUser()->isEmailVerified(),
                'theme'             => $this->account->getTheme(),
            ],
        ];
    }
}
