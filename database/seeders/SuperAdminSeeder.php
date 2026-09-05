<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        );

        $organization = Organization::where('slug', 'nexus-demo')->firstOrFail();
        $employee = Employee::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'job_title' => 'Super administrator',
                'status' => 'active',
            ],
        );
        $membership = OrganizationUser::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'employee_id' => $employee->id,
                'status' => 'active',
                'joined_at' => now(),
            ],
        );
        $role = Role::where('code', 'superadmin')->firstOrFail();

        if (! $membership->roles()->where('roles.id', $role->id)->exists()) {
            $membership->roles()->attach($role->id, [
                'id' => (string) Str::uuid(),
                'assigned_by_user_id' => $user->id,
                'assigned_at' => now(),
            ]);
        }
    }
}
