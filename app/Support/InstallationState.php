<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class InstallationState
{
    private const LOCK_FILE = 'installed.lock';

    public function isInstalled(): bool
    {
        return Storage::disk('local')->exists(self::LOCK_FILE);
    }

    public function markInstalled(): void
    {
        Storage::disk('local')->put(self::LOCK_FILE, now()->toIso8601String());
    }

    public function lockPath(): string
    {
        return Storage::disk('local')->path(self::LOCK_FILE);
    }
}
