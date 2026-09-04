<?php

namespace App\Models;

class Contact extends TenantModel
{
    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }
}
