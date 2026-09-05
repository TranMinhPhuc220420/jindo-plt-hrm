<?php

namespace App\Services\Shift;

final class ShiftSchedule
{
    /**
     * @param  array<mixed>|null  $weekdays
     * @return list<int>
     */
    public static function weekdaysOrAll(?array $weekdays): array
    {
        if ($weekdays === null || $weekdays === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $normalized = array_values(array_unique(array_map(
            static fn (mixed $day): int => (int) $day,
            $weekdays,
        )));
        sort($normalized);

        return $normalized;
    }

    /**
     * @param  array<mixed>|null  $weekdays
     */
    public static function appliesOnWeekday(?array $weekdays, int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, self::weekdaysOrAll($weekdays), true);
    }

    /**
     * @param  array<mixed>|null  $a
     * @param  array<mixed>|null  $b
     */
    public static function weekdaysIntersect(?array $a, ?array $b): bool
    {
        return array_intersect(self::weekdaysOrAll($a), self::weekdaysOrAll($b)) !== [];
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function windowMinutes(string $start, string $end): array
    {
        $from = self::toMinutes($start);
        $to = self::toMinutes($end);

        if ($to <= $from) {
            $to += 24 * 60;
        }

        return [$from, $to];
    }

    public static function timesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        [$aFrom, $aTo] = self::windowMinutes($aStart, $aEnd);
        [$bFrom, $bTo] = self::windowMinutes($bStart, $bEnd);

        return $aFrom < $bTo && $bFrom < $aTo;
    }

    public static function formatTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        $raw = (string) $time;

        if (preg_match('/^\d{2}:\d{2}/', $raw) === 1) {
            return substr($raw, 0, 5);
        }

        return $raw;
    }

    /**
     * @param  list<int>|mixed  $weekdays
     * @return list<int>|null
     */
    public static function normalizeStoredWeekdays(mixed $weekdays): ?array
    {
        if ($weekdays === null) {
            return null;
        }

        if (! is_array($weekdays)) {
            return null;
        }

        $normalized = self::weekdaysOrAll($weekdays);

        if ($normalized === [0, 1, 2, 3, 4, 5, 6] && $weekdays === []) {
            return null;
        }

        return $normalized;
    }

    private static function toMinutes(string $time): int
    {
        $parts = explode(':', substr($time, 0, 5));
        $hours = (int) $parts[0];
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }
}
