<?php

namespace App\Models;

use App\Support\Genres;
use App\Support\WritingKinds;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'kind', 'title', 'genre', 'body', 'plot', 'chapters', 'desk', 'cover_path', 'prologue', 'epilogue', 'finished', 'published_at'])]
class Piece extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'chapters' => 'array',
            'desk' => 'array',
            'finished' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? asset($this->cover_path) : null;
    }

    public function genreLabel(): string
    {
        return Genres::label($this->genre);
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    public function isReady(): bool
    {
        return $this->finished && $this->isDraft();
    }

    public function deskUrl(): string
    {
        if (! $this->isDraft()) {
            return route('pieces.show', $this);
        }

        return route($this->finished ? 'pieces.review' : 'pieces.edit', $this);
    }

    public function deskAction(): string
    {
        if (! $this->isDraft()) {
            return 'Read';
        }

        return $this->finished ? 'Review' : 'Continue';
    }

    public function statusLabel(): string
    {
        if (! $this->isDraft()) {
            return $this->genreLabel();
        }

        return $this->finished ? 'Ready' : 'Draft';
    }

    public function isStory(): bool
    {
        return WritingKinds::isStory($this->kind);
    }

    public function excerpt(): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($this->body)) ?? '');

        return str($text)->limit(160)->toString();
    }
}
