<?php

namespace App\Support;

use App\Models\OrganizationUser;
use Illuminate\Support\Collection;
use LogicException;

class TenantContext
{
    private ?OrganizationUser $membership = null;

    public function set(OrganizationUser $membership): void
    {
        $this->membership = $membership->loadMissing('organization', 'roles.permissions', 'employee');
    }

    public function clear(): void
    {
        $this->membership = null;
    }

    public function hasOrganization(): bool
    {
        return $this->membership !== null;
    }

    public function organizationId(): string
    {
        if ($this->membership === null) {
            throw new LogicException('No organization has been resolved for this request.');
        }

        return $this->membership->organization_id;
    }

    public function membership(): OrganizationUser
    {
        return $this->membership
            ?? throw new LogicException('No organization has been resolved for this request.');
    }

    /** @return Collection<int, string> */
    public function roleCodes(): Collection
    {
        return $this->membership()->roles->pluck('code');
    }

    /** @return Collection<int, string> */
    public function permissions(): Collection
    {
        return $this->membership()->roles
            ->flatMap->permissions
            ->where('pivot.allowed', true)
            ->pluck('code')
            ->unique()
            ->values();
    }

    public function allows(string $permission): bool
    {
        return $this->roleCodes()->contains('owner') || $this->permissions()->contains($permission);
    }
}
