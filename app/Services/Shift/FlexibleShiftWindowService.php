<?php

namespace App\Services\Shift;

use App\Exceptions\DomainException;
use App\Models\Shift;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FlexibleShiftWindowService
{
    /**
     * Reuse an active shift with the same clocks, or create a generated FLEX-* template.
     */
    public function resolve(int $companyId, string $startTime, string $endTime): Shift
    {
        $start = ShiftSchedule::formatTime($startTime);
        $end = ShiftSchedule::formatTime($endTime);
        $this->assertWindow($start, $end);

        $match = Shift::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('is_generated')
            ->orderBy('id')
            ->get()
            ->first(function (Shift $shift) use ($start, $end): bool {
                return ShiftSchedule::formatTime($shift->start_time) === $start
                    && ShiftSchedule::formatTime($shift->end_time) === $end;
            });

        if ($match !== null) {
            return $match;
        }

        return $this->createGenerated($companyId, $start, $end);
    }

    private function createGenerated(int $companyId, string $start, string $end): Shift
    {
        $code = sprintf(
            'FLEX-%s-%s',
            str_replace(':', '', $start),
            str_replace(':', '', $end),
        );
        $isNight = $end < $start;

        $existing = Shift::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->fill([
                'name' => $start.'–'.$end,
                'start_time' => $start.':00',
                'end_time' => $end.':00',
                'kind' => 'flexible',
                'is_night' => $isNight,
                'is_flexible' => true,
                'is_active' => true,
                'is_generated' => true,
                'break_minutes' => 0,
            ]);
            $existing->save();

            return $existing;
        }

        try {
            return DB::transaction(function () use ($companyId, $code, $start, $end, $isNight): Shift {
                return Shift::query()->create([
                    'company_id' => $companyId,
                    'name' => $start.'–'.$end,
                    'code' => $code,
                    'start_time' => $start.':00',
                    'end_time' => $end.':00',
                    'break_minutes' => 0,
                    'kind' => 'flexible',
                    'is_night' => $isNight,
                    'is_flexible' => true,
                    'is_active' => true,
                    'is_generated' => true,
                ]);
            });
        } catch (QueryException) {
            $retry = Shift::query()
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->first();

            if ($retry !== null) {
                return $retry;
            }

            throw new DomainException(
                message: 'Shift code already exists for this company.',
                errorCode: 'SHIFT_CODE_DUPLICATE',
                status: 422,
            );
        }
    }

    private function assertWindow(string $start, string $end): void
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
            throw new DomainException(
                message: 'Shift start and end time are required.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }

        if ($start === $end) {
            throw new DomainException(
                message: 'Shift start and end time must differ.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }
    }
}
