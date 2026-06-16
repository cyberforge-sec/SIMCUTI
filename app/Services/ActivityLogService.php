<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class ActivityLogService
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Log an activity
     */
    public function log(
        string $aksi,
        string $deskripsi,
        ?string $modelType = null,
        ?string $modelId = null,
        ?string $userId = null
    ): void {
        $data = [
            'user_id' => $userId ?? Session::get('user_id'),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($modelType) $data['model_type'] = $modelType;
        if ($modelId) $data['model_id'] = $modelId;

        // admin=true to bypass RLS for insert
        $this->supabase->insert('activity_logs', $data, true);
    }

    /**
     * Get recent logs (admin)
     */
    public function getRecentLogs(int $limit = 10): array
    {
        return $this->supabase->selectAdvanced('activity_logs', [
            'columns' => '*',
            'order' => 'created_at.desc',
            'limit' => $limit,
        ]);
    }

    /**
     * Get logs with filters
     */
    public function getLogs(array $options = []): array
    {
        $queryOptions = [
            'columns' => '*',
            'order' => 'created_at.desc',
            'limit' => $options['limit'] ?? 50,
            'offset' => $options['offset'] ?? 0,
            'filters' => [],
        ];

        if (isset($options['user_id'])) {
            $queryOptions['filters']['user_id'] = 'eq.' . $options['user_id'];
        }
        if (isset($options['aksi'])) {
            $queryOptions['filters']['aksi'] = 'eq.' . $options['aksi'];
        }
        if (isset($options['model_type'])) {
            $queryOptions['filters']['model_type'] = 'eq.' . $options['model_type'];
        }
        if (isset($options['date_from'])) {
            $queryOptions['filters']['created_at'] = 'gte.' . $options['date_from'] . 'T00:00:00';
        }

        $logs = $this->supabase->selectAdvanced('activity_logs', $queryOptions);

        // Apply date_to filter in PHP since PostgREST doesn't support
        // two filters on the same column in a single query string
        if (isset($options['date_to'])) {
            $dateTo = $options['date_to'] . 'T23:59:59';
            $logs = array_values(array_filter($logs, function ($log) use ($dateTo) {
                return ($log['created_at'] ?? '') <= $dateTo;
            }));
        }

        return $logs;
    }

    /**
     * Count total logs
     */
    public function countLogs(array $filters = []): int
    {
        return $this->supabase->count('activity_logs', $filters, true);
    }
}
