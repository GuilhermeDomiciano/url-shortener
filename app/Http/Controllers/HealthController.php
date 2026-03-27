<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $timestamp = Carbon::now()->format(DATE_ATOM);
        $dbStatus = 'ok';
        $redisStatus = 'ok';

        try {
            DB::select('SELECT 1');
            Log::info('health.database', ['status' => 'ok']);
        } catch (Throwable $exception) {
            $dbStatus = 'error';
            Log::error('health.database', [
                'status' => 'error',
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            Redis::ping();
            Log::info('health.redis', ['status' => 'ok']);
        } catch (Throwable $exception) {
            $redisStatus = 'error';
            Log::error('health.redis', [
                'status' => 'error',
                'error' => $exception->getMessage(),
            ]);
        }

        if ($dbStatus === 'ok' && $redisStatus === 'ok') {
            return response()->json([
                'status'    => 'ok',
                'timestamp' => $timestamp,
            ], 200);
        }

        return response()->json([
            'status'    => 'error',
            'timestamp' => $timestamp,
            'services'  => [
                'database' => $dbStatus,
                'redis'    => $redisStatus,
            ],
        ], 503);
    }
}
