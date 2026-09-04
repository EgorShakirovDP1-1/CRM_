<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_only_returns_records_from_selected_organization(): void
    {
        $first = User::factory()->withOrganization()->create();
        $second = User::factory()->withOrganization()->create();
        $firstOrganization = $first->memberships()->firstOrFail()->organization_id;
        $secondOrganization = $second->memberships()->firstOrFail()->organization_id;
        $mine = Customer::create(['organization_id' => $firstOrganization, 'type' => 'company', 'display_name' => 'Mine']);
        $theirs = Customer::create(['organization_id' => $secondOrganization, 'type' => 'company', 'display_name' => 'Theirs']);

        $this->actingAs($first)
            ->withHeader('X-Organization-Id', $firstOrganization)
            ->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonFragment(['id' => $mine->id, 'display_name' => 'Mine'])
            ->assertJsonMissing(['id' => $theirs->id, 'display_name' => 'Theirs']);
    }

    public function test_cross_tenant_record_is_not_addressable(): void
    {
        $first = User::factory()->withOrganization()->create();
        $second = User::factory()->withOrganization()->create();
        $foreignOrganization = $second->memberships()->firstOrFail()->organization_id;
        $foreign = Customer::create(['organization_id' => $foreignOrganization, 'type' => 'person', 'display_name' => 'Hidden']);

        $this->actingAs($first)
            ->withHeader('X-Organization-Id', $first->memberships()->firstOrFail()->organization_id)
            ->getJson('/api/v1/customers/'.$foreign->id)
            ->assertNotFound();
    }

    public function test_web_archive_cannot_address_a_customer_from_another_organization(): void
    {
        $first = User::factory()->withOrganization()->create();
        $second = User::factory()->withOrganization()->create();
        $foreign = Customer::create([
            'organization_id' => $second->memberships()->firstOrFail()->organization_id,
            'type' => 'person',
            'display_name' => 'Cannot archive me',
        ]);

        $this->actingAs($first)
            ->delete('/crm/customers/'.$foreign->id)
            ->assertNotFound();

        $this->assertDatabaseHas('customers', ['id' => $foreign->id, 'status' => 'active']);
    }
}
