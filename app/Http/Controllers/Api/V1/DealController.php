<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Deal;
use App\Models\Pipeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DealController extends TenantCrudController
{
    protected string $modelClass = Deal::class;

    protected array $searchable = ['title'];

    protected array $filterable = ['status', 'customer_id', 'pipeline_id', 'stage_id', 'owner_employee_id'];

    protected function rules(?Model $record = null): array
    {
        return [
            'customer_id' => ['required', 'uuid', $this->tenantExists('customers')],
            'pipeline_id' => ['required', 'uuid', $this->tenantExists('pipelines')],
            'stage_id' => ['required', 'uuid', Rule::exists('pipeline_stages', 'id')->whereIn('pipeline_id', Pipeline::pluck('id'))],
            'owner_employee_id' => ['required', 'uuid', $this->tenantExists('employees')],
            'title' => ['required', 'string', 'max:255'],
            'expected_amount' => ['required', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['open', 'won', 'lost'])],
        ];
    }
}
