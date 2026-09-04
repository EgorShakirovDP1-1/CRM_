<?php

namespace App\Models;

class Invoice extends TenantModel
{
    protected function casts(): array
    {
        return ['issued_on' => 'date', 'due_on' => 'date', 'total_amount' => 'decimal:2'];
    }
}
