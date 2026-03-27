<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Link\AnalyticsRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class EloquentAnalyticsRepository implements AnalyticsRepository
{
    public function getTotalClicks(int $linkId): int
    {
        $historical = (int) DB::table('daily_clicks')
            ->where('link_id', $linkId)
            ->sum('count');

        $today = Carbon::now()->toDateString();
        $todayRedis = (int) Redis::connection()->hget("clicks:daily:{$today}", (string) $linkId);

        // daily_clicks may already include today if ClickAggregator has flushed; avoid double-counting
        // by checking if today's row already exists in daily_clicks
        $todayInDb = (int) DB::table('daily_clicks')
            ->where('link_id', $linkId)
            ->where('day', $today)
            ->value('count');

        $todayExtra = max(0, $todayRedis - $todayInDb);

        return $historical + $todayExtra;
    }

    public function getClicksToday(int $linkId): int
    {
        $today = Carbon::now()->toDateString();

        $dbCount = (int) DB::table('daily_clicks')
            ->where('link_id', $linkId)
            ->where('day', $today)
            ->value('count');

        $redisCount = (int) Redis::connection()->hget("clicks:daily:{$today}", (string) $linkId);

        return max($dbCount, $redisCount);
    }

    public function getClicksThisWeek(int $linkId): int
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $today = Carbon::now()->toDateString();

        $dbCount = (int) DB::table('daily_clicks')
            ->where('link_id', $linkId)
            ->where('day', '>=', $startOfWeek)
            ->sum('count');

        $todayInDb = (int) DB::table('daily_clicks')
            ->where('link_id', $linkId)
            ->where('day', $today)
            ->value('count');

        $todayRedis = (int) Redis::connection()->hget("clicks:daily:{$today}", (string) $linkId);
        $todayExtra = max(0, $todayRedis - $todayInDb);

        return $dbCount + $todayExtra;
    }

    public function getDailyClicks(int $linkId, string $startDate, string $endDate): array
    {
        $today = Carbon::now()->toDateString();

        // Get historical data from daily_clicks table (excluding today to handle separately)
        $rows = DB::table('daily_clicks')
            ->where('link_id', $linkId)
            ->where('day', '>=', $startDate)
            ->where('day', '<=', $endDate)
            ->orderBy('day', 'asc')
            ->get(['day', 'count'])
            ->keyBy('day');

        // Build a result indexed by date
        $result = [];
        foreach ($rows as $day => $row) {
            $result[$day] = (int) $row->count;
        }

        // Supplement today's count from Redis if today is within range
        if ($today >= $startDate && $today <= $endDate) {
            $redisCount = (int) Redis::connection()->hget("clicks:daily:{$today}", (string) $linkId);
            $dbCount = (int) ($result[$today] ?? 0);
            $todayCount = max($dbCount, $redisCount);
            if ($todayCount > 0) {
                $result[$today] = $todayCount;
            }
        }

        // Sort by date ascending and format
        ksort($result);

        $formatted = [];
        foreach ($result as $date => $count) {
            $formatted[] = [
                'date'  => $date,
                'count' => $count,
            ];
        }

        return $formatted;
    }

    public function getRecentClicks(int $linkId, int $limit = 20): array
    {
        $rows = DB::table('clicks')
            ->where('link_id', $linkId)
            ->orderBy('clicked_at', 'desc')
            ->limit($limit)
            ->get(['clicked_at', 'ip', 'user_agent']);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'clicked_at'        => Carbon::parse($row->clicked_at)->format(DATE_ATOM),
                'ip_masked'         => $this->maskIp($row->ip),
                'user_agent_parsed' => $this->parseUserAgent($row->user_agent),
                'country'           => null,
            ];
        }

        return $result;
    }

    public function getTotalClicksForLinks(array $linkIds): array
    {
        if (empty($linkIds)) {
            return [];
        }

        $rows = DB::table('daily_clicks')
            ->whereIn('link_id', $linkIds)
            ->groupBy('link_id')
            ->get([DB::raw('link_id'), DB::raw('SUM(count) AS total')]);

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row->link_id] = (int) $row->total;
        }

        // Supplement with Redis for today
        $today = Carbon::now()->toDateString();
        $redisAll = Redis::connection()->hgetall("clicks:daily:{$today}");

        foreach ($linkIds as $linkId) {
            $redisCount  = (int) ($redisAll[(string) $linkId] ?? 0);
            $dbTodayCount = (int) DB::table('daily_clicks')
                ->where('link_id', $linkId)
                ->where('day', $today)
                ->value('count');

            $extra = max(0, $redisCount - $dbTodayCount);
            $totals[$linkId] = ($totals[$linkId] ?? 0) + $extra;
        }

        return $totals;
    }

    public function getTodayClicksForLinks(array $linkIds): array
    {
        if (empty($linkIds)) {
            return [];
        }

        $today = Carbon::now()->toDateString();

        $rows = DB::table('daily_clicks')
            ->whereIn('link_id', $linkIds)
            ->where('day', $today)
            ->get(['link_id', 'count']);

        $dbCounts = [];
        foreach ($rows as $row) {
            $dbCounts[(int) $row->link_id] = (int) $row->count;
        }

        $redisAll = Redis::connection()->hgetall("clicks:daily:{$today}");

        $result = [];
        foreach ($linkIds as $linkId) {
            $dbCount    = $dbCounts[$linkId] ?? 0;
            $redisCount = (int) ($redisAll[(string) $linkId] ?? 0);
            $result[$linkId] = max($dbCount, $redisCount);
        }

        return $result;
    }

    private function maskIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        // IPv4: replace last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.xxx', $ip);
        }

        // IPv6: replace last group
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return preg_replace('/:[0-9a-fA-F]*$/', ':xxx', $ip);
        }

        // Unknown format — mask last segment after last delimiter
        return preg_replace('/[.:][^.:]*$/', '.xxx', $ip);
    }

    private function parseUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        // Extract browser name and major version
        $browser = null;
        $os = null;

        // Order matters — check more specific patterns first
        $browserPatterns = [
            'Edg'         => ['pattern' => '/Edg\/(\d+)/', 'name' => 'Edge'],
            'OPR'         => ['pattern' => '/OPR\/(\d+)/', 'name' => 'Opera'],
            'Opera'       => ['pattern' => '/Opera\/(\d+)/', 'name' => 'Opera'],
            'Firefox'     => ['pattern' => '/Firefox\/(\d+)/', 'name' => 'Firefox'],
            'SamsungBrowser' => ['pattern' => '/SamsungBrowser\/(\d+)/', 'name' => 'Samsung Browser'],
            'Chrome'      => ['pattern' => '/Chrome\/(\d+)/', 'name' => 'Chrome'],
            'Safari'      => ['pattern' => '/Version\/(\d+).*Safari/', 'name' => 'Safari'],
            'MSIE'        => ['pattern' => '/MSIE (\d+)/', 'name' => 'Internet Explorer'],
            'Trident'     => ['pattern' => '/rv:(\d+)/', 'name' => 'Internet Explorer'],
        ];

        foreach ($browserPatterns as $key => $info) {
            if (str_contains($userAgent, $key) && preg_match($info['pattern'], $userAgent, $m)) {
                $browser = $info['name'] . ' ' . $m[1];
                break;
            }
        }

        // OS detection
        $osPatterns = [
            'Windows NT 10' => 'Windows 10',
            'Windows NT 6.3' => 'Windows 8.1',
            'Windows NT 6.2' => 'Windows 8',
            'Windows NT 6.1' => 'Windows 7',
            'Windows'       => 'Windows',
            'Mac OS X'      => 'macOS',
            'Android'       => 'Android',
            'iPhone'        => 'iOS',
            'iPad'          => 'iOS',
            'Linux'         => 'Linux',
        ];

        foreach ($osPatterns as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                $os = $label;
                break;
            }
        }

        if ($browser !== null && $os !== null) {
            return $browser . ' / ' . $os;
        }

        if ($browser !== null) {
            return $browser;
        }

        if ($os !== null) {
            return $os;
        }

        return null;
    }
}
