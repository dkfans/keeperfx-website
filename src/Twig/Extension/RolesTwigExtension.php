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
        $roles = [];

        foreach(UserRole::cases() as $case){
            $roles[\strtolower($case->name)] = $case->value;
        }

        return [
            'roles' => $roles
        ];
    }
}
