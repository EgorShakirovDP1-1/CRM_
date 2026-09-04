<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $context = app(TenantContext::class);
            if ($context->hasOrganization()) {
                $builder->where($builder->qualifyColumn('organization_id'), $context->organizationId());
            }
        });

        static::creating(function (self $model): void {
            if (! $model->getAttribute('organization_id')) {
                $context = app(TenantContext::class);
                if (! $context->hasOrganization()) {
                    throw new LogicException('organization_id is required outside an organization request.');
                }
                $model->setAttribute('organization_id', $context->organizationId());
            }
        });
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
