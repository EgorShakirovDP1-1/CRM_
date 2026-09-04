<?php

namespace App\Models;

class CatalogItem extends TenantModel
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'tax_rate_pct' => 'decimal:2'];
    }
}
