<?php

namespace App\Application\Link;

use App\Domain\Link\AnalyticsRepository;
use App\Domain\Link\LinkRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class GetLinkAnalyticsAction
{
    public function __construct(
        private AnalyticsRepository $analytics,
        private LinkRepository $links
    ) {}

    /**
     * @param  string  $slug
     * @param  string  $period  One of: 7d, 30d, all
     * @return array
     * @throws \InvalidArgumentException  if period is invalid
     * @throws \RuntimeException          if link is not found
     */
    public function execute(string $slug, string $period = '7d'): array
    {
        $allowedPeriods = ['7d', '30d', 'all'];
        if (! in_array($period, $allowedPeriods, true)) {
            throw new \InvalidArgumentException(
                'Invalid period. Allowed values: 7d, 30d, all'
            );
        }

        $link = $this->links->findBySlug($slug);
        if ($link === null) {
            throw new \RuntimeException("Link not found: {$slug}");
        }

        $cacheKey = "analytics:{$slug}:{$period}";

        return Cache::remember($cacheKey, 300, function () use ($link, $slug, $period) {
            $linkId = $link->id();
            $today  = Carbon::now()->toDateString();

            // Determine date range
            switch ($period) {
                case '7d':
                    $startDate = Carbon::now()->subDays(6)->toDateString();
                    break;
                case '30d':
                    $startDate = Carbon::now()->subDays(29)->toDateString();
                    break;
                case 'all':
                default:
                    $startDate = $link->createdAt()
                        ? Carbon::instance($link->createdAt())->toDateString()
                        : '2000-01-01';
                    break;
            }

            $totalClicks    = $this->analytics->getTotalClicks($linkId);
            $clicksToday    = $this->analytics->getClicksToday($linkId);
            $clicksThisWeek = $this->analytics->getClicksThisWeek($linkId);
            $dailyClicks    = $this->analytics->getDailyClicks($linkId, $startDate, $today);
            $recentClicks   = $this->analytics->getRecentClicks($linkId, 20);

            return [
                'slug'             => $slug,
                'original_url'     => $link->originalUrl(),
                'expires_at'       => $link->expiresAt()
                    ? Carbon::instance($link->expiresAt())->format(DATE_ATOM)
                    : null,
                'created_at'       => $link->createdAt()
                    ? Carbon::instance($link->createdAt())->format(DATE_ATOM)
                    : null,
                'total_clicks'     => $totalClicks,
                'clicks_today'     => $clicksToday,
                'clicks_this_week' => $clicksThisWeek,
                'daily_clicks'     => $dailyClicks,
                'recent_clicks'    => $recentClicks,
            ];
        });
    }
}
