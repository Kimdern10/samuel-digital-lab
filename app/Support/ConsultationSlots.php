<?php

namespace App\Support;

use App\Models\Consultation;
use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Works out which consultation times are free, from the owner's hours in the admin.
 * Times are shown in Nigerian time and stored in UTC.
 */
class ConsultationSlots
{
    public const string TIMEZONE = 'Africa/Lagos';

    /**
     * Whether visitors can book calls at all.
     */
    public static function enabled(): bool
    {
        return Setting::get('consult_enabled') === '1';
    }

    /**
     * How long each call lasts, in minutes.
     */
    public static function minutes(): int
    {
        return max(5, (int) Setting::get('consult_minutes'));
    }

    /**
     * Every free time, grouped by day, e.g. ['2026-11-03' => ['09:00', '09:30']].
     *
     * @return array<string, list<string>>
     */
    public static function available(?CarbonImmutable $now = null): array
    {
        if (! self::enabled()) {
            return [];
        }

        $now = ($now ?? CarbonImmutable::now())->setTimezone(self::TIMEZONE);
        $earliest = $now->addHours(max(0, (int) Setting::get('consult_notice_hours')));
        $daysAhead = max(1, min(60, (int) Setting::get('consult_days_ahead')));
        $workDays = array_map('intval', Setting::list('consult_days'));
        $interval = max(5, (int) Setting::get('consult_interval'));
        $minutes = self::minutes();

        [$startHour, $startMinute] = self::parseTime(Setting::get('consult_start'), [9, 0]);
        [$endHour, $endMinute] = self::parseTime(Setting::get('consult_end'), [17, 0]);

        $lastDay = $now->startOfDay()->addDays($daysAhead);

        $taken = Consultation::query()->booked()
            ->whereBetween('starts_at', [$now->startOfDay()->utc(), $lastDay->endOfDay()->utc()])
            ->pluck('starts_at')
            ->map(fn ($start): int => CarbonImmutable::parse($start)->getTimestamp())
            ->all();

        $days = [];

        for ($day = $now->startOfDay(); $day->lessThanOrEqualTo($lastDay); $day = $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, $workDays, true)) {
                continue;
            }

            $closing = $day->setTime($endHour, $endMinute);
            $slots = [];

            for ($slot = $day->setTime($startHour, $startMinute); $slot->addMinutes($minutes)->lessThanOrEqualTo($closing); $slot = $slot->addMinutes($interval)) {
                if ($slot->lessThan($earliest)) {
                    continue;
                }

                $clashes = array_filter($taken, fn (int $start): bool => abs($start - $slot->getTimestamp()) < $minutes * 60);

                if ($clashes === []) {
                    $slots[] = $slot->format('H:i');
                }
            }

            if ($slots !== []) {
                $days[$day->format('Y-m-d')] = $slots;
            }
        }

        return $days;
    }

    /**
     * Whether a chosen day and time is still free.
     */
    public static function isAvailable(string $date, string $time): bool
    {
        return in_array($time, self::available()[$date] ?? [], true);
    }

    /**
     * Turn a Nigerian day and time into the UTC moment stored in the database.
     */
    public static function toUtc(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$time}", self::TIMEZONE)->utc();
    }

    /**
     * Read "HH:MM" into hours and minutes.
     *
     * @param  array{int, int}  $fallback
     * @return array{int, int}
     */
    private static function parseTime(string $value, array $fallback): array
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches) !== 1) {
            return $fallback;
        }

        return [min(23, (int) $matches[1]), min(59, (int) $matches[2])];
    }
}
