<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'organization_name' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => mb_strtolower($input['email']),
                'password' => $input['password'],
            ]);

            $organization = Organization::create([
                'name' => $input['organization_name'] ?? $input['name'].' Workspace',
                'slug' => Str::slug($input['organization_name'] ?? $input['name']).'-'.Str::lower(Str::random(6)),
                'timezone' => config('app.timezone'),
            ]);
            $branch = Branch::create([
                'organization_id' => $organization->id,
                'name' => __('crm.main_branch'),
                'timezone' => $organization->timezone,
            ]);
            $employee = Employee::create([
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'first_name' => $input['name'],
                'last_name' => '',
                'job_title' => __('crm.owner'),
            ]);
            $membership = OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'employee_id' => $employee->id,
            ]);
            $ownerRole = Role::firstOrCreate(
                ['code' => RoleCode::Owner->value],
                ['name_ru' => 'Владелец', 'name_en' => 'Owner', 'rank' => RoleCode::Owner->rank(), 'is_system' => true],
            );
            $membership->roles()->attach($ownerRole->id, [
                'id' => (string) Str::uuid(),
                'assigned_by_user_id' => $user->id,
                'assigned_at' => now(),
            ]);

            return $user;
        });
    }
}
