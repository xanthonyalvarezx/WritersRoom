<?php

namespace App\Support;

class Genres
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Fiction' => [
                'fiction' => 'Fiction',
                'fiction-literary' => 'Literary',
                'fiction-mystery' => 'Mystery',
                'fiction-thriller' => 'Thriller',
                'fiction-romance' => 'Romance',
                'fiction-fantasy' => 'Fantasy',
                'fiction-science' => 'Science Fiction',
                'fiction-horror' => 'Horror',
                'fiction-historical' => 'Historical',
            ],
            'Non-Fiction' => [
                'non-fiction' => 'Non-Fiction',
                'non-fiction-essay' => 'Essay',
                'non-fiction-memoir' => 'Memoir',
                'non-fiction-biography' => 'Biography',
                'non-fiction-journalism' => 'Journalism',
                'non-fiction-criticism' => 'Criticism',
            ],
            'Poetry' => [
                'poetry' => 'Poetry',
                'poetry-lyric' => 'Lyric',
                'poetry-narrative' => 'Narrative',
                'poetry-spoken-word' => 'Spoken Word',
                'poetry-haiku' => 'Haiku',
            ],
            'Short Stories' => [
                'short-stories' => 'Short Stories',
                'short-stories-flash' => 'Flash',
                'short-stories-story' => 'Short Story',
                'short-stories-cycle' => 'Cycle',
            ],
            'Novels' => [
                'novels' => 'Novels',
                'novels-contemporary' => 'Contemporary',
                'novels-literary' => 'Literary',
                'novels-genre' => 'Genre',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(array_merge(...array_values(self::grouped())));
    }

    public static function label(?string $value): string
    {
        foreach (self::grouped() as $group => $options) {
            if (! isset($options[$value])) {
                continue;
            }

            return $options[$value] === $group
                ? $group
                : $group.' · '.$options[$value];
        }

        return 'Piece';
    }
}
