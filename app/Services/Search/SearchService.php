<?php

namespace App\Services\Search;

use App\Models\Article;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SearchService
{
    /** @var list<string> */
    private const VALID_TYPES = ['course', 'episode', 'question', 'article'];

    public function __construct(
        private readonly SearchQueryNormalizer $normalizer,
    ) {}

    /**
     * @param  array{
     *     key?: string,
     *     limit?: int,
     *     page?: int,
     *     types?: list<string>,
     *     sort?: string,
     *     level?: string|null,
     *     category?: string|null,
     * }  $params
     */
    public function search(array $params): array
    {
        $rawKey = (string) ($params['key'] ?? '');
        $query = $this->normalizer->normalize($rawKey);
        $limit = $this->resolveLimit($params['limit'] ?? null);
        $page = max(1, (int) ($params['page'] ?? 1));
        $sort = in_array($params['sort'] ?? 'relevance', ['relevance', 'newest', 'popular'], true)
            ? ($params['sort'] ?? 'relevance')
            : 'relevance';
        $types = $this->resolveTypes($params['types'] ?? null);
        $level = isset($params['level']) ? trim((string) $params['level']) : null;
        $category = isset($params['category']) ? trim((string) $params['category']) : null;

        if ($query === '') {
            return $this->emptyResponse('No search key provided');
        }

        if ($this->normalizer->isTooGeneric($query)) {
            return $this->emptyResponse('Query too generic', $query);
        }

        $scoutTake = min(
            max($page * $limit * 3, $limit * 5),
            (int) config('search.max_scout_take', 500)
        );
        $tokenGroups = $this->normalizer->tokenGroups($query);

        $facetCounts = [];
        $allHits = [];

        foreach (self::VALID_TYPES as $type) {
            $typeHits = $this->searchType(
                type: $type,
                query: $query,
                tokenGroups: $tokenGroups,
                take: $scoutTake,
                sort: $sort,
                level: $level,
                category: $category,
            );

            $facetCounts[$type] = count($typeHits);
            $allHits = array_merge($allHits, $typeHits);
        }

        usort($allHits, function (array $a, array $b) use ($sort) {
            if ($sort === 'newest') {
                return ($b['sort_id'] ?? 0) <=> ($a['sort_id'] ?? 0);
            }

            if ($sort === 'popular') {
                return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
            }

            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        $filteredHits = array_values(array_filter(
            $allHits,
            fn (array $hit) => in_array($hit['type'], $types, true)
        ));

        $total = count($filteredHits);
        $offset = ($page - 1) * $limit;

        $results = array_map(
            fn (array $hit) => $hit['payload'],
            array_slice($filteredHits, $offset, $limit)
        );

        return [
            'message' => 'Success',
            'query' => $query,
            'result' => $results,
            'facets' => [
                'types' => $facetCounts,
            ],
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'page' => $page,
                'has_more' => ($offset + $limit) < $total,
                'sort' => $sort,
                'types' => $types,
            ],
        ];
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @return list<array<string, mixed>>
     */
    private function searchType(
        string $type,
        string $query,
        array $tokenGroups,
        int $take,
        string $sort,
        ?string $level,
        ?string $category,
    ): array {
        return match ($type) {
            'course' => $this->searchCourses($query, $tokenGroups, $take, $sort, $level, $category),
            'episode' => $this->searchEpisodes($query, $tokenGroups, $take, $sort),
            'question' => $this->searchQuestions($query, $tokenGroups, $take, $sort),
            'article' => $this->searchArticles($query, $tokenGroups, $take, $sort),
            default => [],
        };
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @return list<array<string, mixed>>
     */
    private function searchCourses(
        string $query,
        array $tokenGroups,
        int $take,
        string $sort,
        ?string $level,
        ?string $category,
    ): array {
        $config = config('search.types.course');
        $raw = $this->scoutRawSearch(
            modelClass: Course::class,
            query: $query,
            take: $take,
            tokenGroups: $tokenGroups,
            dbColumns: $config['db_columns'],
            scoutColumns: $config['scout_columns'],
            filters: array_filter([
                'level_slug' => $level,
                'category_slug' => $category,
            ]),
        );

        if ($raw['ids']->isEmpty()) {
            return [];
        }

        $courses = Course::query()
            ->whereIn('id', $raw['ids'])
            ->when($level, fn ($q) => $q->whereHas('level', fn ($l) => $l->where('slug', $level)))
            ->when($category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $category)))
            ->with(['level:id,title,slug', 'category:id,title,slug'])
            ->get()
            ->keyBy('id');

        $hits = [];

        foreach ($raw['ids'] as $index => $id) {
            $course = $courses->get($id);
            if (! $course || ! $course->publish) {
                continue;
            }

            $highlight = $raw['highlights'][$id] ?? [];
            $hits[] = [
                'type' => 'course',
                'score' => $raw['scores'][$id] ?? (1000 - $index),
                'sort_id' => $course->id,
                'popularity' => $course->viewCount(),
                'payload' => [
                    'type' => 'course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'english_title' => $course->english_title,
                    'slug' => $course->slug,
                    'poster' => $course->poster,
                    'number_of_episodes' => $course->numberOfEpisode(),
                    'level' => $course->level ? [
                        'title' => $course->level->title,
                        'slug' => $course->level->slug,
                    ] : null,
                    'categories' => $course->category->map(fn ($cat) => [
                        'title' => $cat->title,
                        'slug' => $cat->slug,
                    ])->values()->all(),
                    '_highlight' => $highlight,
                ],
            ];
        }

        return $hits;
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @return list<array<string, mixed>>
     */
    private function searchEpisodes(
        string $query,
        array $tokenGroups,
        int $take,
        string $sort,
    ): array {
        $config = config('search.types.episode');
        $raw = $this->scoutRawSearch(
            modelClass: Episode::class,
            query: $query,
            take: $take,
            tokenGroups: $tokenGroups,
            dbColumns: $config['db_columns'],
            scoutColumns: $config['scout_columns'],
        );

        if ($raw['ids']->isEmpty()) {
            return [];
        }

        $episodes = Episode::query()
            ->whereIn('id', $raw['ids'])
            ->with(['section.course'])
            ->get()
            ->keyBy('id');

        $hits = [];

        foreach ($raw['ids'] as $index => $id) {
            $episode = $episodes->get($id);
            if (! $episode || ! $episode->publish) {
                continue;
            }

            $course = optional($episode->section)->course;
            $highlight = $raw['highlights'][$id] ?? [];

            $hits[] = [
                'type' => 'episode',
                'score' => $raw['scores'][$id] ?? (1000 - $index),
                'sort_id' => $episode->id,
                'popularity' => $episode->viewCount(),
                'payload' => [
                    'type' => 'episode',
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'english_title' => $episode->english_title,
                    'slug' => $episode->slug,
                    'course' => $course ? [
                        'id' => $course->id,
                        'title' => $course->title,
                        'english_title' => $course->english_title,
                        'slug' => $course->slug,
                        'poster' => $course->poster,
                    ] : null,
                    '_highlight' => $highlight,
                ],
            ];
        }

        return $hits;
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @return list<array<string, mixed>>
     */
    private function searchQuestions(
        string $query,
        array $tokenGroups,
        int $take,
        string $sort,
    ): array {
        $config = config('search.types.question');
        $raw = $this->scoutRawSearch(
            modelClass: Question::class,
            query: $query,
            take: $take,
            tokenGroups: $tokenGroups,
            dbColumns: $config['db_columns'],
            scoutColumns: $config['scout_columns'],
        );

        if ($raw['ids']->isEmpty()) {
            return [];
        }

        $questions = Question::query()
            ->whereIn('id', $raw['ids'])
            ->withCount('answers')
            ->get()
            ->keyBy('id');

        $hits = [];

        foreach ($raw['ids'] as $index => $id) {
            $question = $questions->get($id);
            if (! $question || ! $question->publish) {
                continue;
            }

            $highlight = $raw['highlights'][$id] ?? [];

            $hits[] = [
                'type' => 'question',
                'score' => $raw['scores'][$id] ?? (1000 - $index),
                'sort_id' => $question->id,
                'popularity' => (int) $question->answers_count,
                'payload' => [
                    'type' => 'question',
                    'id' => $question->id,
                    'subject' => $question->subject,
                    'slug' => $question->slug,
                    'number_of_answers' => (int) $question->answers_count,
                    '_highlight' => $highlight,
                ],
            ];
        }

        return $hits;
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @return list<array<string, mixed>>
     */
    private function searchArticles(
        string $query,
        array $tokenGroups,
        int $take,
        string $sort,
    ): array {
        $config = config('search.types.article');
        $raw = $this->scoutRawSearch(
            modelClass: Article::class,
            query: $query,
            take: $take,
            tokenGroups: $tokenGroups,
            dbColumns: $config['db_columns'],
            scoutColumns: $config['scout_columns'],
        );

        if ($raw['ids']->isEmpty()) {
            return [];
        }

        $articles = Article::query()
            ->whereIn('id', $raw['ids'])
            ->with(['user:id,first_name,last_name,username', 'category:id,title,slug'])
            ->get()
            ->keyBy('id');

        $hits = [];

        foreach ($raw['ids'] as $index => $id) {
            $article = $articles->get($id);
            if (! $article || ! $article->publish || $article->status !== 'published') {
                continue;
            }

            $highlight = $raw['highlights'][$id] ?? [];

            $hits[] = [
                'type' => 'article',
                'score' => $raw['scores'][$id] ?? (1000 - $index),
                'sort_id' => $article->id,
                'popularity' => $article->viewCount(),
                'payload' => [
                    'type' => 'article',
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'cover_image' => $article->cover_image,
                    'reading_time_minutes' => $article->reading_time_minutes,
                    'author' => $article->user ? [
                        'first_name' => $article->user->first_name,
                        'last_name' => $article->user->last_name,
                        'username' => $article->user->username,
                    ] : null,
                    'category' => $article->category ? [
                        'title' => $article->category->title,
                        'slug' => $article->category->slug,
                    ] : null,
                    '_highlight' => $highlight,
                ],
            ];
        }

        return $hits;
    }

    /**
     * @param  list<string>  $dbColumns
     * @param  list<string>  $scoutColumns
     * @param  list<list<string>>  $tokenGroups
     * @param  array<string, string|null>  $filters
     * @return array{ids: Collection<int, int>, scores: array<int, float>, highlights: array<int, array<string, string>>}
     */
    private function scoutRawSearch(
        string $modelClass,
        string $query,
        int $take,
        array $tokenGroups,
        array $dbColumns,
        array $scoutColumns,
        array $filters = [],
    ): array {
        try {
            $builder = $modelClass::search($query)
                ->where('publish', true)
                ->take($take)
                ->options([
                    'attributesToHighlight' => $scoutColumns,
                    'highlightPreTag' => '<mark>',
                    'highlightPostTag' => '</mark>',
                    'showRankingScore' => true,
                ]);

            foreach ($filters as $field => $value) {
                if ($value !== null && $value !== '') {
                    $builder->where($field, $value);
                }
            }

            $response = $builder->raw();
            $hits = $response['hits'] ?? [];

            $ids = collect();
            $scores = [];
            $highlights = [];

            foreach ($hits as $index => $hit) {
                $id = (int) ($hit['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $ids->push($id);
                $scores[$id] = (float) ($hit['_rankingScore'] ?? (1 - ($index * 0.01)));
                $highlights[$id] = $this->extractHighlights($hit);
            }

            if ($ids->isNotEmpty()) {
                return compact('ids', 'scores', 'highlights');
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $this->databaseFallbackSearch($modelClass, $query, $tokenGroups, $take, $dbColumns, $filters);
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @param  list<string>  $dbColumns
     * @param  array<string, string|null>  $filters
     * @return array{ids: Collection<int, int>, scores: array<int, float>, highlights: array<int, array<string, string>>}
     */
    private function databaseFallbackSearch(
        string $modelClass,
        string $query,
        array $tokenGroups,
        int $take,
        array $dbColumns,
        array $filters = [],
    ): array {
        $ids = $this->databaseSearchIds($modelClass, $tokenGroups, $take, $dbColumns, $filters);

        if ($ids->isEmpty() && $query !== '') {
            $expandedGroups = $this->normalizer->tokenGroups($this->normalizer->expandedQuery($query));

            if ($expandedGroups !== $tokenGroups) {
                $ids = $this->databaseSearchIds($modelClass, $expandedGroups, $take, $dbColumns, $filters);
            }
        }

        return [
            'ids' => $ids,
            'scores' => $ids->mapWithKeys(fn ($id, $index) => [$id => 1 - ($index * 0.01)])->all(),
            'highlights' => [],
        ];
    }

    /**
     * @param  list<list<string>>  $tokenGroups
     * @param  list<string>  $dbColumns
     * @param  array<string, string|null>  $filters
     * @return Collection<int, int>
     */
    private function databaseSearchIds(
        string $modelClass,
        array $tokenGroups,
        int $take,
        array $dbColumns,
        array $filters = [],
    ): Collection {
        /** @var Model $modelClass */
        $query = $modelClass::query()->where('publish', true);

        foreach ($tokenGroups as $group) {
            $query->where(function ($builder) use ($group, $dbColumns, $modelClass) {
                foreach ($group as $term) {
                    $likeToken = '%'.addcslashes($term, '%_\\').'%';

                    $builder->orWhere(function ($sub) use ($dbColumns, $likeToken, $modelClass) {
                        foreach ($dbColumns as $column) {
                            $sub->orWhere($column, 'LIKE', $likeToken);
                        }

                        if ($modelClass === Course::class) {
                            $sub->orWhereHas('tags', fn ($q) => $q->where('name', 'LIKE', $likeToken))
                                ->orWhereHas('category', fn ($q) => $q->where('title', 'LIKE', $likeToken))
                                ->orWhereHas('level', fn ($q) => $q->where('title', 'LIKE', $likeToken)->orWhere('english_title', 'LIKE', $likeToken))
                                ->orWhereHas('teacher', fn ($q) => $q->where('first_name', 'LIKE', $likeToken)->orWhere('last_name', 'LIKE', $likeToken)->orWhere('username', 'LIKE', $likeToken));
                        }

                        if ($modelClass === Episode::class) {
                            $sub->orWhereHas('section.course', fn ($q) => $q->where('title', 'LIKE', $likeToken)->orWhere('english_title', 'LIKE', $likeToken));
                        }

                        if ($modelClass === Question::class) {
                            $sub->orWhereHas('tags', fn ($q) => $q->where('name', 'LIKE', $likeToken))
                                ->orWhereHas('category', fn ($q) => $q->where('title', 'LIKE', $likeToken));
                        }
                    });
                }
            });
        }

        if ($modelClass === Course::class) {
            if (! empty($filters['level_slug'])) {
                $query->whereHas('level', fn ($q) => $q->where('slug', $filters['level_slug']));
            }

            if (! empty($filters['category_slug'])) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category_slug']));
            }
        }

        return $query->orderByDesc('id')->limit($take)->pluck('id');
    }

    /**
     * @param  array<string, mixed>  $hit
     * @return array<string, string>
     */
    private function extractHighlights(array $hit): array
    {
        $formatted = $hit['_formatted'] ?? [];
        $highlights = [];

        foreach ($formatted as $field => $value) {
            if (is_string($value) && str_contains($value, '<mark>')) {
                $highlights[$field] = $value;
            }
        }

        return $highlights;
    }

    private function resolveLimit(mixed $limit): int
    {
        $limit = (int) $limit;
        $default = (int) config('search.default_limit', 20);
        $max = (int) config('search.max_limit', 50);

        if ($limit <= 0) {
            return $default;
        }

        return min($limit, $max);
    }

    /**
     * @param  list<string>|null  $types
     * @return list<string>
     */
    private function resolveTypes(?array $types): array
    {
        if ($types === null || $types === []) {
            return self::VALID_TYPES;
        }

        $valid = array_values(array_intersect($types, self::VALID_TYPES));

        return $valid !== [] ? $valid : self::VALID_TYPES;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResponse(string $message, ?string $query = null): array
    {
        return [
            'message' => $message,
            'query' => $query ?? '',
            'result' => [],
            'facets' => [
                'types' => [
                    'course' => 0,
                    'episode' => 0,
                    'question' => 0,
                ],
            ],
            'meta' => [
                'total' => 0,
                'limit' => (int) config('search.default_limit', 20),
                'page' => 1,
                'has_more' => false,
                'sort' => 'relevance',
                'types' => self::VALID_TYPES,
            ],
        ];
    }
}
