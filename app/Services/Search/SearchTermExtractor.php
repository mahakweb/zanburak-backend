<?php

namespace App\Services\Search;

class SearchTermExtractor
{
    /** @var list<string> */
    private array $stopWords;

    /** @var list<string> */
    private array $slugNoiseWords;

    public function __construct()
    {
        $this->stopWords = array_map(
            fn (string $word) => mb_strtolower(trim($word)),
            config('search.stop_words', [])
        );

        $this->slugNoiseWords = array_map(
            'mb_strtolower',
            config('search.slug_noise_words', [
                'complete', 'full', 'course', 'courses', 'tutorial', 'tutorials',
                'guide', 'learn', 'learning', 'beginner', 'advanced', 'intermediate',
                'from', 'zero', 'scratch', 'to', 'the', 'a', 'an', 'and', 'or',
                'for', 'with', 'how', 'what', 'why', 'introduction', 'intro',
                'basic', 'basics', 'master', 'masterclass', 'bootcamp', 'training',
                'آموزش', 'دوره', 'کامل', 'صفر', 'صد', 'مقدماتی', 'پیشرفته', 'حرفه',
            ])
        );
    }

    /**
     * @param  list<string>  $tags
     * @param  list<string>  $categories
     * @return list<string>
     */
    public function fromDocument(
        ?string $title,
        ?string $englishTitle = null,
        ?string $metaKeywords = null,
        array $tags = [],
        array $categories = [],
    ): array {
        $terms = [];

        foreach ([$title, $englishTitle, $metaKeywords] as $text) {
            $terms = [...$terms, ...$this->extractFromText($text)];
        }

        foreach ($tags as $tag) {
            $terms = [...$terms, ...$this->extractFromText($tag)];
        }

        foreach ($categories as $category) {
            $terms = [...$terms, ...$this->extractFromText($category)];
        }

        if ($englishTitle) {
            $terms = [...$terms, ...$this->extractSlugParts($englishTitle)];
        }

        return $this->uniqueTerms($terms);
    }

    /**
     * @return list<string>
     */
    public function extractFromText(?string $text): array
    {
        if (! is_string($text) || trim($text) === '') {
            return [];
        }

        $text = $this->normalize($text);
        $terms = [];

        if (preg_match_all('/[\p{Latin}][\p{Latin}\p{N}._+\-]*/u', $text, $latinMatches)) {
            foreach ($latinMatches[0] as $token) {
                $terms[] = $token;
                $terms[] = str_replace('.', '', $token);
            }
        }

        if (preg_match_all('/[\p{Arabic}][\p{Arabic}\p{N}]*/u', $text, $persianMatches)) {
            $terms = [...$terms, ...$persianMatches[0]];
        }

        foreach (preg_split('/[\s,،؛|\/]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (mb_strlen($token) >= 2) {
                $terms[] = $token;
            }
        }

        return $terms;
    }

    /**
     * @return list<string>
     */
    public function extractSlugParts(?string $englishTitle): array
    {
        if (! is_string($englishTitle) || trim($englishTitle) === '') {
            return [];
        }

        $parts = preg_split('/[-_\s]+/u', mb_strtolower($englishTitle), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($parts, fn (string $part) => ! $this->isNoiseWord($part)));
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    public function uniqueTerms(array $terms): array
    {
        $unique = [];

        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '' || $this->isNoiseWord($term) || mb_strlen($term) < 2) {
                continue;
            }

            $unique[mb_strtolower($term)] = $term;
        }

        return array_values($unique);
    }

    public function normalize(string $text): string
    {
        $text = str_replace(["\u{200C}", "\u{200F}", "\u{200E}"], ' ', $text);
        $text = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function isNoiseWord(string $term): bool
    {
        $normalized = mb_strtolower(trim($term));

        return in_array($normalized, $this->stopWords, true)
            || in_array($normalized, $this->slugNoiseWords, true);
    }
}
