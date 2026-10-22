<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        string $action,
        Model $auditable,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        $auditLog = new AuditLog([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $auditLog->user()->associate($user);
        $auditLog->auditable()->associate($auditable);
        $auditLog->save();

        return $auditLog;
    }
}
