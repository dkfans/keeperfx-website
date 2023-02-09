<?php

namespace App\Twig\Extension;

use App\Enum\UserRole;

/**
 * Account Twig Extension.
 */
class RolesTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    public function getName(): string
    {
        return 'roles_extension';
    }

    public function getGlobals(): array
    {
        return [
            'roles' => [
                'user'               => UserRole::User->value,
                'workshop_moderator' => UserRole::WorkshopModerator->value,
                'developer'          => UserRole::Developer->value,
                'admin'              => UserRole::Admin->value,
            ]
        ];
    }
}
