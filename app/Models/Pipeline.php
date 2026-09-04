<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends TenantModel
{
    /** @return HasMany<PipelineStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }
}
