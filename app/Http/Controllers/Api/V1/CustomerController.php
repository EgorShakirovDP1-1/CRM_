<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CustomerController extends TenantCrudController
{
    protected string $modelClass = Customer::class;

    protected array $searchable = ['display_name'];

    protected array $filterable = ['type', 'status', 'preferred_language', 'risk_level'];

    protected function rules(?Model $record = null): array
    {
        return [
            'type' => ['required', Rule::in(['person', 'company', 'sole_trader'])],
            'display_name' => ['required', 'string', 'max:255'],
            'owner_employee_id' => ['nullable', 'uuid', $this->tenantExists('employees')],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'archived'])],
            'preferred_language' => ['sometimes', Rule::in(['ru', 'en'])],
            'risk_level' => ['nullable', Rule::in(['low', 'medium', 'high'])],
        ];
    }
}
