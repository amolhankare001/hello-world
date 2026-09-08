<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     */
    public function record(
        User $user,
        string $action,
        Request $request,
        ?Model $auditable = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata,
        ]);
    }
}
