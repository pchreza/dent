<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class JalaliDate
{
    private const WEEKDAY_NAMES = [
        6 => 'شنبه',
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
    ];

    public static function format(DateTimeInterface $date): string
    {
        $gy = (int) $date->format('Y');
        $gm = (int) $date->format('n');
        $gd = (int) $date->format('j');
        $gDaysInMonth = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            - 80
            + $gd;

        for ($i = 1; $i < $gm; $i++) {
            $days += $gDaysInMonth[$i];
        }

        if ($gm > 2 && (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0)) {
            $days++;
        }

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }

    public static function parse(string $value, ?DateTimeZone $timezone = null): CarbonImmutable
    {
        if (! preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', trim($value), $matches)) {
            throw new InvalidArgumentException('تاریخ شمسی باید با فرمت YYYY/MM/DD باشد.');
        }

        $jy = (int) $matches[1];
        $jm = (int) $matches[2];
        $jd = (int) $matches[3];
        $timezone ??= new DateTimeZone(config('app.timezone', 'Asia/Tehran'));

        if ($jy < 1 || $jm < 1 || $jm > 12 || $jd < 1) {
            throw new InvalidArgumentException('تاریخ شمسی واردشده معتبر نیست.');
        }

        $monthDays = $jm <= 6 ? 31 : ($jm <= 11 ? 30 : (self::isLeapYear($jy) ? 30 : 29));
        if ($jd > $monthDays) {
            throw new InvalidArgumentException('روز واردشده برای این ماه شمسی معتبر نیست.');
        }

        $days = 0;
        if ($jy >= 1400) {
            for ($year = 1400; $year < $jy; $year++) {
                $days += self::isLeapYear($year) ? 366 : 365;
            }
        } else {
            for ($year = $jy; $year < 1400; $year++) {
                $days -= self::isLeapYear($year) ? 366 : 365;
            }
        }

        $days += $jm <= 6 ? ($jm - 1) * 31 : (186 + (($jm - 7) * 30));
        $days += $jd - 1;

        return CarbonImmutable::createSafe(2021, 3, 21, 0, 0, 0, $timezone)->addDays($days);
    }

    public static function weekdayName(DateTimeInterface $date): string
    {
        return self::WEEKDAY_NAMES[(int) $date->format('w')];
    }

    /** @return list<array{date: CarbonImmutable, jalali: string, weekday: string}> */
    public static function week(CarbonImmutable $date): array
    {
        $start = $date->startOfWeek(CarbonImmutable::SATURDAY);
        $days = [];

        for ($index = 0; $index < 7; $index++) {
            $day = $start->addDays($index);
            $days[] = [
                'date' => $day,
                'jalali' => self::format($day),
                'weekday' => self::weekdayName($day),
            ];
        }

        return $days;
    }

    private static function isLeapYear(int $year): bool
    {
        return in_array($year % 33, [1, 5, 9, 13, 17, 22, 26, 30], true);
    }
}
