<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class LeadController extends TenantCrudController
{
    protected string $modelClass = Lead::class;

    protected array $searchable = ['name', 'email', 'phone'];

    protected array $filterable = ['status', 'source_id', 'owner_employee_id'];

    protected function rules(?Model $record = null): array
    {
        return [
            'source_id' => ['nullable', 'uuid', $this->tenantExists('lead_sources')],
            'owner_employee_id' => ['nullable', 'uuid', $this->tenantExists('employees')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['sometimes', Rule::in(['new', 'qualified', 'converted', 'disqualified'])],
        ];
    }
}
