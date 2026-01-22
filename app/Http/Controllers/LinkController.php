<?php

namespace App\Http\Controllers;

use App\Application\Link\CreateLinkAction;
use App\Http\Requests\StoreLinkRequest;
use Illuminate\Http\JsonResponse;
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
}
