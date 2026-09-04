<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Appointment;
use App\Services\AuditService;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends TenantCrudController
{
    protected string $modelClass = Appointment::class;

    protected array $filterable = ['status', 'source', 'branch_id', 'employee_id', 'customer_id'];

    public function __construct(AuditService $audit, private readonly BookingService $booking)
    {
        parent::__construct($audit);
    }

    protected function rules(?Model $record = null): array
    {
        return [
            'customer_id' => ['required', 'uuid', $this->tenantExists('customers')],
            'branch_id' => ['required', 'uuid', $this->tenantExists('branches')],
            'employee_id' => ['nullable', 'uuid', $this->tenantExists('employees')],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'source' => ['sometimes', Rule::in(['staff', 'web', 'bot', 'api'])],
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
            'resource_ids' => ['sometimes', 'array', 'max:20'],
            'resource_ids.*' => ['uuid', $this->tenantExists('resources')],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $appointment = $this->booking->book($request->validate($this->rules()));

        return response()->json(['data' => $appointment], 201);
    }
}
