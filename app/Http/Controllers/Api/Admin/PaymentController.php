<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentItem;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    /**
     * Get all payments with filters and pagination
     */
    public function payments(Request $request)
    {
        $query = Payment::with([
            'user:id,first_name,last_name,username,email,profile_pic',
            'items.payable',
            'attempts' => function($q) {
                $q->latest();
            }
        ]);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'paid') {
                $query->where('status', 1)->whereNotNull('paid_at');
            } elseif ($request->status === 'pending') {
                $query->where('status', 0)->whereNull('paid_at');
            } elseif ($request->status === 'expired') {
                $query->where('status', 0)->where('expired_at', '<', now());
            }
        }

        if ($request->filled('driver')) {
            $query->where('driver', $request->driver);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'amount_high':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('amount', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = $request->input('perPage', 20);
        $payments = $query->paginate($perPage);

        $data = collect($payments->items())->map(function ($payment) {
            return [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'reference_id' => $payment->reference_id,
                'tracking_number' => $payment->tracking_number,
                'amount' => $payment->amount,
                'discount_amount' => $payment->discount_amount,
                'discount_code' => $payment->discount_code,
                'driver' => $payment->driver,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
                'expired_at' => $payment->expired_at,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
                'verified_by_admin' => $payment->verified_by_admin,
                'description' => $payment->description,
                
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'first_name' => $payment->user->first_name,
                    'last_name' => $payment->user->last_name,
                    'username' => $payment->user->username,
                    'email' => $payment->user->email,
                    'profile_pic' => $payment->user->profile_pic,
                ] : null,

                'items' => $payment->items->map(function ($item) {
                    $base = [
                        'id' => $item->id,
                        'payable_type' => class_basename($item->payable_type),
                        'payable_id' => $item->payable_id,
                        'price' => $item->price,
                        'discount_amount' => $item->discount_amount,
                        'discount_code' => $item->discount_code,
                        'final_price' => $item->final_price,
                    ];

                    if ($item->relationLoaded('payable') && $item->payable) {
                        $payable = $item->payable;

                        if ($payable instanceof \App\Models\Course) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'price' => $payable->price,
                            ];
                        } elseif ($payable instanceof \App\Models\Path) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'icon' => $payable->icon,
                                'short_description' => $payable->short_description,
                            ];
                        } elseif ($payable instanceof \App\Models\Plan) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'icon' => $payable->icon,
                                'price' => $payable->price,
                                'period_time' => $payable->period_time,
                                'features' => $payable->features,
                            ];
                        }
                    }

                    return $base;
                }),

                'attempts_count' => $payment->attempts->count(),
                'last_attempt' => $payment->attempts->first() ? [
                    'id' => $payment->attempts->first()->id,
                    'attempt_reference' => $payment->attempts->first()->attempt_reference,
                    'gateway' => $payment->attempts->first()->gateway,
                    'status' => $payment->attempts->first()->status,
                    'created_at' => $payment->attempts->first()->created_at,
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'payments' => $data,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ], 200);
    }

    /**
     * Get payment details with all related data
     */
    public function paymentDetails(Request $request, $uuid)
    {
        $payment = Payment::with([
            'user:id,first_name,last_name,username,email,profile_pic,mobile,created_at',
            'items.payable',
            'attempts' => function($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])->where('uuid', $uuid)->firstOrFail();

        $paymentData = [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'reference_id' => $payment->reference_id,
            'tracking_number' => $payment->tracking_number,
            'amount' => $payment->amount,
            'discount_amount' => $payment->discount_amount,
            'discount_code' => $payment->discount_code,
            'driver' => $payment->driver,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
            'expired_at' => $payment->expired_at,
            'visited_at' => $payment->visited_at,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
            'verified_by_admin' => $payment->verified_by_admin,
            'description' => $payment->description,

            'user' => $payment->user ? [
                'id' => $payment->user->id,
                'first_name' => $payment->user->first_name,
                'last_name' => $payment->user->last_name,
                'username' => $payment->user->username,
                'email' => $payment->user->email,
                'mobile' => $payment->user->mobile,
                'profile_pic' => $payment->user->profile_pic,
                'created_at' => $payment->user->created_at,
            ] : null,

            'items' => $payment->items->map(function ($item) {
                $base = [
                    'id' => $item->id,
                    'payable_type' => class_basename($item->payable_type),
                    'payable_id' => $item->payable_id,
                    'price' => $item->price,
                    'discount_amount' => $item->discount_amount,
                    'discount_code' => $item->discount_code,
                    'final_price' => $item->final_price,
                    'created_at' => $item->created_at,
                ];

                if ($item->relationLoaded('payable') && $item->payable) {
                    $payable = $item->payable;

                    if ($payable instanceof \App\Models\Course) {
                        $base['payable'] = [
                            'id' => $payable->id,
                            'title' => $payable->title,
                            'english_title' => $payable->english_title,
                            'slug' => $payable->slug,
                            'poster' => $payable->poster,
                            'price' => $payable->price,
                            'type' => $payable->type,
                            'short_description' => $payable->short_description,
                        ];
                    } elseif ($payable instanceof \App\Models\Path) {
                        $base['payable'] = [
                            'id' => $payable->id,
                            'title' => $payable->title,
                            'english_title' => $payable->english_title,
                            'slug' => $payable->slug,
                            'poster' => $payable->poster,
                            'icon' => $payable->icon,
                            'short_description' => $payable->short_description,
                        ];
                    } elseif ($payable instanceof \App\Models\Plan) {
                        $base['payable'] = [
                            'id' => $payable->id,
                            'title' => $payable->title,
                            'english_title' => $payable->english_title,
                            'icon' => $payable->icon,
                            'price' => $payable->price,
                            'period_time' => $payable->period_time,
                            'features' => $payable->features,
                        ];
                    }
                }

                return $base;
            }),

            'attempts' => $payment->attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'attempt_reference' => $attempt->attempt_reference,
                    'payment_uuid' => $attempt->payment_uuid,
                    'gateway' => $attempt->gateway,
                    'status' => $attempt->status,
                    'request_payload' => $attempt->request_payload ? json_decode($attempt->request_payload, true) : null,
                    'response_payload' => $attempt->response_payload ? json_decode($attempt->response_payload, true) : null,
                    'created_at' => $attempt->created_at,
                    'updated_at' => $attempt->updated_at,
                ];
            }),
        ];

        return response()->json([
            'message' => 'Success',
            'payment' => $paymentData
        ], 200);
    }

    /**
     * Update payment status (admin verification)
     */
    public function updatePaymentStatus(Request $request, $uuid)
    {
        $request->validate([
            'status' => 'required|boolean',
            'verified_by_admin' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $payment = Payment::where('uuid', $uuid)->firstOrFail();
        $oldStatus = (bool) $payment->status;

        $payment->update([
            'status' => $request->status,
            'verified_by_admin' => $request->verified_by_admin,
            'description' => $request->description,
            'paid_at' => $request->status ? now() : null,
        ]);

        // If admin is approving an unpaid payment, grant user access/benefits
        if ($request->status && ! $oldStatus) {
            $payment->load(['items.payable', 'user']);

            // Detect wallet payments (similar to PaymentService::isWalletPayment)
            $isWalletPayment = str_contains($payment->description ?? '', 'کیف پول')
                || $payment->items()->count() === 0;

            if ($isWalletPayment) {
                // Handle wallet top-up
                $this->handleWalletPayment($payment, $payment->user);
            } else {
                // Handle normal successful payment (courses/paths/plans)
                $this->paymentService->handleSuccessfulPayment($payment);
            }
        }

        // Create a new attempt to log the admin action
        $payment->attempts()->create([
            'attempt_reference' => 'ADMIN-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            'payment_uuid' => $payment->uuid,
            'gateway' => 'admin',
            'status' => $request->status ? 'completed' : 'failed',
            'request_payload' => json_encode([
                'admin_id' => auth()->id(),
                'action' => 'status_update',
                'old_status' => !$request->status,
                'new_status' => $request->status,
                'verified_by_admin' => $request->verified_by_admin,
                'description' => $request->description,
            ]),
            'response_payload' => json_encode([
                'message' => 'Payment status updated by admin',
                'updated_at' => now(),
            ]),
        ]);

        return response()->json([
            'message' => 'Payment status updated successfully',
            'payment' => $payment->fresh(['user', 'items', 'attempts'])
        ], 200);
    }

    /**
     * Get payment statistics
     */
    public function paymentStats(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $stats = [
            'total_payments' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'paid_payments' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 1)
                ->whereNotNull('paid_at')
                ->count(),
                // pending excludes expired
                'pending_payments' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('status', 0)
                    ->whereNull('paid_at')
                    ->where(function ($q) {
                        $q->whereNull('expired_at')
                          ->orWhere('expired_at', '>=', now());
                    })
                    ->count(),
            'expired_payments' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 0)
                ->where('expired_at', '<', now())
                ->count(),
            'total_amount' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 1)
                ->whereNotNull('paid_at')
                ->sum('amount'),
            'total_discount' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 1)
                ->whereNotNull('paid_at')
                ->sum('discount_amount'),
        ];

        // Payment methods breakdown
        $paymentMethods = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select('driver', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('driver')
            ->get();

        $stats['payment_methods'] = $paymentMethods;

        // Daily payments for chart
        $dailyPayments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $stats['daily_payments'] = $dailyPayments;

        return response()->json([
            'message' => 'Success',
            'stats' => $stats
        ], 200);
    }

    /**
     * Delete payment (soft delete or hard delete)
     */
    public function deletePayment(Request $request, $uuid)
    {
        $request->validate([
            'force_delete' => 'boolean',
        ]);

        $payment = Payment::where('uuid', $uuid)->firstOrFail();

        if ($request->force_delete) {
            // Hard delete - remove all related data
            $payment->attempts()->delete();
            $payment->items()->delete();
            $payment->delete();
            
            $message = 'Payment permanently deleted';
        } else {
            // Soft delete - just mark as deleted
            $payment->update(['status' => 0]);
            
            // Create a new attempt to log the admin action
            $payment->attempts()->create([
                'attempt_reference' => 'ADMIN-DELETE-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'payment_uuid' => $payment->uuid,
                'gateway' => 'admin',
                'status' => 'deleted',
                'request_payload' => json_encode([
                    'admin_id' => auth()->id(),
                    'action' => 'delete',
                    'deleted_at' => now(),
                ]),
                'response_payload' => json_encode([
                    'message' => 'Payment deleted by admin',
                    'updated_at' => now(),
                ]),
            ]);
            
            $message = 'Payment deleted successfully';
        }

        return response()->json([
            'message' => $message
        ], 200);
    }

    /**
     * Export payments to CSV
     */
    public function exportPayments(Request $request)
    {
        $query = Payment::with(['user', 'items.payable']);

        // Apply same filters as payments method
        if ($request->filled('status')) {
            if ($request->status === 'paid') {
                $query->where('status', 1)->whereNotNull('paid_at');
            } elseif ($request->status === 'pending') {
                $query->where('status', 0)->whereNull('paid_at');
            } elseif ($request->status === 'expired') {
                $query->where('status', 0)->where('expired_at', '<', now());
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'UUID', 'Reference ID', 'User', 'Email', 'Amount', 'Discount', 'Status', 
            'Payment Method', 'Paid At', 'Created At', 'Items'
        ];

        foreach ($payments as $payment) {
            $items = $payment->items->map(function ($item) {
                return $item->payable ? $item->payable->title : 'Unknown Item';
            })->implode(', ');

            $csvData[] = [
                $payment->id,
                $payment->uuid,
                $payment->reference_id,
                $payment->user ? $payment->user->first_name . ' ' . $payment->user->last_name : 'N/A',
                $payment->user ? $payment->user->email : 'N/A',
                $payment->amount,
                $payment->discount_amount,
                $payment->status ? 'Paid' : 'Pending',
                $payment->driver,
                $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : 'N/A',
                $payment->created_at->format('Y-m-d H:i:s'),
                $items
            ];
        }

        $filename = 'payments_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get users for payment creation
     */
    public function getUsers(Request $request)
    {
        $query = \App\Models\User::select('id', 'first_name', 'last_name', 'username', 'email', 'profile_pic', 'wallet_balance');

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
     * Universal search for payment creation (users, courses, plans, paths)
     */
    public function search(Request $request)
    {
        $search = $request->input('search', '');
        $type = $request->input('type', 'user'); // user, course, plan, path
        
        if (strlen($search) < 2) {
            return response()->json([
                'message' => 'Search term too short',
                'results' => []
            ], 200);
        }

        $results = [];

        switch ($type) {
            case 'user':
                $users = \App\Models\User::select('id', 'first_name', 'last_name', 'username', 'email', 'profile_pic', 'wallet_balance')
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
                $courses = \App\Models\Course::select('id', 'title', 'english_title', 'price', 'type', 'poster')
                    ->where('publish', 1)
                    ->where(function($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('english_title', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
                $results['courses'] = $courses;
                break;

            case 'plan':
                $plans = \App\Models\Plan::select('id', 'title', 'english_title', 'price', 'period_time', 'icon', 'features')
                    ->where('status', 1)
                    ->where(function($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('english_title', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
                $results['plans'] = $plans;
                break;

            case 'path':
                $paths = \App\Models\Path::select('id', 'title', 'english_title', 'slug', 'poster', 'icon')
                    ->where('status', 1)
                    ->where(function($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('english_title', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
                $results['paths'] = $paths;
                break;
        }

        return response()->json([
            'message' => 'Success',
            'results' => $results
        ], 200);
    }

    /**
     * Get courses for payment creation
     */
    public function getCourses(Request $request)
    {
        $query = \App\Models\Course::select('id', 'title', 'english_title', 'price', 'type', 'poster')
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
     * Get plans for payment creation
     */
    public function getPlans(Request $request)
    {
        $query = \App\Models\Plan::select('id', 'title', 'english_title', 'price', 'period_time', 'icon', 'features')
            ->where('status', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('english_title', 'like', "%{$search}%");
            });
        }

        $plans = $query->limit(20)->get();

        return response()->json([
            'message' => 'Success',
            'plans' => $plans
        ], 200);
    }

    /**
     * Get paths for payment creation
     */
    public function getPaths(Request $request)
    {
        $query = \App\Models\Path::select('id', 'title', 'english_title', 'slug', 'poster', 'icon')
            ->where('status', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('english_title', 'like', "%{$search}%");
            });
        }

        $paths = $query->limit(20)->get();

        return response()->json([
            'message' => 'Success',
            'paths' => $paths
        ], 200);
    }

    /**
     * Create payment for user (Admin)
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'payment_method' => 'required|in:wallet,bank',
            'items' => 'required|array|min:1',
            'items.*.payable_type' => 'required|in:course,plan,path,wallet',
            'items.*.payable_id' => 'required_unless:items.*.payable_type,wallet|nullable|integer',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string|max:50',
            'driver' => 'required_if:payment_method,bank|string|max:50',
            'description' => 'nullable|string|max:1000',
            'auto_approve' => 'boolean',
            'expired_at' => 'required|date|after:now',
        ]);

        // Custom validation: Prevent wallet payment method when wallet items are selected
        $hasWalletItem = collect($request->items)->contains('payable_type', 'wallet');
        if ($hasWalletItem && $request->payment_method === 'wallet') {
            return response()->json([
                'message' => 'نمی‌توانید از کیف پول برای افزایش موجودی کیف پول استفاده کنید',
                'errors' => [
                    'payment_method' => ['نمی‌توانید از کیف پول برای افزایش موجودی کیف پول استفاده کنید']
                ]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get user
            $user = \App\Models\User::findOrFail($request->user_id);

            // Calculate final amount
            $finalAmount = $request->amount - ($request->discount_amount ?? 0);

            // Create payment
            $payment = Payment::create([
                'user_id' => $request->user_id,
                'driver' => $request->driver ?? null,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'discount_amount' => $request->discount_amount ?? 0,
                'discount_code' => $request->discount_code,
                'status' => $request->auto_approve ?? false,
                'paid_at' => $request->auto_approve ? now() : null,
                'expired_at' => $request->expired_at,
                'verified_by_admin' => $request->auto_approve ? auth('api')->user()->id : null,
                'description' => $request->description ?? 'Payment created by admin',
            ]);

            // Track wallet items
            $walletItems = [];
            $hasWalletItem = false;
            
            // Create payment items
            foreach ($request->items as $item) {
                // Check if this is a wallet item
                if ($item['payable_type'] === 'wallet') {
                    $hasWalletItem = true;
                    $walletAmount = max(0, $item['price'] - ($item['discount_amount'] ?? 0));
                    $walletItems[] = [
                        'amount' => $walletAmount,
                        'price' => $item['price'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                    ];
                    continue; // Skip creating PaymentItem for wallet
                }
                
                $payableType = 'App\\Models\\' . ucfirst($item['payable_type']);
                
                // Validate payable exists (skip for wallet items)
                if ($item['payable_type'] !== 'wallet') {
                    if (!class_exists($payableType)) {
                        throw new \Exception("Invalid payable type: {$item['payable_type']}");
                    }

                    $payable = $payableType::find($item['payable_id']);
                    if (!$payable) {
                        throw new \Exception("{$item['payable_type']} not found with ID: {$item['payable_id']}");
                    }
                }

                $finalPrice = max(0, $item['price'] - ($item['discount_amount'] ?? 0));

                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'payable_type' => $payableType,
                    'payable_id' => $item['payable_id'],
                    'price' => $item['price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'final_price' => $finalPrice,
                ]);
            }
            
            // Update payment description if wallet charging is included
            if ($hasWalletItem) {
                $currentDescription = $payment->description ?? '';
                $walletText = 'افزایش موجودی کیف پول';
                
                if (!str_contains($currentDescription, $walletText)) {
                    $newDescription = $currentDescription ? 
                        $currentDescription . ' + ' . $walletText : 
                        $walletText;
                    
                    $payment->update(['description' => $newDescription]);
                }
            }

            // Handle bank payment method - create real bank transaction
            if ($request->payment_method === 'bank') {
                // Create payment attempt
                // $attempt = $payment->attempts()->create([
                //     'attempt_reference' => 'ADMIN-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                //     'payment_uuid' => $payment->uuid,
                //     'gateway' => $request->driver ?? 'zarinpal',
                //     'status' => 'pending',
                //     'request_payload' => json_encode([
                //         'admin_id' => auth('api')->user()->id,
                //         'action' => 'create_payment',
                //         'auto_approve' => $request->auto_approve,
                //         'items' => $request->items,
                //     ]),
                //     'response_payload' => json_encode([
                //         'message' => 'Payment created by admin',
                //         'payment_id' => $payment->id,
                //         'created_at' => now(),
                //     ]),
                // ]);

                // Start real bank transaction (similar to PaymentService)
                try {
                    $callbackUrl = route('api.payment.callback', $payment->uuid);
                    $driver = $request->driver ?? config('payment.default', 'zarinpal');
                    
                    $invoice = (new \Shetabit\Multipay\Invoice)->amount((int) $payment->amount);
                    
                    $bankResponse = \Shetabit\Payment\Facade\Payment::via($driver)
                        ->callbackUrl($callbackUrl)
                        ->purchase($invoice, function ($driver, $transactionId) use ($payment) {
                            // $attempt->update(['transaction_id' => $transactionId]);
                            $payment->update(['tracking_number' => $transactionId, 'resnumber' => $transactionId]);
                        });

                    // If auto approve, process the payment after successful bank transaction
                    if ($request->auto_approve) {
                        $this->processPayment($payment, $user, $hasWalletItem ? $walletItems : null);
                    }

                } catch (\Exception $e) {
                    // Log bank transaction error
                    \Illuminate\Support\Facades\Log::error('Bank transaction failed for payment: ' . $payment->id, [
                        'error' => $e->getMessage(),
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                    ]);
                    
                    throw new \Exception('Bank transaction failed: ' . $e->getMessage());
                }
            } else {
                // For wallet payments, process immediately if auto approve
                if ($request->auto_approve) {
                    $this->processPayment($payment, $user, $hasWalletItem ? $walletItems : null);
                }
            }

            // Create admin attempt log
            
            // $payment->attempts()->create([
            //     'attempt_reference' => 'ADMIN-CREATE-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            //     'payment_uuid' => $payment->uuid,
            //     'gateway' => 'admin',
            //     'status' => $request->auto_approve ? 'completed' : 'pending',
            //     'request_payload' => json_encode([
            //         'admin_id' => auth()->id(),
            //         'action' => 'create_payment',
            //         'auto_approve' => $request->auto_approve,
            //         'items' => $request->items,
            //     ]),
            //     'response_payload' => json_encode([
            //         'message' => 'Payment created by admin',
            //         'payment_id' => $payment->id,
            //         'created_at' => now(),
            //     ]),
            // ]);

            DB::commit();

            return response()->json([
                'message' => 'Payment created successfully',
                'payment' => $payment->fresh(['user', 'items.payable', 'attempts'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Process payment (grant access/benefits) - Enhanced version similar to PaymentService
     */
    private function processPayment(Payment $payment, \App\Models\User $user, $walletItems = null)
    {
        if (!$payment->items) {
            return;
        }

        // Check if this payment includes wallet charging
        $hasWalletPayment = str_contains($payment->description ?? '', 'کیف پول');
        
        // جمع‌آوری اطلاعات خریدها برای ارسال یک نوتیف واحد
        $courses = [];
        $plans = [];
        $paths = [];
        
        // Process regular payment items (if any)
        if ($payment->items()->count() > 0) {
            foreach ($payment->items as $item) {
                $payable = $item->payable;

                if (!$payable) {
                    continue;
                }

                switch ($item->payable_type) {
                    case \App\Models\Course::class:
                        // Grant course access with proper price tracking
                        $itemFinalPrice = $item->final_price ?? $item->price ?? 0;
                        $user->courses()->syncWithoutDetaching([
                            $payable->id => [
                                'price' => $itemFinalPrice,
                                'payment_id' => $payment->id,
                            ]
                        ]);
                        $courses[] = $payable;
                        break;

                    case \App\Models\Path::class:
                        // Handle path access with course opening logic
                        $courseIdsInPaymentItems = $payment->items->where('payable_type', \App\Models\Course::class)->pluck('payable_id')->toArray();
                        $userCourseIds = $user->courses->pluck('id')->toArray();

                        $availableCourses = $payable->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInPaymentItems) {
                            return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInPaymentItems);
                        });

                        // Apply path discount (40% as per PaymentService)
                        $discountPercentForPath = 40;
                        foreach ($availableCourses as $course) {
                            $user->courses()->attach($course->id, [
                                'payment_id' => $payment->id,
                                'price' => (int) round($course->price - ($course->price * $discountPercentForPath / 100)),
                            ]);
                        }
                        $paths[] = $payable;
                        break;

                    case \App\Models\Plan::class:
                        // Grant plan subscription with proper expiration
                        $user->plans()->attach($payable->id, [
                            'price' => $payable->price,
                            'expired_at' => \Carbon\Carbon::now()->addDays($payable->period_time),
                            'purchase_type' => 'online',
                            'payment_id' => $payment->id,
                        ]);
                        $plans[] = $payable;
                        break;

                    default:
                        \Illuminate\Support\Facades\Log::warning("نوع ناشناخته در پرداخت: {$item->payable_type}", [
                            'payment_id' => $payment->id,
                            'item_id' => $item->id,
                        ]);
                }
            }
        }
        
        // ارسال نوتیف‌های مناسب بر اساس محتویات خرید
        $this->sendPurchaseNotifications($user, $courses, $plans, $paths);
        
        // If payment description includes wallet charging, also handle wallet
        if ($hasWalletPayment) {
            $this->handleWalletPayment($payment, $user, $walletItems);
        }

        // Record discount/coupon usage(s) for this successful payment
        $codes = collect();
        if ($payment->discount_code) {
            $codes->push($payment->discount_code);
        }
        foreach ($payment->items as $item) {
            if (!empty($item->discount_code)) {
                $codes->push($item->discount_code);
            }
        }
        $codes = $codes->filter()->unique();
        foreach ($codes as $code) {
            $discount = \App\Models\Discount::where('code', $code)->first();
            if ($discount) {
                $discount->usages()->create([
                    'user_id' => $payment->user_id,
                    'payment_id' => $payment->id,
                    'used_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle wallet payment - similar to PaymentService handleSuccessfulWalletPayment
     */
    private function handleWalletPayment(Payment $payment, \App\Models\User $user, $walletItems = null)
    {
        if ($walletItems && is_array($walletItems)) {
            // Handle multiple wallet items
            foreach ($walletItems as $walletItem) {
                $amount = $walletItem['amount'];
                
                // Create wallet record for each wallet item
                $wallet = $user->wallets()->create([
                    'description' => 'افزایش موجودی کیف پول',
                    'amount' => $amount,
                    'after_balance' => $user->wallet_balance + $amount,
                    'type' => 'increase',
                    'payment_id' => $payment->id,
                    'tracking_number' => $payment->tracking_number ?? 'ADMIN-' . now()->format('YmdHis'),
                ]);

                // Update user wallet balance
                $user->update([
                    'wallet_balance' => $wallet->after_balance,
                ]);
            }
        } else {
            // Fallback to single wallet payment (for backward compatibility)
            $amount = $payment->amount;
            
            // Create wallet record (uuid and reference_id will be auto-generated)
            $wallet = $user->wallets()->create([
                'description' => 'افزایش موجودی کیف پول',
                'amount' => $amount,
                'after_balance' => $user->wallet_balance + $amount,
                'type' => 'increase',
                'payment_id' => $payment->id,
                'tracking_number' => $payment->tracking_number ?? 'ADMIN-' . now()->format('YmdHis'),
            ]);

            // Update user wallet balance
            $user->update([
                'wallet_balance' => $wallet->after_balance,
            ]);
        }
    }
    
    /**
     * ارسال نوتیف‌های خرید بر اساس محتویات
     */
    private function sendPurchaseNotifications($user, $courses, $plans, $paths)
    {
        $totalItems = count($courses) + count($plans) + count($paths);
        
        if ($totalItems === 0) {
            return;
        }
        
        // ارسال اطلاع‌رسانی - Notification های فیزیکی خودشان کانال‌ها را از NotificationService می‌گیرند
        // اگر فقط دوره خریداری شده
        if (count($courses) > 0 && count($plans) === 0 && count($paths) === 0) {
            if (count($courses) === 1) {
                // یک دوره
                $user->notify(new \App\Notifications\Payment\CoursePurchaseNotification(
                    "دوره «{$courses[0]->title}» با موفقیت برای شما فعال شد.",
                    frontendUrl("course/{$courses[0]->slug}"),
                    'مشاهده دوره'
                ));
            } else {
                // چند دوره
                $courseTitles = collect($courses)->pluck('title')->implode('، ');
                $user->notify(new \App\Notifications\Payment\CoursePurchaseNotification(
                    count($courses) . " دوره با موفقیت برای شما فعال شد: {$courseTitles}",
                    frontendUrl('panel/courses'),
                    'مشاهده دوره‌ها'
                ));
            }
        }
        // اگر فقط پلن VIP خریداری شده
        elseif (count($plans) > 0 && count($courses) === 0 && count($paths) === 0) {
            if (count($plans) === 1) {
                $user->notify(new \App\Notifications\Payment\VipUpgradeNotification(
                    "عضویت ویژه «{$plans[0]->title}» با موفقیت برای شما فعال شد.",
                    frontendUrl('panel/vip'),
                    'مشاهده پلن'
                ));
            } else {
                $planTitles = collect($plans)->pluck('title')->implode('، ');
                $user->notify(new \App\Notifications\Payment\VipUpgradeNotification(
                    count($plans) . " پلن عضویت ویژه با موفقیت برای شما فعال شد: {$planTitles}",
                    frontendUrl('panel/vip'),
                    'مشاهده پلن‌ها'
                ));
            }
        }
        // اگر ترکیبی از دوره و پلن
        else {
            $items = [];
            if (count($courses) > 0) {
                $items[] = count($courses) . " دوره";
            }
            if (count($plans) > 0) {
                $items[] = count($plans) . " پلن عضویت ویژه";
            }
            if (count($paths) > 0) {
                $items[] = count($paths) . " مسیر یادگیری";
            }
            
            $itemsText = implode(' و ', $items);
            $user->notify(new \App\Notifications\Payment\CoursePurchaseNotification(
                "خرید شما با موفقیت انجام شد و {$itemsText} برای شما فعال شد.",
                frontendUrl('panel/courses'),
                'مشاهده خریدها'
            ));
        }
    }
}
