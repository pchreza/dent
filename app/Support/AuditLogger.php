<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Http\Request;

final class AuditLogger
{
    public function record(
        string $action,
        ?int $tenantId = null,
        ?int $actorId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?Request $request = null,
    ): AuditEvent {
        $request ??= request();

        return AuditEvent::query()->create([
            'tenant_id' => $tenantId,
            'actor_id' => $actorId ?? $request->user()?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
