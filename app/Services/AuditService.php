<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Create an activity log entry.
     *
     * @param int|null $userId
     * @param string $action
     * @param string $modelType
     * @param int|null $modelId
     * @param string|null $description
     * @param array|null $changes
     * @return ActivityLog
     */
    public function log(?int $userId, string $action, string $modelType, ?int $modelId = null, ?string $description = null, ?array $changes = null): ActivityLog
    {
        $payload = [
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ];

        return ActivityLog::create($payload);
    }

    /**
     * Compute changes array: only include whitelisted fields to avoid leaking sensitive data.
     * $before and $after are arrays of attributes.
     */
    public function computeDiff(array $before, array $after, array $whitelist = []): array
    {
        $fields = $whitelist ?: array_keys($after);
        $changes = [];

        foreach ($fields as $field) {
            $old = Arr::get($before, $field);
            $new = Arr::get($after, $field);
            if ($old !== $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }
}
