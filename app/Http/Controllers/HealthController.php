<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function check(Request $request): JsonResponse|Response
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
                'error'  => $exception->getMessage(),
            ]);
        }

        try {
            Redis::ping();
            Log::info('health.redis', ['status' => 'ok']);
        } catch (Throwable $exception) {
            $redisStatus = 'error';
            Log::error('health.redis', [
                'status' => 'error',
                'error'  => $exception->getMessage(),
            ]);
        }

        $allOk    = $dbStatus === 'ok' && $redisStatus === 'ok';
        $status   = $allOk ? 'ok' : 'error';
        $httpCode = $allOk ? 200 : 503;
        $services = [
            'database' => $dbStatus,
            'redis'    => $redisStatus,
        ];

        // Return HTML view when the request prefers text/html (browser requests)
        if ($request->accepts(['text/html']) && ! $request->wantsJson()) {
            return response()
                ->view('health', [
                    'status'    => $status,
                    'timestamp' => $timestamp,
                    'services'  => $services,
                ], $httpCode)
                ->header('Cache-Control', 'no-store, no-cache');
        }

        $payload = [
            'status'    => $status,
            'timestamp' => $timestamp,
        ];

        if (! $allOk) {
            $payload['services'] = $services;
        }

        return response()
            ->json($payload, $httpCode)
            ->header('Cache-Control', 'no-store, no-cache');
    }
}
