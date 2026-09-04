<?php

namespace App\Models;

class RiskAssessment extends TenantModel
{
    protected function casts(): array
    {
        return ['score' => 'decimal:4', 'explanation_json' => 'array', 'assessed_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
