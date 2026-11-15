<?php

namespace App\Modules\Admin\Services;

use Illuminate\Support\Facades\DB;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $entity,
        int|string|null $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?int $tenantId = null,
        ?array $metadata = null,
    ): void {
        DB::table('audit_logs')->insert([
            'actor_id' => auth()->id(),
            'tenant_id' => $tenantId,
            'entity' => $entity,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'action' => $action,
            'request_id' => request()->header('X-Request-Id'),
            'before' => $before !== null ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'after' => $after !== null ? json_encode($after, JSON_THROW_ON_ERROR) : null,
            'metadata' => $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            'logged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
