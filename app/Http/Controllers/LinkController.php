<?php

namespace App\Http\Controllers;

use App\Application\Link\CreateLinkAction;
use App\Application\Link\DeactivateLinkAction;
use App\Application\Link\GetLinkAnalyticsAction;
use App\Application\Link\ListLinksAction;
use App\Http\Requests\StoreLinkRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LinkController extends Controller
{
    public function store(StoreLinkRequest $request, CreateLinkAction $action): JsonResponse
    {
        $data = $request->validated();

        $expiresAt = isset($data['expires_at'])
            ? Carbon::parse($data['expires_at'])
            : null;

        $link = $action->execute([
            'original_url' => $data['original_url'],
            'custom_slug' => $data['custom_slug'] ?? null,
            'user_id' => null,
            'expires_at' => $expiresAt,
        ]);

        $shortUrl = rtrim(config('app.url'), '/') . '/' . $link->slug();

        return response()->json([
            'slug' => $link->slug(),
            'short_url' => $shortUrl,
            'original_url' => $link->originalUrl(),
            'expires_at' => $link->expiresAt()?->format(DATE_ATOM),
            'created_at' => $link->createdAt()?->format(DATE_ATOM),
        ], 201);
    }

    public function index(Request $request, ListLinksAction $action): JsonResponse
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $result = $action->execute($page, $perPage);

        return response()->json($result, 200);
    }

    public function analytics(string $slug, Request $request, GetLinkAnalyticsAction $action): JsonResponse
    {
        $period = $request->query('period', '7d');

        $allowedPeriods = ['7d', '30d', 'all'];
        if (! in_array($period, $allowedPeriods, true)) {
            return response()->json(
                ['error' => 'Invalid period. Allowed values: 7d, 30d, all'],
                422
            );
        }

        try {
            $data = $action->execute($slug, $period);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'Link not found'], 404);
        }

        return response()->json($data, 200);
    }

    public function destroy(string $slug, DeactivateLinkAction $action): JsonResponse
    {
        try {
            $action->execute($slug);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'Link not found'], 404);
        }

        return response()->json(null, 204);
    }
}
