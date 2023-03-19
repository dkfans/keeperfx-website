<?php

namespace App\Twig\Extension;

use App\Account;

/**
 * Account Twig Extension.
 */
class AccountTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    protected Account $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

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
                'id'       => $this->account->getUser()->getId(),
                'username' => $this->account->getUser()->getUsername(),
                'role'     => $this->account->getUser()->getRole()->value,
                'avatar'   => $this->account->getUser()->getAvatar(),
            ]
        ];
    }
}
