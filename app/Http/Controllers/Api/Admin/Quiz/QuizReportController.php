<?php

namespace App\Http\Controllers\Api\Admin\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizAttemptAnswer;
use App\Models\Section;
use App\Services\Quiz\QuizGradingService;
use App\Services\Quiz\QuizReportService;
use Illuminate\Http\Request;

class QuizReportController extends Controller
{
    public function __construct(
        protected QuizReportService $reports,
        protected QuizGradingService $grading
    ) {}

    /**
     * All quizzes attached to a course, its sections and its episodes,
     * each enriched with its report summary for side-by-side comparison.
     */
    public function courseOverview(Course $course)
    {
        $this->authorize('viewAny', Quiz::class);

        $sectionIds = $course->section()->pluck('id')->all();
        $episodeIds = $sectionIds
            ? Episode::whereIn('section_id', $sectionIds)->pluck('id')->all()
            : [];

        $scopes = [
            [Course::class, [$course->id]],
        ];
        if ($sectionIds) {
            $scopes[] = [Section::class, $sectionIds];
        }
        if ($episodeIds) {
            $scopes[] = [Episode::class, $episodeIds];
        }

        return response()->json([
            'message' => 'Success',
            'quizzes' => $this->mapQuizzes($this->quizzesForScopes($scopes)),
        ]);
    }

    public function sectionOverview(Section $section)
    {
        $this->authorize('viewAny', Quiz::class);

        $episodeIds = Episode::where('section_id', $section->id)->pluck('id')->all();
        $scopes = [
            [Section::class, [$section->id]],
        ];
        if ($episodeIds) {
            $scopes[] = [Episode::class, $episodeIds];
        }

        return response()->json([
            'message' => 'Success',
            'quizzes' => $this->mapQuizzes($this->quizzesForScopes($scopes)),
        ]);
    }

    public function episodeOverview(Episode $episode)
    {
        $this->authorize('viewAny', Quiz::class);

        $scopes = [
            [Episode::class, [$episode->id]],
        ];

        return response()->json([
            'message' => 'Success',
            'quizzes' => $this->mapQuizzes($this->quizzesForScopes($scopes)),
        ]);
    }

    protected function quizzesForScopes(array $scopes)
    {
        return Quiz::query()
            ->with('quizzable')
            ->withCount('attempts')
            ->where(function ($q) use ($scopes) {
                foreach ($scopes as [$type, $ids]) {
                    $q->orWhere(function ($sub) use ($type, $ids) {
                        $sub->where('quizzable_type', $type)
                            ->whereIn('quizzable_id', $ids);
                    });
                }
            })
            ->latest()
            ->get();
    }

    protected function mapQuizzes($quizzes)
    {
        return $quizzes->map(function (Quiz $quiz) {
            $type = class_basename($quiz->quizzable_type ?? '');

            return [
                'id' => $quiz->id,
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'is_published' => (bool) $quiz->is_published,
                'questions_count' => (int) $quiz->questions_count,
                'total_score' => (float) $quiz->total_score,
                'passing_percentage' => $quiz->passing_percentage,
                'passing_score' => $quiz->passing_score,
                'time_limit' => $quiz->time_limit,
                'start_at' => $quiz->start_at,
                'end_at' => $quiz->end_at,
                'is_available' => $quiz->isAvailable(),
                'manual_review_required' => (bool) $quiz->manual_review_required,
                'attached_type' => $type ?: 'standalone',
                'attached_title' => $quiz->quizzable->title ?? null,
                'summary' => $this->reports->summary($quiz),
            ];
        });
    }

    public function summary(Quiz $quiz)
    {
        $this->authorize('viewReports', $quiz);

        return response()->json([
            'message' => 'Success',
            'summary' => $this->reports->summary($quiz),
            'distribution' => $this->reports->scoreDistribution($quiz),
        ]);
    }

    public function passed(Quiz $quiz, Request $request)
    {
        $this->authorize('viewReports', $quiz);

        return response()->json([
            'message' => 'Success',
            'students' => $this->reports->passedStudents($quiz, (int) $request->input('perPage', 20)),
        ]);
    }

    public function failed(Quiz $quiz, Request $request)
    {
        $this->authorize('viewReports', $quiz);

        return response()->json([
            'message' => 'Success',
            'students' => $this->reports->failedStudents($quiz, (int) $request->input('perPage', 20)),
        ]);
    }

    public function reviewQueue(Quiz $quiz)
    {
        $this->authorize('viewReports', $quiz);

        $attempts = QuizAttempt::with(['user:id,first_name,last_name,username,profile_pic', 'answers.question'])
            ->where('quiz_id', $quiz->id)
            ->whereIn('status', ['grading', 'submitted'])
            ->where(function ($query) {
                $query->where('requires_manual_review', true)
                    ->orWhereHas('answers', fn ($q) => $q->where('needs_manual_review', true));
            })
            ->latest('submitted_at')
            ->paginate(20);

        return response()->json(['message' => 'Success', 'attempts' => $attempts]);
    }

    public function gradeAnswer(Request $request, QuizAttemptAnswer $answer)
    {
        $this->authorize('review', $answer->attempt);

        $data = $request->validate([
            'is_correct' => ['required', 'boolean'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
        ]);

        $this->grading->manualGradeAnswer(
            $answer,
            $data['is_correct'],
            $data['score'] ?? null,
            $data['comment'] ?? null
        );

        return response()->json(['message' => 'Answer graded', 'answer' => $answer->fresh()]);
    }

    public function gradeAnswers(Request $request, QuizAttempt $attempt)
    {
        $this->authorize('review', $attempt);

        $data = $request->validate([
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.answer_id' => ['required', 'integer', 'distinct'],
            'grades.*.is_correct' => ['required', 'boolean'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0'],
            'grades.*.comment' => ['nullable', 'string'],
        ]);

        try {
            $answers = $this->grading->manualGradeAnswers($attempt, $data['grades']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Answers graded',
            'answers' => $answers->values(),
            'attempt' => $attempt->fresh(['user:id,first_name,last_name,username,profile_pic']),
        ]);
    }

    public function completeReview(Request $request, QuizAttempt $attempt)
    {
        $this->authorize('review', $attempt);

        $attempt = $this->grading->completeManualReview($attempt, $request->user()->id);

        return response()->json(['message' => 'Review completed', 'attempt' => $attempt]);
    }
}
