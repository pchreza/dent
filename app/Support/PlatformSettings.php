<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class PlatformSettings
{
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('platform_settings')) {
                return $default;
            }

            $rawValue = DB::table('platform_settings')->where('key', $key)->value('value');

            if ($rawValue === null) {
                return $default;
            }

            $decoded = json_decode((string) $rawValue, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
        } catch (Throwable) {
            return $default;
        }
    }
}
