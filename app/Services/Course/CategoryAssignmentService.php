<?php

namespace App\Services\Course;

use App\Models\AutomationRule;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Support\Collection;

class CategoryAssignmentService
{
  /**
   * @param  array<int, array{field: string, operator: string, value: string}>|null  $rulesOverride
   */
  public function getMatchingCourses(Category $category, ?string $matchType = null, ?array $rulesOverride = null): Collection
  {
    $matchType = $matchType ?? $category->match_type ?? 'any';

    if ($rulesOverride !== null) {
      $rules = collect($rulesOverride)->map(fn (array $rule) => new AutomationRule($rule));
    } else {
      if (!$category->relationLoaded('automationRules')) {
        $category->load('automationRules');
      }
      $rules = $category->automationRules;
    }

    if ($rules->isEmpty()) {
      return collect();
    }

    $query = Course::query()->with(['status:id,title,english_title,slug', 'level:id,title,english_title,slug']);
    $phpFilteredRules = [];

    $sqlRules = $rules->where('field', '!=', 'totalTime');
    $phpRules = $rules->where('field', 'totalTime');

    $relationFields = ['status', 'level'];
    $relationRules = $sqlRules->filter(fn ($rule) => in_array($rule->field, $relationFields, true));
    $basicSqlRules = $sqlRules->reject(fn ($rule) => in_array($rule->field, $relationFields, true));

    if ($basicSqlRules->isNotEmpty()) {
      if ($matchType === 'all') {
        $query->where(function ($q) use ($basicSqlRules) {
          foreach ($basicSqlRules as $rule) {
            $q->where(function ($sub) use ($rule) {
              $this->applyBasicComparison($sub, $rule->field, $rule);
            });
          }
        });
      } else {
        $query->where(function ($q) use ($basicSqlRules) {
          foreach ($basicSqlRules as $rule) {
            $q->orWhere(function ($sub) use ($rule) {
              $this->applyBasicComparison($sub, $rule->field, $rule);
            });
          }
        });
      }
    }

    if ($relationRules->isNotEmpty()) {
      if ($matchType === 'all') {
        foreach ($relationRules as $rule) {
          $this->applyRelationComparison($query, $rule);
        }
      } else {
        $query->where(function ($q) use ($relationRules) {
          foreach ($relationRules as $rule) {
            $this->applyRelationComparison($q, $rule, true);
          }
        });
      }
    }

    $courses = $query->get();

    foreach ($phpRules as $rule) {
      $courses = $this->filterByTotalTime($courses, $rule, $matchType);
    }

    return $courses->values();
  }

  public function syncCategory(Category $category): void
  {
    if ($category->assignment_type !== 'automatic') {
      return;
    }

    $matchedIds = $this->getMatchingCourses($category)->pluck('id')->all();
    $category->course()->sync($matchedIds);
  }

  /**
   * @param  array<int>|null  $manualCategoryIds
   */
  public function syncCourseAutomaticCategories(Course $course, ?array $manualCategoryIds = null): void
  {
    $course->loadMissing(['status', 'level']);

    $automaticCategoryIds = Category::where('assignment_type', 'automatic')->pluck('id')->all();

    if ($manualCategoryIds === null) {
      $currentIds = $course->category()->pluck('categories.id')->all();
      $manualCategoryIds = array_values(array_diff($currentIds, $automaticCategoryIds));
    } else {
      $manualCategoryIds = array_values(array_diff($manualCategoryIds, $automaticCategoryIds));
    }

    $matchedAutomaticIds = Category::where('assignment_type', 'automatic')
      ->where('status', true)
      ->with('automationRules')
      ->get()
      ->filter(fn (Category $category) => $this->courseMatchesCategory($course, $category))
      ->pluck('id')
      ->all();

    $finalIds = array_values(array_unique(array_merge($manualCategoryIds, $matchedAutomaticIds)));
    $course->category()->sync($finalIds);
  }

  public function syncAllAutomaticCategories(): void
  {
    Category::where('assignment_type', 'automatic')
      ->with('automationRules')
      ->get()
      ->each(fn (Category $category) => $this->syncCategory($category));
  }

  public function courseMatchesCategory(Course $course, Category $category): bool
  {
    return $this->getMatchingCourses($category)->contains('id', $course->id);
  }

  protected function applyBasicComparison($query, string $column, AutomationRule $rule)
  {
    $value = $this->normalizeRuleValue($column, $rule->value);

    return match ($rule->operator) {
      'is_equal_to' => $query->where($column, '=', $value),
      'not_equal_to' => $query->where($column, '!=', $value),
      'less_than' => $query->where($column, '<', $value),
      'greater_than' => $query->where($column, '>', $value),
      'contains' => $query->where($column, 'LIKE', "%{$rule->value}%"),
      'not_contains' => $query->where($column, 'NOT LIKE', "%{$rule->value}%"),
      'starts_with' => $query->where($column, 'LIKE', "{$rule->value}%"),
      'ends_with' => $query->where($column, 'LIKE', "%{$rule->value}"),
      default => $query,
    };
  }

  protected function normalizeRuleValue(string $column, mixed $value): mixed
  {
    if ($column === 'publish') {
      if (in_array((string) $value, ['1', 'true', 'yes', 'بله', 'منتشر'], true)) {
        return true;
      }
      if (in_array((string) $value, ['0', 'false', 'no', 'خیر', 'پیش‌نویس'], true)) {
        return false;
      }
    }

    return $value;
  }

  protected function applyRelationComparison($query, AutomationRule $rule, bool $orGroup = false)
  {
    $relation = $rule->field;
    $columns = ['title', 'english_title', 'slug'];

    $clause = function ($relQ) use ($columns, $rule) {
      $first = true;
      foreach ($columns as $col) {
        $method = $first ? 'where' : 'orWhere';
        $first = false;
        match ($rule->operator) {
          'is_equal_to' => $relQ->{$method}($col, '=', $rule->value),
          'not_equal_to' => $relQ->{$method}($col, '!=', $rule->value),
          'less_than' => $relQ->{$method}($col, '<', $rule->value),
          'greater_than' => $relQ->{$method}($col, '>', $rule->value),
          'contains' => $relQ->{$method}($col, 'LIKE', "%{$rule->value}%"),
          'not_contains' => $relQ->{$method}($col, 'NOT LIKE', "%{$rule->value}%"),
          'starts_with' => $relQ->{$method}($col, 'LIKE', "{$rule->value}%"),
          'ends_with' => $relQ->{$method}($col, 'LIKE', "%{$rule->value}"),
          default => null,
        };
      }
    };

    if ($orGroup) {
      return $query->orWhereHas($relation, $clause);
    }

    return $query->whereHas($relation, $clause);
  }

  protected function filterByTotalTime(Collection $courses, AutomationRule $rule, string $matchType): Collection
  {
    return $courses->filter(function ($course) use ($rule) {
      $value = $course->totalTime() ?? 0;
      $target = intval($rule->value);

      return match ($rule->operator) {
        'is_equal_to' => $value == $target,
        'not_equal_to' => $value != $target,
        'less_than' => $value < $target,
        'greater_than' => $value > $target,
        default => true,
      };
    })->values();
  }
}
