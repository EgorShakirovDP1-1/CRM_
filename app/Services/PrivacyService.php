<?php

namespace App\Services;

use App\Models\Consent;
use App\Models\Customer;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class PrivacyService
{
    public function __construct(private readonly TenantContext $context, private readonly AuditService $audit) {}

    public function withdraw(Consent $consent): Consent
    {
        $consent->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);
        $this->audit->record('consent.withdrawn', $consent, ['purpose' => $consent->purpose]);

        return $consent;
    }

    /** @return array<string, mixed> */
    public function export(Customer $customer): array
    {
        $this->audit->record('privacy.subject-exported', $customer);

        return [
            'customer' => $customer->toArray(),
            'contacts' => $customer->contacts()->get()->toArray(),
            'consents' => DB::table('consents')->where('organization_id', $this->context->organizationId())->where('customer_id', $customer->id)->get()->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
