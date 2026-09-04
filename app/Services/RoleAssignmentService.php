<?php

namespace App\Services;

use App\Models\OrganizationUser;
use App\Models\Role;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

class RoleAssignmentService
{
    public function __construct(private readonly TenantContext $context, private readonly AuditService $audit) {}

    public function assign(OrganizationUser $target, Role $role): void
    {
        if ($target->organization_id !== $this->context->organizationId()) {
            throw new AuthorizationException('Cross-tenant role assignment is forbidden.');
        }
        $actorRank = (int) $this->context->membership()->roles->max('rank');
        if ($actorRank <= $role->rank || $role->code === 'owner') {
            throw new AuthorizationException('Roles may only be assigned down the fixed hierarchy.');
        }
        if (! $target->roles()->where('roles.id', $role->id)->exists()) {
            $target->roles()->attach($role->id, [
                'id' => (string) Str::uuid(), 'assigned_by_user_id' => auth()->id(), 'assigned_at' => now(),
            ]);
        }
        $this->audit->record('role.assigned', $target, ['role' => $role->code]);
    }
}
