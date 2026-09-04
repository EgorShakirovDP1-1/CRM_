<?php

namespace App\Models;

class CustomerOrder extends TenantModel
{
    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2'];
    }
}
