<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'popular');
        $filter = $request->input('filter', 'all');
        $perPage = (int) $request->input('perPage', 12);
        $page = max(1, (int) $request->input('page', 1));

        $query = Tag::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('normalized', 'like', '%' . mb_strtolower($search) . '%');
            });
        }

        if ($filter === 'followed' && $user) {
            $followingIds = $this->followingTagIds($user);
            $query->whereIn('tag_id', $followingIds ?: [-1]);
        } elseif ($filter === 'with_questions') {
            $query->whereHas('questions', fn ($q) => $q->where('publish', 1));
        } elseif ($filter === 'with_courses') {
            $query->whereHas('courses');
        }

        $query->withCount([
            'followers',
            'questions as questions_count' => fn ($q) => $q->where('publish', 1),
            'courses as courses_count',
        ]);

        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            'followers' => $query->orderByDesc('followers_count'),
            default => $query->orderByDesc('questions_count'),
        };

        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $tags = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $followingIds = $this->followingTagIds($user);

        $items = $tags->map(function (Tag $tag) use ($followingIds) {
            return [
                'id' => $tag->tag_id,
                'name' => $tag->name,
                'slug' => $tag->normalized,
                'questions_count' => $tag->questions_count ?? 0,
                'courses_count' => $tag->courses_count ?? 0,
                'articles_count' => 0,
                'followers_count' => $tag->followers_count ?? 0,
                'is_following' => in_array($tag->tag_id, $followingIds, true),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'tags' => $items,
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $tag = Tag::findBySlug($slug);

        if (!$tag) {
            return response()->json(['message' => 'Tag not found.'], 404);
        }

        $user = auth('api')->user();

        return response()->json([
            'message' => 'Success',
            'tag' => $tag->toApiArray($user),
        ]);
    }

    public function content(Request $request, string $slug)
    {
        $tag = Tag::findBySlug($slug);

        if (!$tag) {
            return response()->json(['message' => 'Tag not found.'], 404);
        }

        $type = $request->input('type', 'questions');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('perPage', 10);
        $search = trim((string) $request->input('search', ''));
        $user = auth('api')->user();

        return match ($type) {
            'courses' => $this->coursesContent($tag, $page, $perPage, $search),
            'articles' => response()->json([
                'message' => 'Success',
                'items' => [],
                'pagination' => $this->emptyPagination($page, $perPage),
                'coming_soon' => true,
            ]),
            default => $this->questionsContent($tag, $user, $page, $perPage, $search),
        };
    }

    private function questionsContent(Tag $tag, ?User $user, int $page, int $perPage, string $search = '')
    {
        $query = $tag->questions()
            ->where('publish', 1)
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->withCount('answers')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('question', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $questions = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $items = $questions->map(function (Question $question) use ($user) {
            $tags = $question->tags->map(fn ($t) => [
                'id' => $t->tag_id,
                'name' => $t->name,
                'slug' => $t->normalized,
            ]);

            return [
                'id' => $question->id,
                'subject' => $question->subject,
                'slug' => $question->slug,
                'question' => $question->question,
                'best_answer' => $question->best_answer,
                'is_private' => $question->is_private,
                'created_at' => $question->created_at,
                'user' => $question->user,
                'total_answers' => $question->answers_count,
                'tags' => $tags,
                'bookmarked' => $user ? $user->hasBookmarked($question) : false,
                'is_editable' => $user ? $question->isEditableBy($user) : false,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ]);
    }

    private function coursesContent(Tag $tag, int $page, int $perPage, string $search = '')
    {
        $query = $tag->courses()
            ->select('courses.*')
            ->orderByDesc('courses.created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $courses = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $items = $courses->map(function (Course $course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'description' => $course->description,
                'status' => $course->status ?? null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ]);
    }

    private function emptyPagination(int $page, int $perPage): array
    {
        return [
            'total' => 0,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => 1,
        ];
    }

    private function followingTagIds(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return DB::table('followables')
            ->where('user_id', $user->id)
            ->where('followable_type', Tag::class)
            ->whereNotNull('accepted_at')
            ->pluck('followable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
