<?php

namespace App\Models;

class PendingMessageCase extends TenantModel
{
    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'resolved_at' => 'datetime'];
    }
}
