<?php

namespace App\Http\Controllers\Api\Admin\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizQuestion;
use App\Models\Section;
use Illuminate\Http\Request;

class QuizSearchController extends Controller
{
    public function courses(Request $request)
    {
        $this->authorize('viewAny', Quiz::class);

        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('perPage', 10);

        $items = Course::query()
            ->select(['id', 'title', 'english_title', 'slug'])
            ->when($q !== '', fn ($builder) => $builder->where(function ($s) use ($q) {
                $s->where('title', 'like', "%{$q}%")
                    ->orWhere('english_title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->items();

        return response()->json([
            'message' => 'Success',
            'items' => collect($items)->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title ?: $c->english_title,
            ])->all(),
        ]);
    }

    public function sections(Request $request)
    {
        $this->authorize('viewAny', Quiz::class);

        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('perPage', 10);

        $items = Section::query()
            ->with('course:id,title')
            ->select(['id', 'course_id', 'title'])
            ->when($q !== '', fn ($builder) => $builder->where('title', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->items();

        return response()->json([
            'message' => 'Success',
            'items' => collect($items)->map(fn ($s) => [
                'id' => $s->id,
                'title' => trim(($s->course->title ?? '').' › '.$s->title, ' ›'),
            ])->all(),
        ]);
    }

    public function episodes(Request $request)
    {
        $this->authorize('viewAny', Quiz::class);

        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('perPage', 10);

        $items = Episode::query()
            ->with('section:id,title,course_id', 'section.course:id,title')
            ->select(['id', 'section_id', 'title', 'english_title'])
            ->when($q !== '', fn ($builder) => $builder->where(function ($s) use ($q) {
                $s->where('title', 'like', "%{$q}%")
                    ->orWhere('english_title', 'like', "%{$q}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->items();

        return response()->json([
            'message' => 'Success',
            'items' => collect($items)->map(function ($e) {
                $course = $e->section->course->title ?? '';
                $label = $e->title ?: $e->english_title;

                return [
                    'id' => $e->id,
                    'title' => trim(($course ? $course.' › ' : '').$label),
                ];
            })->all(),
        ]);
    }

    public function questions(Request $request)
    {
        $this->authorize('viewAny', Quiz::class);

        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('perPage', 15);

        $items = QuizQuestion::query()
            ->select(['id', 'text', 'type', 'difficulty', 'default_score'])
            ->when($q !== '', fn ($builder) => $builder->where('text', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->items();

        $typeLabels = [
            'single_choice' => 'تک‌گزینه‌ای',
            'multiple_choice' => 'چندگزینه‌ای',
            'true_false' => 'درست/غلط',
            'short_answer' => 'پاسخ کوتاه',
            'long_answer' => 'تشریحی',
            'fill_blank' => 'جای خالی',
            'matching' => 'تطبیق',
            'ordering' => 'مرتب‌سازی',
        ];

        return response()->json([
            'message' => 'Success',
            'items' => collect($items)->map(function ($question) use ($typeLabels) {
                $text = mb_strlen($question->text) > 70
                    ? mb_substr($question->text, 0, 70).'…'
                    : $question->text;

                return [
                    'id' => $question->id,
                    'text' => $question->text,
                    'title' => '#'.$question->id.' · ['.($typeLabels[$question->type] ?? $question->type).'] '.$text,
                    'type' => $question->type,
                    'difficulty' => $question->difficulty,
                    'default_score' => $question->default_score,
                ];
            })->all(),
        ]);
    }
}
