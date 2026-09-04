<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_employee_appointment_is_rejected(): void
    {
        $user = User::factory()->withOrganization()->create();
        $membership = $user->memberships()->with('employee')->firstOrFail();
        $organizationId = $membership->organization_id;
        $employee = $membership->employee;
        $customer = Customer::create(['organization_id' => $organizationId, 'type' => 'person', 'display_name' => 'Client']);
        $starts = now()->addDay()->startOfHour();
        Appointment::create([
            'organization_id' => $organizationId, 'customer_id' => $customer->id, 'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id, 'starts_at' => $starts, 'ends_at' => $starts->copy()->addHour(), 'status' => 'confirmed', 'source' => 'staff',
        ]);

        $this->actingAs($user)->withHeader('X-Organization-Id', $organizationId)
            ->postJson('/api/v1/appointments', [
                'customer_id' => $customer->id, 'branch_id' => $employee->branch_id, 'employee_id' => $employee->id,
                'starts_at' => $starts->copy()->addMinutes(30)->toIso8601String(), 'ends_at' => $starts->copy()->addMinutes(90)->toIso8601String(),
                'status' => 'confirmed', 'source' => 'api',
            ])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
    }

    public function test_fully_reserved_room_is_rejected(): void
    {
        $user = User::factory()->withOrganization()->create();
        $membership = $user->memberships()->with('employee')->firstOrFail();
        $organizationId = $membership->organization_id;
        $employee = $membership->employee;
        $customer = Customer::create(['organization_id' => $organizationId, 'type' => 'person', 'display_name' => 'Client']);
        $locationId = (string) Str::uuid();
        $resourceId = (string) Str::uuid();
        DB::table('locations')->insert([
            'id' => $locationId,
            'organization_id' => $organizationId,
            'branch_id' => $employee->branch_id,
            'name' => 'Room A',
            'timezone' => 'Europe/Riga',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('resources')->insert([
            'id' => $resourceId,
            'organization_id' => $organizationId,
            'location_id' => $locationId,
            'type' => 'room',
            'name' => 'Room A',
            'capacity' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $starts = now()->addDay()->startOfHour();
        $existing = Appointment::create([
            'organization_id' => $organizationId,
            'customer_id' => $customer->id,
            'branch_id' => $employee->branch_id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => 'confirmed',
            'source' => 'staff',
        ]);
        DB::table('appointment_resources')->insert([
            'appointment_id' => $existing->id,
            'resource_id' => $resourceId,
            'reserved_from' => $starts,
            'reserved_to' => $starts->copy()->addHour(),
        ]);

        $this->actingAs($user)->withHeader('X-Organization-Id', $organizationId)
            ->postJson('/api/v1/appointments', [
                'customer_id' => $customer->id,
                'branch_id' => $employee->branch_id,
                'resource_ids' => [$resourceId],
                'starts_at' => $starts->copy()->addMinutes(15)->toIso8601String(),
                'ends_at' => $starts->copy()->addMinutes(45)->toIso8601String(),
                'status' => 'confirmed',
                'source' => 'api',
            ])->assertUnprocessable()->assertJsonValidationErrors('resource_ids');
    }
}
