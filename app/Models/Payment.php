<?php

namespace App\Models;

class Payment extends TenantModel
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }
}
