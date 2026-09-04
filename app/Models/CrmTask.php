<?php

namespace App\Models;

class CrmTask extends TenantModel
{
    protected $table = 'tasks';

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }
}
