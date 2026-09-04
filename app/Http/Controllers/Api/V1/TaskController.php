<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CrmTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TaskController extends TenantCrudController
{
    protected string $modelClass = CrmTask::class;

    protected array $searchable = ['title'];

    protected array $filterable = ['status', 'priority', 'assignee_employee_id', 'customer_id', 'deal_id'];

    protected function rules(?Model $record = null): array
    {
        return [
            'assignee_employee_id' => ['required', 'uuid', $this->tenantExists('employees')],
            'customer_id' => ['nullable', 'uuid', $this->tenantExists('customers')],
            'deal_id' => ['nullable', 'uuid', $this->tenantExists('deals')],
            'type' => ['sometimes', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'completed', 'cancelled'])],
        ];
    }
}
