<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_valid_customers_from_csv_into_the_selected_organization(): void
    {
        $user = User::factory()->withOrganization()->create();
        $organizationId = $user->memberships()->firstOrFail()->organization_id;
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            "display_name,type,status,preferred_language,risk_level\nAcme,company,active,en,low\nИван,person,active,ru,medium\n",
        );

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Organization-Id' => $organizationId])
            ->post('/api/v1/customers/import', ['file' => $file])
            ->assertCreated()
            ->assertJsonPath('data.imported', 2);

        $this->assertDatabaseHas('customers', ['organization_id' => $organizationId, 'display_name' => 'Acme']);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organizationId, 'action' => 'customers.imported']);
    }

    public function test_customer_filters_are_applied_without_leaking_other_tenants(): void
    {
        $user = User::factory()->withOrganization()->create();
        $other = User::factory()->withOrganization()->create();
        $organizationId = $user->memberships()->firstOrFail()->organization_id;
        Customer::create(['organization_id' => $organizationId, 'type' => 'company', 'display_name' => 'Active mine', 'status' => 'active']);
        Customer::create(['organization_id' => $organizationId, 'type' => 'company', 'display_name' => 'Archived mine', 'status' => 'archived']);
        Customer::create(['organization_id' => $other->memberships()->firstOrFail()->organization_id, 'type' => 'company', 'display_name' => 'Active foreign', 'status' => 'active']);

        $this->actingAs($user)
            ->withHeader('X-Organization-Id', $organizationId)
            ->getJson('/api/v1/customers?status=active&type=company')
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'Active mine'])
            ->assertJsonMissing(['display_name' => 'Archived mine'])
            ->assertJsonMissing(['display_name' => 'Active foreign']);
    }

    public function test_csv_export_contains_only_selected_organization_customers(): void
    {
        $user = User::factory()->withOrganization()->create();
        $other = User::factory()->withOrganization()->create();
        $organizationId = $user->memberships()->firstOrFail()->organization_id;
        Customer::create(['organization_id' => $organizationId, 'type' => 'company', 'display_name' => 'Export mine']);
        Customer::create(['organization_id' => $other->memberships()->firstOrFail()->organization_id, 'type' => 'company', 'display_name' => 'Export foreign']);

        $response = $this->actingAs($user)
            ->withHeader('X-Organization-Id', $organizationId)
            ->get('/api/v1/customers/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertIsString($content);
        $this->assertStringContainsString('Export mine', $content);
        $this->assertStringNotContainsString('Export foreign', $content);
    }
}
