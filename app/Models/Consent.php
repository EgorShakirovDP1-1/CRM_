<?php

namespace App\Models;

class Consent extends TenantModel
{
    protected function casts(): array
    {
        return ['scope_json' => 'array', 'granted_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }
}
