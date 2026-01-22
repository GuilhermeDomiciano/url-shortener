<?php

namespace App\Infrastructure\Analytics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class ClickAggregator
{
    public function flushDailyCounts(): int
    {
        $connection = Redis::connection();
        $prefix = '';
        $client = $connection->client();
        if ($client instanceof \Redis) {
            $prefix = (string) $client->getOption(\Redis::OPT_PREFIX);
        }

        $keys = $connection->keys('clicks:daily:*');
        $total = 0;

        foreach ($keys as $key) {
            $unprefixed = $this->stripPrefix($key, $prefix);
            $day = str_replace('clicks:daily:', '', $unprefixed);
            $counts = $connection->hgetall($unprefixed);

            foreach ($counts as $linkId => $count) {
                $total += (int) $count;
                $this->upsertDailyCount((int) $linkId, $day, (int) $count);
            }

            $connection->del($unprefixed);
        }

        return $total;
    }

    private function upsertDailyCount(int $linkId, string $day, int $count): void
    {
        $now = Carbon::now();

        DB::statement(
            'INSERT INTO daily_clicks (link_id, day, count, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (link_id, day)
             DO UPDATE SET count = daily_clicks.count + EXCLUDED.count, updated_at = EXCLUDED.updated_at',
            [$linkId, $day, $count, $now, $now]
        );
    }

    private function stripPrefix(string $key, string $prefix): string
    {
        if ($prefix !== '' && str_starts_with($key, $prefix)) {
            return substr($key, strlen($prefix));
        }

        return $key;
    }
}
