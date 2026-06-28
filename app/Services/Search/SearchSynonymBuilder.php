<?php

namespace App\Services\Search;

use App\Models\Course;
use App\Models\Episode;
use App\Models\Question;

class SearchSynonymBuilder
{
    public function __construct(
        private readonly SearchTermExtractor $termExtractor,
    ) {}

    /**
     * Build synonym groups from published content + optional manual overrides.
     *
     * @return array<string, list<string>>
     */
    public function build(): array
    {
        $groups = [];

        Course::query()
            ->where('publish', 1)
            ->with(['tags', 'category'])
            ->select(['id', 'title', 'english_title', 'meta_keywords'])
            ->chunkById(100, function ($courses) use (&$groups) {
                foreach ($courses as $course) {
                    $terms = $this->extractSynonymGroup(
                        title: $course->title,
                        englishTitle: $course->english_title,
                        metaKeywords: $course->meta_keywords,
                        tags: $course->tags->pluck('name')->all(),
                        categories: $course->category->pluck('title')->all(),
                    );

                    if ($terms !== []) {
                        $groups[] = $terms;
                    }
                }
            });

        Episode::query()
            ->where('publish', 1)
            ->with(['section.course'])
            ->select(['id', 'title', 'english_title', 'meta_keywords', 'section_id'])
            ->chunkById(100, function ($episodes) use (&$groups) {
                foreach ($episodes as $episode) {
                    $course = $episode->section?->course;

                    $terms = $this->extractSynonymGroup(
                        title: $episode->title,
                        englishTitle: $episode->english_title,
                        metaKeywords: $episode->meta_keywords,
                        tags: [],
                        categories: $course ? [$course->title, $course->english_title] : [],
                    );

                    if ($terms !== []) {
                        $groups[] = $terms;
                    }
                }
            });

        Question::query()
            ->where('publish', 1)
            ->with(['tags', 'category'])
            ->select(['id', 'subject', 'question', 'meta_keywords'])
            ->chunkById(100, function ($questions) use (&$groups) {
                foreach ($questions as $question) {
                    $terms = $this->extractSynonymGroup(
                        title: $question->subject,
                        englishTitle: null,
                        metaKeywords: $question->meta_keywords,
                        tags: $question->tags->pluck('name')->all(),
                        categories: array_filter([$question->category?->title]),
                    );

                    if ($terms !== []) {
                        $groups[] = $terms;
                    }
                }
            });

        $merged = $this->mergeOverlappingGroups($groups);
        $overrideGroups = array_values(config('search.synonym_overrides', []));

        return $this->toKeyedGroups([...$merged, ...$overrideGroups]);
    }

    /**
     * @param  list<list<string>>  $groups
     * @return list<list<string>>
     */
    private function mergeOverlappingGroups(array $groups): array
    {
        $merged = [];

        foreach ($groups as $group) {
            $normalizedGroup = array_map('mb_strtolower', $group);
            $matchIndex = null;

            foreach ($merged as $index => $existing) {
                $normalizedExisting = array_map('mb_strtolower', $existing);

                if ($this->groupsShouldMerge($normalizedGroup, $normalizedExisting)) {
                    $matchIndex = $index;
                    break;
                }
            }

            if ($matchIndex === null) {
                $merged[] = array_values(array_unique($group));
                continue;
            }

            $merged[$matchIndex] = array_values(array_unique([
                ...$merged[$matchIndex],
                ...$group,
            ]));
        }

        // یک پاس دیگر برای ادغام transitive (مثلاً a-b و b-c)
        for ($pass = 0; $pass < 3; $pass++) {
            $before = count($merged);
            $merged = $this->mergeOverlappingGroupsSinglePass($merged);
            if (count($merged) === $before) {
                break;
            }
        }

        return array_values(array_filter(
            $merged,
            fn (array $group) => count($group) >= 2
        ));
    }

    /**
     * @param  list<list<string>>  $groups
     * @return list<list<string>>
     */
    private function mergeOverlappingGroupsSinglePass(array $groups): array
    {
        $merged = [];

        foreach ($groups as $group) {
            $normalizedGroup = array_map('mb_strtolower', $group);
            $matchIndex = null;

            foreach ($merged as $index => $existing) {
                if ($this->groupsShouldMerge(
                    array_map('mb_strtolower', $group),
                    array_map('mb_strtolower', $existing)
                )) {
                    $matchIndex = $index;
                    break;
                }
            }

            if ($matchIndex === null) {
                $merged[] = $group;
            } else {
                $merged[$matchIndex] = array_values(array_unique([...$merged[$matchIndex], ...$group]));
            }
        }

        return $merged;
    }

    /**
     * @param  list<list<string>>  $groups
     * @return array<string, list<string>>
     */
    private function toKeyedGroups(array $groups): array
    {
        $keyed = [];

        foreach ($groups as $group) {
            $group = array_values(array_unique(array_filter($group)));
            if (count($group) < 2) {
                continue;
            }

            $key = $this->groupKey($group);
            if (isset($keyed[$key])) {
                $keyed[$key] = array_values(array_unique([...$keyed[$key], ...$group]));
                continue;
            }

            $keyed[$key] = $group;
        }

        return $keyed;
    }

    /**
     * @param  list<string>  $tags
     * @param  list<string>  $categories
     * @return list<string>
     */
    private function extractSynonymGroup(
        ?string $title,
        ?string $englishTitle,
        ?string $metaKeywords,
        array $tags,
        array $categories,
    ): array {
        $terms = [];

        foreach ([$title, $metaKeywords] as $text) {
            if (preg_match_all('/[\p{Latin}][\p{Latin}\p{N}._+\-]*/u', (string) $text, $matches)) {
                foreach ($matches[0] as $token) {
                    $terms[] = $token;
                    $terms[] = str_replace('.', '', $token);
                }
            }
        }

        if ($englishTitle) {
            $terms = [...$terms, ...$this->termExtractor->extractSlugParts($englishTitle)];
        }

        foreach ([...$tags, ...$categories] as $label) {
            $terms = [...$terms, ...$this->termExtractor->extractFromText($label)];
        }

        // اگر slug/latin داریم، واژه‌های فارسی عنوان را هم به گروه وصل کن (مثل «نکست» ↔ nextjs)
        $hasLatinOrSlug = collect($terms)->contains(fn (string $t) => preg_match('/\p{Latin}/u', $t) === 1);

        if ($hasLatinOrSlug && $title) {
            if (preg_match_all('/[\p{Arabic}][\p{Arabic}\p{N}]*/u', $title, $persianMatches)) {
                $terms = [...$terms, ...$persianMatches[0]];
            }
        }

        $terms = $this->termExtractor->uniqueTerms($terms);
        $terms = array_values(array_filter($terms, fn (string $term) => ! preg_match('/^\d+$/u', $term)));

        return count($terms) >= 2 ? $terms : [];
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private function groupsShouldMerge(array $a, array $b): bool
    {
        $shared = array_intersect($a, $b);

        foreach ($shared as $term) {
            if (preg_match('/\p{Latin}/u', $term) === 1 && mb_strlen($term) >= 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $group
     */
    private function groupKey(array $group): string
    {
        foreach ($group as $term) {
            if (preg_match('/\p{Latin}/u', $term) === 1) {
                $key = mb_strtolower(preg_replace('/[^a-z0-9]+/u', '', $term) ?? '');

                if ($key !== '') {
                    return $key;
                }
            }
        }

        return 'fa_'.substr(md5(implode('|', $group)), 0, 12);
    }
}
