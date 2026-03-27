<?php

namespace App\Application\Link;

use App\Domain\Link\AnalyticsRepository;
use App\Models\Link as LinkModel;
use Illuminate\Support\Carbon;

final class ListLinksAction
{
    public function __construct(
        private AnalyticsRepository $analytics
    ) {}

    /**
     * @param  int  $page
     * @param  int  $perPage  Max 100
     * @return array{data: array, meta: array}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        $perPage = min($perPage, 100);

        $paginator = LinkModel::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $models  = $paginator->items();
        $linkIds = array_map(fn ($m) => $m->id, $models);

        $totalClicksMap = $this->analytics->getTotalClicksForLinks($linkIds);
        $todayClicksMap = $this->analytics->getTodayClicksForLinks($linkIds);

        $appUrl = rtrim(config('app.url'), '/');
        $now    = Carbon::now();

        $data = array_map(function (LinkModel $link) use ($totalClicksMap, $todayClicksMap, $appUrl, $now) {
            $isActive = $link->deleted_at === null
                && ($link->expires_at === null || Carbon::parse($link->expires_at)->gt($now));

            return [
                'slug'         => $link->slug,
                'short_url'    => $appUrl . '/' . $link->slug,
                'original_url' => $link->original_url,
                'expires_at'   => $link->expires_at
                    ? Carbon::parse($link->expires_at)->format(DATE_ATOM)
                    : null,
                'created_at'   => Carbon::parse($link->created_at)->format(DATE_ATOM),
                'total_clicks' => $totalClicksMap[$link->id] ?? 0,
                'clicks_today' => $todayClicksMap[$link->id] ?? 0,
                'is_active'    => $isActive,
            ];
        }, $models);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ];
    }
}
