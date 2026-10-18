<?php

namespace App\Support;

class WritingKinds
{
    /**
     * @return array<string, array{label: string, detail: string, genre: string, story: bool}>
     */
    public static function all(): array
    {
        return [
            'poem' => [
                'label' => 'A poem',
                'detail' => 'One page, with no chapters.',
                'genre' => 'poetry',
                'story' => false,
            ],
            'essay' => [
                'label' => 'An essay',
                'detail' => 'A single piece of nonfiction.',
                'genre' => 'non-fiction-essay',
                'story' => false,
            ],
            'short-story' => [
                'label' => 'A short story',
                'detail' => 'One story, told in a single sitting.',
                'genre' => 'short-stories',
                'story' => false,
            ],
            'novel' => [
                'label' => 'A novel',
                'detail' => 'Chapters, plus a place to plot the story.',
                'genre' => 'novels',
                'story' => true,
            ],
        ];
    }

    public static function exists(?string $kind): bool
    {
        return $kind !== null && array_key_exists($kind, self::all());
    }

    public static function isStory(?string $kind): bool
    {
        return (bool) (self::all()[$kind]['story'] ?? false);
    }

    public static function defaultGenre(?string $kind): string
    {
        return self::all()[$kind]['genre'] ?? 'fiction';
    }

    public static function label(?string $kind): string
    {
        return self::all()[$kind]['label'] ?? 'A piece';
    }
}
