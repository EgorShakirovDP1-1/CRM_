<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends TenantModel
{
    protected function casts(): array
    {
        return ['expected_amount' => 'decimal:2', 'expected_close_date' => 'date'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<PipelineStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }
}
