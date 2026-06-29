<?php

namespace App\Services\Search;

class SearchQueryNormalizer
{
    /** @var list<string> */
    private array $stopWords;

    public function __construct(
        private readonly SearchSynonymRegistry $synonymRegistry,
    ) {
        $this->stopWords = config('search.stop_words', []);
    }

    public function refreshSynonyms(): void
    {
        $this->synonymRegistry->refresh();
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymGroups(): array
    {
        return $this->synonymRegistry->all();
    }

    public function normalize(string $query): string
    {
        $query = trim($query);

        if ($query === '') {
            return '';
        }

        $query = str_replace(["\u{200C}", "\u{200F}", "\u{200E}"], ' ', $query);
        $query = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $query);
        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;

        return trim($query);
    }

    /**
     * @return list<string>
     */
    public function tokens(string $query): array
    {
        $normalized = $this->normalize($query);

        if ($normalized === '') {
            return [];
        }

        $remaining = mb_strtolower($normalized);
        $matchedPhrases = [];

        foreach ($this->phrasesByLengthDesc() as $phrase) {
            $normalizedPhrase = mb_strtolower($this->normalize($phrase));

            if ($normalizedPhrase === '' || mb_strlen($normalizedPhrase) < 3) {
                continue;
            }

            if (mb_stripos($remaining, $normalizedPhrase) !== false) {
                $matchedPhrases[] = $phrase;
                $remaining = str_replace($normalizedPhrase, ' ', $remaining);
            }
        }

        $rest = array_values(array_filter(
            preg_split('/\s+/u', trim($remaining), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn (string $token) => mb_strlen($token) >= (int) config('search.min_query_length', 2)
        ));

        return array_values(array_unique([...$matchedPhrases, ...$rest]));
    }

    /**
     * @return list<string>
     */
    private function phrasesByLengthDesc(): array
    {
        $phrases = [];

        foreach ($this->synonymGroups() as $group) {
            foreach ($group as $term) {
                if (mb_strlen(trim($term)) >= 3) {
                    $phrases[] = trim($term);
                }
            }
        }

        usort($phrases, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        return $phrases;
    }

    /**
     * @return list<string>
     */
    public function specificTokens(string $query): array
    {
        $stopWordsNormalized = array_map(
            fn (string $word) => mb_strtolower($this->normalize($word)),
            $this->stopWords
        );

        return array_values(array_filter(
            $this->tokens($query),
            function (string $token) use ($stopWordsNormalized) {
                return ! in_array(mb_strtolower($token), $stopWordsNormalized, true);
            }
        ));
    }

    public function isTooGeneric(string $query): bool
    {
        return $this->specificTokens($query) === [];
    }

    /**
     * Groups of synonymous terms — one group per user token.
     *
     * @return list<list<string>>
     */
    public function tokenGroups(string $query): array
    {
        return array_map(
            fn (string $token) => $this->synonymsForToken($token),
            $this->specificTokens($query)
        );
    }

    /**
     * Strict alias list for a single user token (manual map only).
     *
     * @return list<string>
     */
    public function matchTermsForToken(string $token): array
    {
        $normalizedToken = mb_strtolower($this->normalize($token));
        $aliases = config('search.term_aliases.'.$normalizedToken, []);

        return array_values(array_unique([$token, ...$aliases]));
    }

    /**
     * One alias group per user token — used for precise DB matching.
     *
     * @return list<list<string>>
     */
    public function matchGroups(string $query): array
    {
        return array_map(
            fn (string $token) => $this->matchTermsForToken($token),
            $this->specificTokens($query)
        );
    }

    /**
     * @return list<string>
     */
    public function synonymsForToken(string $token): array
    {
        $normalizedToken = mb_strtolower($this->normalize($token));
        $terms = [$token];

        foreach ($this->synonymGroups() as $group) {
            $normalizedGroup = array_map(
                fn (string $item) => mb_strtolower($this->normalize($item)),
                $group
            );

            if (in_array($normalizedToken, $normalizedGroup, true)) {
                $terms = array_merge($terms, $group);
                break;
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Flat list of all terms including synonyms — useful for Meilisearch query boosting.
     *
     * @return list<string>
     */
    public function expandedTerms(string $query): array
    {
        $terms = [];

        foreach ($this->tokenGroups($query) as $group) {
            $terms = array_merge($terms, $group);
        }

        return array_values(array_unique($terms));
    }

    /**
     * Meilisearch-friendly query string built from expanded synonyms.
     */
    public function expandedQuery(string $query): string
    {
        return implode(' ', $this->expandedTerms($query));
    }

    /**
     * @return array<string, list<string>>
     */
    public function meilisearchSynonyms(): array
    {
        $synonyms = [];

        foreach ($this->synonymGroups() as $key => $group) {
            $synonyms[$key] = array_values(array_unique($group));
        }

        return $synonyms;
    }
}
