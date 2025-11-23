<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get all reports with filters and pagination
     */
    public function reports(Request $request)
    {
        $query = Report::with([
            'user:id,first_name,last_name,username,email,profile_pic'
        ]);

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('status', 0);
            } elseif ($request->status === 'resolved') {
                $query->where('status', 1);
            }
        }

        // Filter by reportable type
        if ($request->filled('type')) {
            $type = $request->type;
            $modelClass = "App\\Models\\" . ucfirst($type);
            if (class_exists($modelClass)) {
                // Check both short name and full class name
                $query->where(function($q) use ($type, $modelClass) {
                    $q->where('reportable_type', $type)
                      ->orWhere('reportable_type', $modelClass);
                });
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Advanced Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Search in report text
                $q->where('report', 'like', "%{$search}%")
                  // Search in report ID
                  ->orWhere('id', 'like', "%{$search}%")
                  // Search in user who reported (reporter)
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%")
                               ->orWhere('mobile', 'like', "%{$search}%");
                  })
                  // Search in Question content and owner
                  ->orWhere(function($subQuery) use ($search) {
                      $subQuery->where(function($typeQuery) {
                          $typeQuery->where('reportable_type', 'question')
                                    ->orWhere('reportable_type', 'App\\Models\\Question');
                      })
                      ->whereExists(function($existsQuery) use ($search) {
                          $existsQuery->select(DB::raw(1))
                                      ->from('questions')
                                      ->whereColumn('questions.id', 'reports.reportable_id')
                                      ->where(function($contentQuery) use ($search) {
                                          $contentQuery->where('subject', 'like', "%{$search}%")
                                                      ->orWhere('question', 'like', "%{$search}%")
                                                      ->orWhere('id', 'like', "%{$search}%")
                                                      ->orWhereExists(function($userExistsQuery) use ($search) {
                                                          $userExistsQuery->select(DB::raw(1))
                                                                          ->from('users')
                                                                          ->whereColumn('users.id', 'questions.user_id')
                                                                          ->where(function($userQuery) use ($search) {
                                                                              $userQuery->where('first_name', 'like', "%{$search}%")
                                                                                       ->orWhere('last_name', 'like', "%{$search}%")
                                                                                       ->orWhere('username', 'like', "%{$search}%")
                                                                                       ->orWhere('email', 'like', "%{$search}%");
                                                                          });
                                                      });
                                      });
                      });
                  })
                  // Search in Answer content and owner
                  ->orWhere(function($subQuery) use ($search) {
                      $subQuery->where(function($typeQuery) {
                          $typeQuery->where('reportable_type', 'answer')
                                    ->orWhere('reportable_type', 'App\\Models\\Answer');
                      })
                      ->whereExists(function($existsQuery) use ($search) {
                          $existsQuery->select(DB::raw(1))
                                      ->from('answers')
                                      ->whereColumn('answers.id', 'reports.reportable_id')
                                      ->where(function($contentQuery) use ($search) {
                                          $contentQuery->where('answer', 'like', "%{$search}%")
                                                      ->orWhere('id', 'like', "%{$search}%")
                                                      ->orWhereExists(function($userExistsQuery) use ($search) {
                                                          $userExistsQuery->select(DB::raw(1))
                                                                          ->from('users')
                                                                          ->whereColumn('users.id', 'answers.user_id')
                                                                          ->where(function($userQuery) use ($search) {
                                                                              $userQuery->where('first_name', 'like', "%{$search}%")
                                                                                       ->orWhere('last_name', 'like', "%{$search}%")
                                                                                       ->orWhere('username', 'like', "%{$search}%")
                                                                                       ->orWhere('email', 'like', "%{$search}%");
                                                                          });
                                                      });
                                      });
                      });
                  })
                  // Search in Comment content and owner
                  ->orWhere(function($subQuery) use ($search) {
                      $subQuery->where(function($typeQuery) {
                          $typeQuery->where('reportable_type', 'comment')
                                    ->orWhere('reportable_type', 'App\\Models\\Comment');
                      })
                      ->whereExists(function($existsQuery) use ($search) {
                          $existsQuery->select(DB::raw(1))
                                      ->from('comments')
                                      ->whereColumn('comments.id', 'reports.reportable_id')
                                      ->where(function($contentQuery) use ($search) {
                                          $contentQuery->where('comment', 'like', "%{$search}%")
                                                      ->orWhere('id', 'like', "%{$search}%")
                                                      ->orWhereExists(function($userExistsQuery) use ($search) {
                                                          $userExistsQuery->select(DB::raw(1))
                                                                          ->from('users')
                                                                          ->whereColumn('users.id', 'comments.user_id')
                                                                          ->where(function($userQuery) use ($search) {
                                                                              $userQuery->where('first_name', 'like', "%{$search}%")
                                                                                       ->orWhere('last_name', 'like', "%{$search}%")
                                                                                       ->orWhere('username', 'like', "%{$search}%")
                                                                                       ->orWhere('email', 'like', "%{$search}%");
                                                                          });
                                                      });
                                      });
                      });
                  })
                  // Search in Course content and teacher
                  ->orWhere(function($subQuery) use ($search) {
                      $subQuery->where(function($typeQuery) {
                          $typeQuery->where('reportable_type', 'course')
                                    ->orWhere('reportable_type', 'App\\Models\\Course');
                      })
                      ->whereExists(function($existsQuery) use ($search) {
                          $existsQuery->select(DB::raw(1))
                                      ->from('courses')
                                      ->whereColumn('courses.id', 'reports.reportable_id')
                                      ->where(function($contentQuery) use ($search) {
                                          $contentQuery->where('title', 'like', "%{$search}%")
                                                      ->orWhere('english_title', 'like', "%{$search}%")
                                                      ->orWhere('slug', 'like', "%{$search}%")
                                                      ->orWhere('id', 'like', "%{$search}%")
                                                      ->orWhereExists(function($teacherExistsQuery) use ($search) {
                                                          $teacherExistsQuery->select(DB::raw(1))
                                                                             ->from('users')
                                                                             ->whereColumn('users.id', 'courses.teacher_id')
                                                                             ->where(function($userQuery) use ($search) {
                                                                                 $userQuery->where('first_name', 'like', "%{$search}%")
                                                                                          ->orWhere('last_name', 'like', "%{$search}%")
                                                                                          ->orWhere('username', 'like', "%{$search}%")
                                                                                          ->orWhere('email', 'like', "%{$search}%");
                                                                             });
                                                      });
                                      });
                      });
                  })
                  // Search in Episode content
                  ->orWhere(function($subQuery) use ($search) {
                      $subQuery->where(function($typeQuery) {
                          $typeQuery->where('reportable_type', 'episode')
                                    ->orWhere('reportable_type', 'App\\Models\\Episode');
                      })
                      ->whereExists(function($existsQuery) use ($search) {
                          $existsQuery->select(DB::raw(1))
                                      ->from('episodes')
                                      ->whereColumn('episodes.id', 'reports.reportable_id')
                                      ->where(function($contentQuery) use ($search) {
                                          $contentQuery->where('title', 'like', "%{$search}%")
                                                      ->orWhere('english_title', 'like', "%{$search}%")
                                                      ->orWhere('description', 'like', "%{$search}%")
                                                      ->orWhere('id', 'like', "%{$search}%");
                                      });
                      });
                  })
                  // Search in Path content
                  ->orWhere(function($subQuery) use ($search) {
                      $subQuery->where(function($typeQuery) {
                          $typeQuery->where('reportable_type', 'path')
                                    ->orWhere('reportable_type', 'App\\Models\\Path');
                      })
                      ->whereExists(function($existsQuery) use ($search) {
                          $existsQuery->select(DB::raw(1))
                                      ->from('paths')
                                      ->whereColumn('paths.id', 'reports.reportable_id')
                                      ->where(function($contentQuery) use ($search) {
                                          $contentQuery->where('title', 'like', "%{$search}%")
                                                      ->orWhere('english_title', 'like', "%{$search}%")
                                                      ->orWhere('slug', 'like', "%{$search}%")
                                                      ->orWhere('id', 'like', "%{$search}%");
                                      });
                      });
                  });
            });
        }

        // Apply sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = $request->input('perPage', 20);
        $reports = $query->paginate($perPage);

        // Normalize reportable_type and reload relations
        $reports->getCollection()->each(function ($report) {
            $report->normalizeReportableType();
            // Reload the relation with normalized type
            $report->load('reportable');
        });

        // Transform the data to include reportable details
        $reports->getCollection()->transform(function ($report) {
            $reportable = $report->reportable;
            $reportableData = null;

            if ($reportable) {
                // Get the base class name for comparison
                $type = class_basename($report->reportable_type);
                if (empty($type)) {
                    $type = $report->reportable_type;
                }
                
                switch (strtolower($type)) {
                    case 'question':
                        $reportableData = [
                            'id' => $reportable->id,
                            'subject' => $reportable->subject,
                            'question' => $reportable->question,
                            'publish' => $reportable->publish,
                            'slug' => $reportable->slug ?? null,
                            'user' => $reportable->user ? [
                                'id' => $reportable->user->id,
                                'name' => $reportable->user->first_name . ' ' . $reportable->user->last_name,
                                'username' => $reportable->user->username,
                            ] : null,
                        ];
                        break;
                    case 'answer':
                        $reportableData = [
                            'id' => $reportable->id,
                            'answer' => $reportable->answer,
                            'publish' => $reportable->publish,
                            'question_id' => $reportable->question_id,
                            'question' => $reportable->question ? [
                                'id' => $reportable->question->id,
                                'subject' => $reportable->question->subject,
                                'slug' => $reportable->question->slug ?? null,
                            ] : null,
                            'user' => $reportable->user ? [
                                'id' => $reportable->user->id,
                                'name' => $reportable->user->first_name . ' ' . $reportable->user->last_name,
                                'username' => $reportable->user->username,
                            ] : null,
                        ];
                        break;
                    case 'comment':
                        $reportableData = [
                            'id' => $reportable->id,
                            'comment' => $reportable->comment,
                            'approved' => $reportable->approved,
                            'commentable_type' => $reportable->commentable_type,
                            'commentable_id' => $reportable->commentable_id,
                            'user' => $reportable->user ? [
                                'id' => $reportable->user->id,
                                'name' => $reportable->user->first_name . ' ' . $reportable->user->last_name,
                                'username' => $reportable->user->username,
                            ] : null,
                        ];
                        break;
                    case 'course':
                        $reportableData = [
                            'id' => $reportable->id,
                            'title' => $reportable->title,
                            'slug' => $reportable->slug ?? null,
                            'publish' => $reportable->publish,
                            'teacher' => $reportable->teacher ? [
                                'id' => $reportable->teacher->id,
                                'name' => $reportable->teacher->first_name . ' ' . $reportable->teacher->last_name,
                                'username' => $reportable->teacher->username,
                            ] : null,
                        ];
                        break;
                }
            }

            // Get base class name for display
            $displayType = class_basename($report->reportable_type);
            if (empty($displayType)) {
                $displayType = $report->reportable_type;
            }
            
            return [
                'id' => $report->id,
                'report' => $report->report,
                'status' => $report->status,
                'reportable_type' => strtolower($displayType),
                'reportable_type_full' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
                'reportable' => $reportableData,
                'user' => $report->user ? [
                    'id' => $report->user->id,
                    'name' => $report->user->first_name . ' ' . $report->user->last_name,
                    'username' => $report->user->username,
                    'email' => $report->user->email,
                    'profile_pic' => $report->user->profile_pic,
                ] : null,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'reports' => $reports
        ], 200);
    }

    /**
     * Get report statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 0)->count(),
            'resolved' => Report::where('status', 1)->count(),
            'by_type' => [
                'question' => Report::where(function($q) {
                    $q->where('reportable_type', 'question')
                      ->orWhere('reportable_type', 'App\\Models\\Question');
                })->count(),
                'answer' => Report::where(function($q) {
                    $q->where('reportable_type', 'answer')
                      ->orWhere('reportable_type', 'App\\Models\\Answer');
                })->count(),
                'comment' => Report::where(function($q) {
                    $q->where('reportable_type', 'comment')
                      ->orWhere('reportable_type', 'App\\Models\\Comment');
                })->count(),
                'course' => Report::where(function($q) {
                    $q->where('reportable_type', 'course')
                      ->orWhere('reportable_type', 'App\\Models\\Course');
                })->count(),
            ],
        ];

        return response()->json([
            'message' => 'Success',
            'stats' => $stats
        ], 200);
    }

    /**
     * Get single report details
     */
    public function show($id)
    {
        $report = Report::with(['user'])->find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        // Normalize reportable_type before loading relation
        if (!str_contains($report->reportable_type, 'App\\Models\\')) {
            $report->reportable_type = "App\\Models\\" . $report->reportable_type;
        }
        
        $report->load('reportable');

        return response()->json([
            'message' => 'Success',
            'report' => $report
        ], 200);
    }

    /**
     * Update report status (resolve/unresolve)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|boolean'
        ]);

        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $report->status = $request->status;
        $report->save();

        return response()->json([
            'message' => 'Report status updated successfully',
            'report' => $report
        ], 200);
    }

    /**
     * Delete report
     */
    public function delete($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully'
        ], 200);
    }

    /**
     * Delete multiple reports
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:reports,id'
        ]);

        Report::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => 'Reports deleted successfully'
        ], 200);
    }

    /**
     * Deactivate reported content
     */
    public function deactivateContent(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $reportable = $report->reportable;

        if (!$reportable) {
            return response()->json(['message' => 'Reported content not found'], 404);
        }

        DB::beginTransaction();
        try {
            $type = class_basename($report->reportable_type);
            if (empty($type)) {
                $type = $report->reportable_type;
            }
            
            switch (strtolower($type)) {
                case 'question':
                    if (isset($reportable->publish)) {
                        $reportable->publish = false;
                        $reportable->save();
                    }
                    break;
                case 'answer':
                    if (isset($reportable->publish)) {
                        $reportable->publish = false;
                        $reportable->save();
                    }
                    break;
                case 'comment':
                    if (isset($reportable->approved)) {
                        $reportable->approved = false;
                        $reportable->save();
                    }
                    break;
                case 'course':
                    if (isset($reportable->publish)) {
                        $reportable->publish = false;
                        $reportable->save();
                    }
                    break;
            }

            // Mark report as resolved
            $report->status = true;
            $report->save();

            DB::commit();

            return response()->json([
                'message' => 'Content deactivated successfully',
                'report' => $report->fresh(['user', 'reportable'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deactivating content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete reported content
     */
    public function deleteContent(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        // Normalize reportable_type before loading relation
        $report->normalizeReportableType();
        $reportable = $report->reportable;

        if (!$reportable) {
            return response()->json(['message' => 'Reported content not found'], 404);
        }

        DB::beginTransaction();
        try {
            $reportableType = $report->reportable_type;
            $reportableId = $report->reportable_id;
            
            // Get base class name for query
            $baseType = class_basename($reportableType);
            if (empty($baseType)) {
                $baseType = $reportableType;
            }

            // Delete the content
            $reportable->delete();

            // Delete all reports for this content (check both full and short class names)
            Report::where(function($q) use ($reportableType, $baseType) {
                $q->where('reportable_type', $reportableType)
                  ->orWhere('reportable_type', $baseType);
            })
            ->where('reportable_id', $reportableId)
            ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Content and related reports deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate reported content (if it was deactivated)
     */
    public function activateContent(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        // Normalize reportable_type before loading relation
        $report->normalizeReportableType();
        $reportable = $report->reportable;

        if (!$reportable) {
            return response()->json(['message' => 'Reported content not found'], 404);
        }

        DB::beginTransaction();
        try {
            $type = class_basename($report->reportable_type);
            if (empty($type)) {
                $type = $report->reportable_type;
            }
            
            switch (strtolower($type)) {
                case 'question':
                    if (isset($reportable->publish)) {
                        $reportable->publish = true;
                        $reportable->save();
                    }
                    break;
                case 'answer':
                    if (isset($reportable->publish)) {
                        $reportable->publish = true;
                        $reportable->save();
                    }
                    break;
                case 'comment':
                    if (isset($reportable->approved)) {
                        $reportable->approved = true;
                        $reportable->save();
                    }
                    break;
                case 'course':
                    if (isset($reportable->publish)) {
                        $reportable->publish = true;
                        $reportable->save();
                    }
                    break;
            }

            DB::commit();

            return response()->json([
                'message' => 'Content activated successfully',
                'report' => $report->fresh(['user', 'reportable'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error activating content',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

