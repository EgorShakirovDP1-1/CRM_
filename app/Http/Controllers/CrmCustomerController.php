<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmCustomerController extends Controller
{
    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $customer = Customer::create($request->validate([
            'type' => ['required', Rule::in(['person', 'company', 'sole_trader'])],
            'display_name' => ['required', 'string', 'max:255'],
            'preferred_language' => ['required', Rule::in(['ru', 'en'])],
        ]));
        $audit->record('customer.created', $customer, [], $request);

        return back()->with('success', __('crm.customer_created'));
    }

    public function destroy(string $customer, Request $request, AuditService $audit): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customer);
        $audit->record('customer.archived', $customer, [], $request);
        $customer->update(['status' => 'archived']);

        return back()->with('success', __('crm.customer_archived'));
    }
}
