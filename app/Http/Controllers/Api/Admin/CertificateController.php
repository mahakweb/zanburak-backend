<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\Certificate\CertificateIssuanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateIssuanceService $issuance,
    ) {}
    /**
     * Get all certificates with filters and pagination
     */
    public function certificates(Request $request)
    {
        $query = Certificate::with([
            'user:id,first_name,last_name,username,email,profile_pic',
            'course:id,title,english_title,slug,poster',
        ]);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('issued')) {
            if ($request->issued === 'yes') {
                $query->whereNotNull('issued_at');
            } elseif ($request->issued === 'no') {
                $query->whereNull('issued_at');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('issued_date_from')) {
            $query->whereDate('issued_at', '>=', $request->issued_date_from);
        }

        if ($request->filled('issued_date_to')) {
            $query->whereDate('issued_at', '<=', $request->issued_date_to);
        }

        if ($request->filled('time_completed_min')) {
            $query->where('time_completed', '>=', $request->time_completed_min);
        }

        if ($request->filled('time_completed_max')) {
            $query->where('time_completed', '<=', $request->time_completed_max);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('course_title', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('course', function($courseQuery) use ($search) {
                      $courseQuery->where('title', 'like', "%{$search}%")
                                 ->orWhere('english_title', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'issued_newest':
                $query->orderBy('issued_at', 'desc');
                break;
            case 'issued_oldest':
                $query->orderBy('issued_at', 'asc');
                break;
            case 'user_name':
                $query->orderBy('user_name', 'asc');
                break;
            case 'course_title':
                $query->orderBy('course_title', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = $request->input('perPage', 20);
        $certificates = $query->paginate($perPage);

        $data = collect($certificates->items())->map(function ($certificate) {
            return [
                'id' => $certificate->id,
                'uuid' => $certificate->uuid,
                'serial_number' => $certificate->serial_number,
                'status' => $certificate->status,
                'user_name' => $certificate->user_name,
                'course_title' => $certificate->course_title,
                'time_completed' => $certificate->time_completed,
                'issued_at' => $certificate->issued_at,
                'created_at' => $certificate->created_at,
                'updated_at' => $certificate->updated_at,
                
                'user' => $certificate->user ? [
                    'id' => $certificate->user->id,
                    'first_name' => $certificate->user->first_name,
                    'last_name' => $certificate->user->last_name,
                    'username' => $certificate->user->username,
                    'email' => $certificate->user->email,
                    'profile_pic' => $certificate->user->profile_pic,
                ] : null,

                'course' => $certificate->course ? [
                    'id' => $certificate->course->id,
                    'title' => $certificate->course->title,
                    'english_title' => $certificate->course->english_title,
                    'slug' => $certificate->course->slug,
                    'poster' => $certificate->course->poster,
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'certificates' => $data,
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
                'from' => $certificates->firstItem(),
                'to' => $certificates->lastItem(),
            ]
        ], 200);
    }

    /**
     * Get certificate details with all related data
     */
    public function certificateDetails(Request $request, $uuid)
    {
        $certificate = Certificate::with([
            'user:id,first_name,last_name,username,email,profile_pic,mobile,created_at',
            'course:id,title,english_title,slug,poster,description,teacher_id',
            'course.teacher:id,first_name,last_name,username,profile_pic',
        ])->where('uuid', $uuid)->firstOrFail();

        $certificateData = [
            'id' => $certificate->id,
            'uuid' => $certificate->uuid,
            'user_name' => $certificate->user_name,
            'course_title' => $certificate->course_title,
            'time_completed' => $certificate->time_completed,
            'issued_at' => $certificate->issued_at,
            'created_at' => $certificate->created_at,
            'updated_at' => $certificate->updated_at,

            'user' => $certificate->user ? [
                'id' => $certificate->user->id,
                'first_name' => $certificate->user->first_name,
                'last_name' => $certificate->user->last_name,
                'username' => $certificate->user->username,
                'email' => $certificate->user->email,
                'mobile' => $certificate->user->mobile,
                'profile_pic' => $certificate->user->profile_pic,
                'created_at' => $certificate->user->created_at,
            ] : null,

            'course' => $certificate->course ? [
                'id' => $certificate->course->id,
                'title' => $certificate->course->title,
                'english_title' => $certificate->course->english_title,
                'slug' => $certificate->course->slug,
                'poster' => $certificate->course->poster,
                'description' => $certificate->course->description,
                'teacher' => $certificate->course->teacher ? [
                    'id' => $certificate->course->teacher->id,
                    'first_name' => $certificate->course->teacher->first_name,
                    'last_name' => $certificate->course->teacher->last_name,
                    'username' => $certificate->course->teacher->username,
                    'profile_pic' => $certificate->course->teacher->profile_pic,
                ] : null,
            ] : null,
        ];

        return response()->json([
            'message' => 'Success',
            'certificate' => $certificateData
        ], 200);
    }

    /**
     * Create/Issue a new certificate
     */
    public function createCertificate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'user_name' => 'nullable|string|max:255',
            'course_title' => 'nullable|string|max:255',
            'time_completed' => 'nullable|integer|min:0',
            'grade' => 'nullable|numeric|min:0|max:100',
            'issued_at' => 'nullable|date',
            'auto_issue' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            $course = Course::findOrFail($request->course_id);

            if ($request->boolean('auto_issue', true)) {
                $certificate = $this->issuance->issueManually(
                    $user,
                    $course,
                    $request->input('grade'),
                    $request->input('time_completed'),
                );

                if ($request->filled('user_name')) {
                    $certificate->update(['user_name' => $request->user_name]);
                }
                if ($request->filled('course_title')) {
                    $certificate->update(['course_title' => $request->course_title]);
                }
                if ($request->filled('issued_at')) {
                    $certificate->update(['issued_at' => $request->issued_at]);
                }
            } else {
                $existingCertificate = Certificate::where('user_id', $request->user_id)
                    ->where('course_id', $request->course_id)
                    ->where('status', '!=', 'revoked')
                    ->first();

                if ($existingCertificate) {
                    return response()->json([
                        'message' => 'گواهینامه برای این کاربر و دوره قبلاً صادر شده است',
                        'errors' => [
                            'certificate' => ['گواهینامه برای این کاربر و دوره قبلاً صادر شده است'],
                        ],
                    ], 422);
                }

                $userName = $request->user_name ?? trim($user->first_name.' '.$user->last_name);
                $courseTitle = $request->course_title ?? $course->title;

                $certificate = Certificate::create([
                    'user_id' => $request->user_id,
                    'course_id' => $request->course_id,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'user_name' => $userName,
                    'course_title' => $courseTitle,
                    'time_completed' => $request->time_completed ?? null,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'گواهینامه با موفقیت صادر شد',
                'certificate' => $certificate->fresh(['user', 'course', 'template']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'خطا در صدور گواهینامه',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update certificate
     */
    public function updateCertificate(Request $request, $uuid)
    {
        $request->validate([
            'user_name' => 'nullable|string|max:255',
            'course_title' => 'nullable|string|max:255',
            'time_completed' => 'nullable|integer|min:0',
            'issued_at' => 'nullable|date',
        ]);

        $certificate = Certificate::where('uuid', $uuid)->firstOrFail();

        $updateData = [];
        
        if ($request->filled('user_name')) {
            $updateData['user_name'] = $request->user_name;
        }

        if ($request->filled('course_title')) {
            $updateData['course_title'] = $request->course_title;
        }

        if ($request->has('time_completed')) {
            $updateData['time_completed'] = $request->time_completed;
        }

        if ($request->has('issued_at')) {
            $updateData['issued_at'] = $request->issued_at ? $request->issued_at : null;
        }

        $certificate->update($updateData);

        return response()->json([
            'message' => 'گواهینامه با موفقیت به‌روزرسانی شد',
            'certificate' => $certificate->fresh(['user', 'course'])
        ], 200);
    }

    /**
     * Issue certificate (set issued_at)
     */
    public function issueCertificate(Request $request, $uuid)
    {
        $request->validate([
            'issued_at' => 'nullable|date',
        ]);

        $certificate = Certificate::with(['user', 'course'])->where('uuid', $uuid)->firstOrFail();

        $certificate = $this->issuance->finalizeIssuance(
            $certificate,
            $certificate->course,
            $certificate->grade,
            $certificate->time_completed,
        );

        if ($request->filled('issued_at')) {
            $certificate->update(['issued_at' => $request->issued_at]);
        }

        return response()->json([
            'message' => 'گواهینامه با موفقیت صادر شد',
            'certificate' => $certificate->fresh(['user', 'course', 'template']),
        ], 200);
    }

    /**
     * Delete certificate
     */
    public function deleteCertificate(Request $request, $uuid)
    {
        $request->validate([
            'force_delete' => 'nullable|boolean',
        ]);

        $certificate = Certificate::where('uuid', $uuid)->firstOrFail();

        $forceDelete = $request->boolean('force_delete', false);

        if ($forceDelete) {
            $certificate->delete();
            $message = 'گواهینامه به طور کامل حذف شد';
        } else {
            // Soft delete - just remove issued_at
            $certificate->update(['issued_at' => null]);
            $message = 'گواهینامه لغو شد';
        }

        return response()->json([
            'message' => $message
        ], 200);
    }

    /**
     * Get certificate statistics
     */
    public function certificateStats(Request $request)
    {
        // Get all certificates (no date filter by default, or use provided dates)
        $query = Certificate::query();
        
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $stats = [
            'total_certificates' => (clone $query)->count(),
            'issued_certificates' => (clone $query)
                ->whereNotNull('issued_at')
                ->count(),
            'pending_certificates' => (clone $query)
                ->whereNull('issued_at')
                ->count(),
            'total_users_with_certificates' => (clone $query)
                ->distinct('user_id')
                ->count('user_id'),
            'total_courses_with_certificates' => (clone $query)
                ->distinct('course_id')
                ->count('course_id'),
        ];

        // Daily certificates for chart
        $dailyQuery = Certificate::query();
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dailyQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $dailyCertificates = $dailyQuery
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $stats['daily_certificates'] = $dailyCertificates;

        // Certificates by course
        $courseQuery = Certificate::query();
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $courseQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $certificatesByCourse = $courseQuery
            ->with('course:id,title')
            ->select('course_id', DB::raw('count(*) as count'))
            ->groupBy('course_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'course_id' => $item->course_id,
                    'course_title' => $item->course ? $item->course->title : 'Unknown',
                    'count' => $item->count,
                ];
            });

        $stats['certificates_by_course'] = $certificatesByCourse;

        return response()->json([
            'message' => 'Success',
            'stats' => $stats
        ], 200);
    }

    /**
     * Export certificates to CSV
     */
    public function exportCertificates(Request $request)
    {
        $query = Certificate::with(['user', 'course']);

        // Apply same filters as certificates method
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('issued')) {
            if ($request->issued === 'yes') {
                $query->whereNotNull('issued_at');
            } elseif ($request->issued === 'no') {
                $query->whereNull('issued_at');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('course_title', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $certificates = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'UUID', 'User Name', 'User Email', 'Course Title', 'Time Completed', 
            'Issued At', 'Created At'
        ];

        foreach ($certificates as $certificate) {
            $csvData[] = [
                $certificate->id,
                $certificate->uuid,
                $certificate->user_name,
                $certificate->user ? $certificate->user->email : 'N/A',
                $certificate->course_title,
                $certificate->time_completed ?? 'N/A',
                $certificate->issued_at ? $certificate->issued_at->format('Y-m-d H:i:s') : 'N/A',
                $certificate->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'certificates_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get users for certificate creation
     */
    public function getUsers(Request $request)
    {
        $query = User::select('id', 'first_name', 'last_name', 'username', 'email', 'profile_pic');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->limit(20)->get();

        return response()->json([
            'message' => 'Success',
            'users' => $users
        ], 200);
    }

    /**
     * Get courses for certificate creation
     */
    public function getCourses(Request $request)
    {
        $query = Course::select('id', 'title', 'english_title', 'slug', 'poster')
            ->where('publish', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('english_title', 'like', "%{$search}%");
            });
        }

        $courses = $query->limit(20)->get();

        return response()->json([
            'message' => 'Success',
            'courses' => $courses
        ], 200);
    }

    /**
     * Universal search for certificate creation (users, courses)
     */
    public function search(Request $request)
    {
        $search = $request->input('search', '');
        $type = $request->input('type', 'user'); // user, course
        
        if (strlen($search) < 2) {
            return response()->json([
                'message' => 'Search term too short',
                'results' => []
            ], 200);
        }

        $results = [];

        switch ($type) {
            case 'user':
                $users = User::select('id', 'first_name', 'last_name', 'username', 'email', 'profile_pic')
                    ->where(function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('username', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
                $results['users'] = $users;
                break;

            case 'course':
                $courses = Course::select('id', 'title', 'english_title', 'slug', 'poster')
                    ->where('publish', 1)
                    ->where(function($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('english_title', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
                $results['courses'] = $courses;
                break;
        }

        return response()->json([
            'message' => 'Success',
            'results' => $results
        ], 200);
    }
}

