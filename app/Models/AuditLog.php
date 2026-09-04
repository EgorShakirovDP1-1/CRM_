<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    use BelongsToOrganization, HasUuids;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'actor_user_id', 'action', 'entity_type', 'entity_id', 'metadata_json', 'ip_address', 'created_at'];

    protected function casts(): array
    {
        return ['metadata_json' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $log) => $log->created_at ??= now());
        static::updating(function (): never {
            throw new LogicException('Audit records are append-only.');
        });
        static::deleting(function (): never {
            throw new LogicException('Audit records are append-only.');
        });
    }
}
