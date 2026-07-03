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
            if (! empty($data['questions'])) {
                $this->syncQuestionsFromPayload($quiz, $data['questions']);
            } elseif (! empty($data['question_ids'])) {
                $this->syncQuestions($quiz, $data['question_ids']);
            }

            return $quiz->fresh(['questions']);
        });
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        return DB::transaction(function () use ($quiz, $data) {
            $quiz->update($this->mapQuizPayload($data, $quiz));
            if (array_key_exists('questions', $data)) {
                $this->syncQuestionsFromPayload($quiz, $data['questions'] ?? []);
            } elseif (array_key_exists('question_ids', $data)) {
                $this->syncQuestions($quiz, $data['question_ids'] ?? []);
            }

            return $quiz->fresh(['questions']);
        });
    }

    public function syncQuestions(Quiz $quiz, array $questionIds, array $scoresById = []): void
    {
        $sync = [];
        foreach (array_values($questionIds) as $position => $questionId) {
            $entry = ['position' => $position + 1];
            if (array_key_exists($questionId, $scoresById) && $scoresById[$questionId] !== null && $scoresById[$questionId] !== '') {
                $entry['score'] = $scoresById[$questionId];
            }
            $sync[$questionId] = $entry;
        }
        $quiz->questions()->sync($sync);
        $quiz->recalculateTotals();
    }

    public function syncQuestionsFromPayload(Quiz $quiz, array $questions): void
    {
        $ids = [];
        $scores = [];
        foreach ($questions as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $ids[] = $id;
            if (array_key_exists('score', $item) && $item['score'] !== null && $item['score'] !== '') {
                $scores[$id] = $item['score'];
            }
        }

        $this->syncQuestions($quiz, $ids, $scores);
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
            'description' => array_key_exists('description', $data) ? $data['description'] : $existing?->description,
            'instructions' => array_key_exists('instructions', $data) ? $data['instructions'] : $existing?->instructions,
            'passing_score' => array_key_exists('passing_score', $data) ? $data['passing_score'] : $existing?->passing_score,
            'passing_percentage' => array_key_exists('passing_percentage', $data) ? $data['passing_percentage'] : $existing?->passing_percentage,
            'time_limit' => array_key_exists('time_limit', $data) ? $data['time_limit'] : $existing?->time_limit,
            'max_attempts' => array_key_exists('max_attempts', $data) ? $data['max_attempts'] : $existing?->max_attempts,
            'randomize_questions' => $data['randomize_questions'] ?? $existing?->randomize_questions ?? false,
            'randomize_answers' => $data['randomize_answers'] ?? $existing?->randomize_answers ?? false,
            'result_display' => $data['result_display'] ?? $existing?->result_display ?? 'immediately',
            'negative_scoring' => $data['negative_scoring'] ?? $existing?->negative_scoring ?? false,
            'negative_scoring_factor' => $data['negative_scoring_factor'] ?? $existing?->negative_scoring_factor ?? 0,
            'start_at' => array_key_exists('start_at', $data) ? $data['start_at'] : $existing?->start_at,
            'end_at' => array_key_exists('end_at', $data) ? $data['end_at'] : $existing?->end_at,
            'manual_review_required' => $data['manual_review_required'] ?? $existing?->manual_review_required ?? false,
            'show_questions_in_result' => $showQuestionsInResult = ($data['show_questions_in_result'] ?? $existing?->show_questions_in_result ?? true),
            'show_correct_answers' => $showQuestionsInResult
                ? ($data['show_correct_answers'] ?? $existing?->show_correct_answers ?? true)
                : false,
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
