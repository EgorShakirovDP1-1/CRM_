<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /** @param array<string, mixed> $metadata */
    public function record(string $action, Model|string $entity, array $metadata = [], ?Request $request = null): AuditLog
    {
        return AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entity instanceof Model ? $entity->getMorphClass() : $entity,
            'entity_id' => $entity instanceof Model ? $entity->getKey() : null,
            'metadata_json' => $this->redact($metadata),
            'ip_address' => $request?->ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function redact(array $metadata): array
    {
        foreach (['password', 'token', 'secret', 'credential', 'authorization'] as $key) {
            unset($metadata[$key]);
        }

        return $metadata;
    }
}
