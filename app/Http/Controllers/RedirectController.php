<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class RedirectController extends Controller
{
    private const NEGATIVE_SENTINEL = '__missing__';
    private const NEGATIVE_TTL_SECONDS = 60;

    public function __invoke(string $slug): RedirectResponse|Response
    {
        $cache = Cache::store('redis');
        $cached = null;

        try {
            $cached = $cache->get($slug);
        } catch (Throwable) {
            $cached = null;
        }

        if ($cached === self::NEGATIVE_SENTINEL) {
            abort(404);
        }

        if (is_string($cached) && $cached !== '') {
            return redirect()->away($cached, 302);
        }

        $record = DB::table('links')
            ->select('original_url', 'expires_at')
            ->where('slug', $slug)
            ->first();

        if (!$record) {
            try {
                $cache->put($slug, self::NEGATIVE_SENTINEL, self::NEGATIVE_TTL_SECONDS);
            } catch (Throwable) {
                // ignore cache failures
            }

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

                abort(410);
            }
        }

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

        return redirect()->away($record->original_url, 302);
    }
}
