<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BusinessDayPolicy
{
    public function dueAt(CarbonImmutable $from, int $businessDays, string $calendarId): CarbonImmutable
    {
        $weekendJson = DB::table('business_calendars')->where('id', $calendarId)->value('weekend_days');
        $decodedWeekend = json_decode(is_string($weekendJson) ? $weekendJson : '[0,6]', true, flags: JSON_THROW_ON_ERROR);
        $weekend = is_array($decodedWeekend)
            ? array_values(array_map(static fn (mixed $day): int => (int) $day, $decodedWeekend))
            : [0, 6];
        $holidays = DB::table('business_holidays')
            ->where('business_calendar_id', $calendarId)
            ->pluck('is_working_day_override', 'date');

        $candidate = $from;
        $remaining = $businessDays;
        while ($remaining > 0) {
            $candidate = $candidate->addDay();
            $date = $candidate->toDateString();
            $isHoliday = $holidays->has($date) && ! $holidays->get($date);
            $isWorkingOverride = $holidays->get($date) === 1 || $holidays->get($date) === true;
            if ((! in_array($candidate->dayOfWeek, $weekend, true) && ! $isHoliday) || $isWorkingOverride) {
                $remaining--;
            }
        }

        return $candidate;
    }
}
