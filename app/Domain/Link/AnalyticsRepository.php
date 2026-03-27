<?php

namespace App\Domain\Link;

interface AnalyticsRepository
{
    public function getTotalClicks(int $linkId): int;

    public function getClicksToday(int $linkId): int;

    public function getClicksThisWeek(int $linkId): int;

    public function getDailyClicks(int $linkId, string $startDate, string $endDate): array;

    public function getRecentClicks(int $linkId, int $limit = 20): array;

    /**
     * @param  int[]  $linkIds
     * @return array<int, int>  map of linkId => total clicks
     */
    public function getTotalClicksForLinks(array $linkIds): array;

    /**
     * @param  int[]  $linkIds
     * @return array<int, int>  map of linkId => today clicks
     */
    public function getTodayClicksForLinks(array $linkIds): array;
}
