<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\NormalizeIdentifier;
use PHPUnit\Framework\TestCase;

final class NormalizeIdentifierTest extends TestCase
{
    public function test_it_converts_persian_digits_to_latin_digits(): void
    {
        self::assertSame('09123456789', NormalizeIdentifier::mobile('۰۹۱۲۳۴۵۶۷۸۹'));
    }

    public function test_it_removes_non_numeric_mobile_characters(): void
    {
        self::assertSame('+989123456789', NormalizeIdentifier::mobile('+۹۸۹۱۲۳۴۵۶۷۸۹'));
    }

    public function test_it_normalizes_usernames(): void
    {
        self::assertSame('superadmin', NormalizeIdentifier::username(' SuperAdmin '));
    }
}
