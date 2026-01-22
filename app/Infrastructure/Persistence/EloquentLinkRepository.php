<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;
use App\Models\Link as LinkModel;
use DateTimeImmutable;
use DateTimeInterface;

final class EloquentLinkRepository implements LinkRepository
{
    public function create(array $data): Link
    {
        $model = LinkModel::create([
            'slug' => $data['slug'],
            'original_url' => $data['original_url'],
            'user_id' => $data['user_id'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return $this->toDomain($model);
    }

    public function findBySlug(string $slug): ?Link
    {
        $model = LinkModel::query()
            ->where('slug', $slug)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function updateSlug(int $id, string $slug): Link
    {
        $model = LinkModel::query()->findOrFail($id);
        $model->slug = $slug;
        $model->save();

        return $this->toDomain($model);
    }

    private function toDomain(LinkModel $model): Link
    {
        return new Link(
            $model->id,
            $model->slug,
            $model->original_url,
            $model->user_id,
            $this->toDateTimeImmutable($model->expires_at),
            $this->toDateTimeImmutable($model->created_at)
        );
    }

    private function toDateTimeImmutable(DateTimeInterface|null $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
