<?php

namespace App\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RegisterClick implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private string $slug,
        private ?string $ip,
        private ?string $userAgent,
        private DateTimeInterface $clickedAt
    ) {
    }

    public function handle(): void
    {
        /** @var Builder $query */
        $query = DB::table('links')
            ->select('id', 'expires_at')
            ->where('slug', $this->slug);

        $link = $query->first();

        if (!$link) {
            return;
        }

        if ($link->expires_at !== null) {
            $expiresAt = Carbon::parse($link->expires_at);
            if ($expiresAt->isPast()) {
                return;
            }
        }

        DB::table('clicks')->insert([
            'link_id' => $link->id,
            'clicked_at' => $this->clickedAt,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
        ]);

        $day = Carbon::instance($this->clickedAt)->toDateString();
        $key = 'clicks:daily:' . $day;
        Redis::hincrby($key, (string) $link->id, 1);
    }
}
