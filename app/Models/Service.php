<?php

namespace App\Models;

class Service extends TenantModel
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
