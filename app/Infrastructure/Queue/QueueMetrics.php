<?php

namespace App\Infrastructure\Queue;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

final class QueueMetrics
{
    public function snapshot(string $queue = 'default'): array
    {
        $connection = Redis::connection();
        $prefix = $this->redisPrefix($connection);
        $queueKey = $prefix . 'queues:' . $queue;
        $length = (int) $connection->llen($queueKey);

        $failedCount = 0;
        if (Schema::hasTable('failed_jobs')) {
            $failedCount = (int) DB::table('failed_jobs')->count();
        }

        $metrics = [
            'queue' => $queue,
            'lag' => $length,
            'failed_jobs' => $failedCount,
        ];

        Log::info('queue.metrics', $metrics);

        return $metrics;
    }

    private function redisPrefix($connection): string
    {
        $client = $connection->client();
        if ($client instanceof \Redis) {
            return (string) $client->getOption(\Redis::OPT_PREFIX);
        }

        return '';
    }
}
