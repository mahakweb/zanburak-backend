<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * List plans with aggregate metrics for admin panel
     */
    public function plans(Request $request)
    {
        $query = Plan::query()
            ->select(['id', 'title', 'english_title', 'price', 'period_time', 'icon', 'status', 'popular', 'features', 'allows_installment', 'created_at'])
            ->withCount([
                // total purchases = count of pivot rows in plan_user
                'users as purchases_count',
                // active users = pivot rows with non-expired membership
                'activeUsers as active_users_count',
            ]);

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('status', 1);
            } elseif ($request->status === 'inactive') {
                $query->where('status', 0);
            }
        }

        if ($request->input('installment') === 'yes') {
            $query->where('allows_installment', true);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'purchases_high':
                $query->orderBy('purchases_count', 'desc');
                break;
            case 'active_high':
                $query->orderBy('active_users_count', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = (int) $request->input('perPage', 20);
        $plans = $query->paginate($perPage);

        $data = collect($plans->items())->map(function ($plan) {
            return [
                'id' => $plan->id,
                'title' => $plan->title,
                'english_title' => $plan->english_title,
                'price' => $plan->price,
                'period_time' => $plan->period_time,
                'icon' => $plan->icon,
                'status' => (bool) $plan->status,
                'popular' => (bool) $plan->popular,
                'allows_installment' => (bool) $plan->allows_installment,
                'features' => $plan->features,
                'created_at' => $plan->created_at,
                'purchases_count' => (int) ($plan->purchases_count ?? 0),
                'active_users_count' => (int) ($plan->active_users_count ?? 0),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'plans' => $data,
            'pagination' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
                'from' => $plans->firstItem(),
                'to' => $plans->lastItem(),
            ],
        ], 200);
    }

    /**
     * Create a new plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'english_title' => ['required', 'string', 'max:255'],
            'period_time' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:1024'],
            'status' => ['nullable', 'boolean'],
            'popular' => ['nullable', 'boolean'],
            'allows_installment' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array', 'max:5'],
            'features.*' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = Plan::create([
            'title' => $validated['title'],
            'english_title' => $validated['english_title'],
            'period_time' => $validated['period_time'],
            'price' => $validated['price'],
            'icon' => $validated['icon'] ?? null,
            'status' => array_key_exists('status', $validated) ? (bool) $validated['status'] : true,
            'popular' => array_key_exists('popular', $validated) ? (bool) $validated['popular'] : false,
            'allows_installment' => (bool) ($validated['allows_installment'] ?? false),
            'description' => $validated['description'] ?? null,
            'features' => $validated['features'] ?? [],
        ]);

        return response()->json([
            'message' => 'Plan created successfully',
            'plan' => [
                'id' => $plan->id,
                'title' => $plan->title,
                'english_title' => $plan->english_title,
                'price' => $plan->price,
                'period_time' => $plan->period_time,
                'icon' => $plan->icon,
                'status' => (bool) $plan->status,
                'popular' => (bool) $plan->popular,
                'description' => $plan->description,
                'allows_installment' => (bool) $plan->allows_installment,
                'features' => $plan->features,
                'created_at' => $plan->created_at,
            ],
        ], 201);
    }

    /**
     * Show single plan details
     */
    public function show(Request $request, Plan $plan)
    {
        return response()->json([
            'message' => 'Success',
            'plan' => [
                'id' => $plan->id,
                'title' => $plan->title,
                'english_title' => $plan->english_title,
                'price' => $plan->price,
                'period_time' => $plan->period_time,
                'icon' => $plan->icon,
                'status' => (bool) $plan->status,
                'popular' => (bool) $plan->popular,
                'description' => $plan->description,
                'allows_installment' => (bool) $plan->allows_installment,
                'features' => $plan->features,
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
            ],
        ], 200);
    }

    /**
     * Update existing plan
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'english_title' => ['required', 'string', 'max:255'],
            'period_time' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:1024'],
            'status' => ['nullable', 'boolean'],
            'popular' => ['nullable', 'boolean'],
            'allows_installment' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array', 'max:5'],
            'features.*' => ['nullable', 'string', 'max:255'],
        ]);

        $plan->update([
            'title' => $validated['title'],
            'english_title' => $validated['english_title'],
            'period_time' => $validated['period_time'],
            'price' => $validated['price'],
            'icon' => $validated['icon'] ?? null,
            'status' => array_key_exists('status', $validated) ? (bool) $validated['status'] : $plan->status,
            'popular' => array_key_exists('popular', $validated) ? (bool) $validated['popular'] : $plan->popular,
            'allows_installment' => array_key_exists('allows_installment', $validated)
                ? (bool) $validated['allows_installment']
                : $plan->allows_installment,
            'description' => $validated['description'] ?? null,
            'features' => $validated['features'] ?? [],
        ]);

        return response()->json([
            'message' => 'Plan updated successfully',
            'plan' => [
                'id' => $plan->id,
                'title' => $plan->title,
                'english_title' => $plan->english_title,
                'price' => $plan->price,
                'period_time' => $plan->period_time,
                'icon' => $plan->icon,
                'status' => (bool) $plan->status,
                'popular' => (bool) $plan->popular,
                'description' => $plan->description,
                'allows_installment' => (bool) $plan->allows_installment,
                'features' => $plan->features,
                'updated_at' => $plan->updated_at,
            ],
        ], 200);
    }

    /**
     * Delete a plan
     */
    public function destroy(Request $request, Plan $plan)
    {
        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted successfully'
        ], 200);
    }
}


