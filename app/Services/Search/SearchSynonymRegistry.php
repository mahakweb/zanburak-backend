<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Cache;

class SearchSynonymRegistry
{
    private const CACHE_KEY = 'search.synonyms.merged';

    public function __construct(
        private readonly SearchSynonymBuilder $builder,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        $ttl = (int) config('search.synonym_cache_ttl', 3600);

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => $this->builder->build());
    }

    public function refresh(): array
    {
        $synonyms = $this->builder->build();
        Cache::put(self::CACHE_KEY, $synonyms, (int) config('search.synonym_cache_ttl', 3600));

        return $synonyms;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
