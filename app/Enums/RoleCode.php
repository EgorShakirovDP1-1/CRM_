<?php

namespace App\Enums;

enum RoleCode: string
{
    case Owner = 'owner';
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Worker = 'worker';
    case Accountant = 'accountant';
    case SupplyManager = 'supply_manager';

    public function rank(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Superadmin => 90,
            self::Admin => 80,
            self::Manager => 60,
            default => 40,
        };
    }
}
