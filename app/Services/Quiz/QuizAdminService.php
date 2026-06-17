<?php

namespace App\Services\Quiz;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizQuestion;
use App\Models\Quiz\QuizQuestionCategory;
use App\Models\Quiz\QuizTag;
use App\Support\Quiz\QuizConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizAdminService
{
    public function createQuiz(array $data): Quiz
    {
        return DB::transaction(function () use ($data) {
            $quiz = Quiz::create($this->mapQuizPayload($data));
            if (! empty($data['question_ids'])) {
                $this->syncQuestions($quiz, $data['question_ids']);
            }

            return $quiz->fresh(['questions']);
        });
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        return DB::transaction(function () use ($quiz, $data) {
            $quiz->update($this->mapQuizPayload($data, $quiz));
            if (array_key_exists('question_ids', $data)) {
                $this->syncQuestions($quiz, $data['question_ids'] ?? []);
            }

            return $quiz->fresh(['questions']);
        });
    }

    public function syncQuestions(Quiz $quiz, array $questionIds): void
    {
        $sync = [];
        foreach (array_values($questionIds) as $position => $questionId) {
            $sync[$questionId] = ['position' => $position + 1];
        }
        $quiz->questions()->sync($sync);
        $quiz->recalculateTotals();
    }

    public function createQuestion(array $data): QuizQuestion
    {
        return DB::transaction(function () use ($data) {
            $question = QuizQuestion::create([
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'],
                'text' => $data['text'],
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'] ?? 'medium',
                'default_score' => $data['default_score'] ?? 1,
                'settings' => $data['settings'] ?? null,
                'requires_manual_review' => $data['requires_manual_review'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $this->syncOptions($question, $data['options'] ?? []);
            $this->syncTags($question, $data['tag_ids'] ?? [], $data['tags'] ?? []);

            return $question->fresh(['options', 'tags', 'category']);
        });
    }

    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($question, $data) {
            $question->update(array_filter([
                'category_id' => $data['category_id'] ?? $question->category_id,
                'type' => $data['type'] ?? $question->type,
                'text' => $data['text'] ?? $question->text,
                'explanation' => $data['explanation'] ?? $question->explanation,
                'difficulty' => $data['difficulty'] ?? $question->difficulty,
                'default_score' => $data['default_score'] ?? $question->default_score,
                'settings' => $data['settings'] ?? $question->settings,
                'requires_manual_review' => $data['requires_manual_review'] ?? $question->requires_manual_review,
                'is_active' => $data['is_active'] ?? $question->is_active,
            ], fn ($v) => $v !== null));

            if (array_key_exists('options', $data)) {
                $question->options()->delete();
                $this->syncOptions($question, $data['options']);
            }

            if (array_key_exists('tag_ids', $data) || array_key_exists('tags', $data)) {
                $this->syncTags($question, $data['tag_ids'] ?? [], $data['tags'] ?? []);
            }

            return $question->fresh(['options', 'tags', 'category']);
        });
    }

    protected function syncOptions(QuizQuestion $question, array $options): void
    {
        foreach ($options as $i => $opt) {
            $question->options()->create([
                'text' => $opt['text'] ?? null,
                'is_correct' => $opt['is_correct'] ?? false,
                'blank_index' => $opt['blank_index'] ?? null,
                'match_key' => $opt['match_key'] ?? null,
                'match_value' => $opt['match_value'] ?? null,
                'correct_position' => $opt['correct_position'] ?? null,
                'feedback' => $opt['feedback'] ?? null,
                'position' => $opt['position'] ?? $i,
            ]);
        }
    }

    protected function syncTags(QuizQuestion $question, array $tagIds, array $tagNames): void
    {
        foreach ($tagNames as $name) {
            $tag = QuizTag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }
        $question->tags()->sync(array_unique($tagIds));
    }

    protected function mapQuizPayload(array $data, ?Quiz $existing = null): array
    {
        $payload = [
            'quizzable_type' => $data['quizzable_type'] ?? $existing?->quizzable_type,
            'quizzable_id' => $data['quizzable_id'] ?? $existing?->quizzable_id,
            'title' => $data['title'] ?? $existing?->title,
            'slug' => $data['slug'] ?? $existing?->slug,
            'description' => $data['description'] ?? $existing?->description,
            'passing_score' => $data['passing_score'] ?? $existing?->passing_score,
            'passing_percentage' => $data['passing_percentage'] ?? $existing?->passing_percentage,
            'time_limit' => $data['time_limit'] ?? $existing?->time_limit,
            'max_attempts' => array_key_exists('max_attempts', $data) ? $data['max_attempts'] : $existing?->max_attempts,
            'randomize_questions' => $data['randomize_questions'] ?? $existing?->randomize_questions ?? false,
            'randomize_answers' => $data['randomize_answers'] ?? $existing?->randomize_answers ?? false,
            'result_display' => $data['result_display'] ?? $existing?->result_display ?? 'immediately',
            'negative_scoring' => $data['negative_scoring'] ?? $existing?->negative_scoring ?? false,
            'negative_scoring_factor' => $data['negative_scoring_factor'] ?? $existing?->negative_scoring_factor ?? 0,
            'start_at' => $data['start_at'] ?? $existing?->start_at,
            'end_at' => $data['end_at'] ?? $existing?->end_at,
            'manual_review_required' => $data['manual_review_required'] ?? $existing?->manual_review_required ?? false,
            'show_correct_answers' => $data['show_correct_answers'] ?? $existing?->show_correct_answers ?? true,
            'is_published' => $data['is_published'] ?? $existing?->is_published ?? false,
            'settings' => $data['settings'] ?? $existing?->settings,
            'created_by' => $data['created_by'] ?? $existing?->created_by,
        ];

        if (isset($data['quizzable']) && is_array($data['quizzable'])) {
            $type = QuizConstants::QUIZZABLE_TYPES[$data['quizzable']['type']] ?? null;
            $payload['quizzable_type'] = $type;
            $payload['quizzable_id'] = $data['quizzable']['id'] ?? null;
        }

        return $payload;
    }

    public function upsertCategory(array $data): QuizQuestionCategory
    {
        return QuizQuestionCategory::updateOrCreate(
            ['slug' => $data['slug'] ?? Str::slug($data['name'])],
            [
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]
        );
    }
}
