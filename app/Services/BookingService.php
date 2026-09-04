<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private readonly AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $start = CarbonImmutable::parse($data['starts_at']);
            $end = CarbonImmutable::parse($data['ends_at']);
            $resourceIds = $this->resourceIds($data['resource_ids'] ?? []);
            unset($data['resource_ids']);
            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages(['ends_at' => __('validation.after', ['attribute' => 'ends_at', 'date' => 'starts_at'])]);
            }

            if (! empty($data['employee_id'])) {
                DB::table('employees')->where('id', $data['employee_id'])->lockForUpdate()->first();
                $conflict = Appointment::query()
                    ->where('employee_id', $data['employee_id'])
                    ->whereNotIn('status', ['cancelled', 'no_show'])
                    ->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start)
                    ->lockForUpdate()
                    ->exists();
                if ($conflict) {
                    throw ValidationException::withMessages(['starts_at' => __('crm.slot_busy')]);
                }

                $publishedSchedule = DB::table('work_schedules')
                    ->where('employee_id', $data['employee_id'])
                    ->where('status', 'published')
                    ->whereDate('valid_from', '<=', $start->toDateString())
                    ->where(fn ($query) => $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $start->toDateString()))
                    ->exists();
                if ($publishedSchedule) {
                    $insideShift = DB::table('work_shifts')
                        ->join('work_schedules', 'work_schedules.id', '=', 'work_shifts.schedule_id')
                        ->where('work_schedules.employee_id', $data['employee_id'])
                        ->where('work_schedules.status', 'published')
                        ->where('work_shifts.status', '!=', 'cancelled')
                        ->where('work_shifts.starts_at', '<=', $start)
                        ->where('work_shifts.ends_at', '>=', $end)
                        ->exists();
                    if (! $insideShift) {
                        throw ValidationException::withMessages(['starts_at' => __('crm.outside_shift')]);
                    }
                }
            }

            foreach ($resourceIds as $resourceId) {
                $resource = DB::table('resources')
                    ->join('locations', 'locations.id', '=', 'resources.location_id')
                    ->where('resources.id', $resourceId)
                    ->where('resources.status', 'active')
                    ->select('resources.capacity', 'locations.branch_id')
                    ->lockForUpdate()
                    ->first();
                if ($resource === null || $resource->branch_id !== $data['branch_id']) {
                    throw ValidationException::withMessages(['resource_ids' => __('crm.resource_wrong_branch')]);
                }
                $reservations = DB::table('appointment_resources')
                    ->join('appointments', 'appointments.id', '=', 'appointment_resources.appointment_id')
                    ->where('appointment_resources.resource_id', $resourceId)
                    ->whereNotIn('appointments.status', ['cancelled', 'no_show'])
                    ->where('appointment_resources.reserved_from', '<', $end)
                    ->where('appointment_resources.reserved_to', '>', $start)
                    ->count();
                if ($reservations >= (int) $resource->capacity) {
                    throw ValidationException::withMessages(['resource_ids' => __('crm.resource_busy')]);
                }
            }

            $appointment = Appointment::create($data);
            foreach ($resourceIds as $resourceId) {
                DB::table('appointment_resources')->insert([
                    'appointment_id' => $appointment->id,
                    'resource_id' => $resourceId,
                    'reserved_from' => $start,
                    'reserved_to' => $end,
                ]);
            }
            $this->audit->record('appointment.created', $appointment, ['starts_at' => $start->toIso8601String()]);

            return $appointment;
        }, 3);
    }

    /** @return list<string> */
    private function resourceIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $id) {
            if (is_string($id)) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
