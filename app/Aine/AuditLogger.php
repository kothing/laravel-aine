<?php

namespace App\Aine;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight audit logger for admin operations.
 *
 * Records who did what, when, and from which IP. Failures are swallowed so
 * auditing never breaks the primary request flow.
 */
class AuditLogger
{
    /**
     * Write an audit log entry.
     *
     * @param string $action      e.g. create|update|delete|publish|import
     * @param string $entityType  e.g. content|collection|media|settings
     * @param int|null $entityId
     * @param string|null $label  Human-readable label (e.g. content title)
     * @param array|null $details Optional JSON-able details
     * @param int|null $projectId
     */
    public static function log(string $action, string $entityType, ?int $entityId = null, ?string $label = null, ?array $details = null, ?int $projectId = null): void
    {
        try {
            AuditLog::create([
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'entity_label' => $label !== null ? mb_substr($label, 0, 255) : null,
                // The model casts details to array (json_encode on write), so pass the raw array.
                'details' => $details,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit logging failed: ' . $e->getMessage());
        }
    }
}
