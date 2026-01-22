<?php

namespace App\Http\Controllers;

use App\Jobs\RegisterClick;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedirectController extends Controller
{
    private const NEGATIVE_SENTINEL = '__missing__';
    private const NEGATIVE_TTL_SECONDS = 60;

    public function __invoke(Request $request, string $slug): RedirectResponse|Response
    {
        $startedAt = microtime(true);
        $cache = Cache::store(config('cache.default'));
        $cached = null;
        $cacheHit = false;

        try {
            $cached = $cache->get($slug);
        } catch (Throwable $exception) {
            Log::warning('redirect.cache_error', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);
            $cached = null;
        }

        if ($cached === self::NEGATIVE_SENTINEL) {
            $this->logRedirect($slug, false, $startedAt);
            abort(404);
        }

        if (is_string($cached) && $cached !== '') {
            $cacheHit = true;
            RegisterClick::dispatch(
                $slug,
                $request->ip(),
                $request->userAgent(),
                Carbon::now()
            );

            $this->logRedirect($slug, true, $startedAt);
            return redirect()->away($cached, 302);
        }

        try {
            $record = DB::table('links')
                ->select('id', 'original_url', 'expires_at')
                ->where('slug', $slug)
                ->first();
        } catch (Throwable $exception) {
            Log::error('redirect.db_error', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);
            abort(500);
        }

        if (!$record) {
            try {
                $cache->put($slug, self::NEGATIVE_SENTINEL, self::NEGATIVE_TTL_SECONDS);
            } catch (Throwable) {
                // ignore cache failures
            }

            $this->logRedirect($slug, $cacheHit, $startedAt);
            abort(404);
        }

        if ($record->expires_at !== null) {
            $expiresAt = Carbon::parse($record->expires_at);

            if ($expiresAt->isPast()) {
                try {
                    $cache->put($slug, self::NEGATIVE_SENTINEL, self::NEGATIVE_TTL_SECONDS);
                } catch (Throwable) {
                    // ignore cache failures
                }

                $this->logRedirect($slug, $cacheHit, $startedAt);
                abort(410);
            }
        }

        RegisterClick::dispatch(
            $slug,
            $request->ip(),
            $request->userAgent(),
            Carbon::now()
        );

        try {
            if ($record->expires_at === null) {
                $cache->forever($slug, $record->original_url);
            } else {
                $ttl = Carbon::now()->diffInSeconds(Carbon::parse($record->expires_at), false);
                if ($ttl > 0) {
                    $cache->put($slug, $record->original_url, $ttl);
                }
            }
        } catch (Throwable) {
            // ignore cache failures
        }

        $this->logRedirect($slug, $cacheHit, $startedAt);
        return redirect()->away($record->original_url, 302);
    }

    private function logRedirect(string $slug, bool $cacheHit, float $startedAt): void
    {
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        Log::info('redirect.request', [
            'slug' => $slug,
            'cache_hit' => $cacheHit,
            'latency_ms' => $latencyMs,
        ]);
    }
}
