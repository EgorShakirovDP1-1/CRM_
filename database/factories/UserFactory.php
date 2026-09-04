<?php

namespace Database\Factories;

use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            /* @chisel-2fa */
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            /* @end-chisel-2fa */
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withOrganization(): static
    {
        return $this->afterCreating(function (User $user): void {
            $organization = Organization::create([
                'slug' => 'test-'.Str::lower(Str::random(10)), 'name' => 'Test Organization', 'timezone' => 'Europe/Riga',
            ]);
            $branch = Branch::create(['organization_id' => $organization->id, 'name' => 'Main', 'timezone' => 'Europe/Riga']);
            $employee = Employee::create([
                'organization_id' => $organization->id, 'branch_id' => $branch->id, 'user_id' => $user->id,
                'first_name' => $user->name, 'last_name' => '', 'job_title' => 'Owner',
            ]);
            $membership = OrganizationUser::create([
                'organization_id' => $organization->id, 'user_id' => $user->id, 'employee_id' => $employee->id,
            ]);
            $role = Role::firstOrCreate(
                ['code' => RoleCode::Owner->value],
                ['name_ru' => 'Владелец', 'name_en' => 'Owner', 'rank' => RoleCode::Owner->rank(), 'is_system' => true],
            );
            $membership->roles()->attach($role->id, [
                'id' => (string) Str::uuid(), 'assigned_by_user_id' => $user->id, 'assigned_at' => now(),
            ]);
        });
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        /* @chisel-2fa */
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
        /* @end-chisel-2fa */
    }
}
