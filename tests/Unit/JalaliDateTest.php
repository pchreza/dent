<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class JalaliDateTest extends TestCase
{
    public function test_it_converts_nowruz_to_first_day_of_jalali_year(): void
    {
        self::assertSame('1404/01/01', JalaliDate::format(CarbonImmutable::create(2025, 3, 21, 12, 0, 0, 'Asia/Tehran')));
    }

    public function test_it_builds_a_saturday_to_friday_week(): void
    {
        $week = JalaliDate::week(CarbonImmutable::create(2025, 8, 12, 12, 0, 0, 'Asia/Tehran'));

        self::assertCount(7, $week);
        self::assertSame('شنبه', $week[0]['weekday']);
        self::assertSame('جمعه', $week[6]['weekday']);
        self::assertSame('1404/05/18', $week[0]['jalali']);
        self::assertSame('1404/05/24', $week[6]['jalali']);
    }
}
