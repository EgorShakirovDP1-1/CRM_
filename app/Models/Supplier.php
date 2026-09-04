<?php

namespace App\Models;

class Supplier extends TenantModel
{
    protected function casts(): array
    {
        return ['address_json' => 'array'];
    }
}
