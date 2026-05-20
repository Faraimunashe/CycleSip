<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        string $event,
        Model $subject,
        ?int $userId = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): void {
        ActivityLog::create([
            'event' => $event,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'user_id' => $userId,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
