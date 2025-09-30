<?php

namespace App\Http\Controllers\Api\Admin\Discount;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountEligibility;
use App\Models\DiscountCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    /**
     * Get paginated list of discount codes
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 5);
        $search = $request->get('search');
        $status = $request->get('status');

        $query = Discount::query()->with(['eligibilities', 'conditions', 'usages']);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status && $status !== 'all') {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $discounts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'message' => 'Success',
            'discounts' => $discounts->items(),
            'pagination' => [
                'current_page' => $discounts->currentPage(),
                'last_page' => $discounts->lastPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
                'from' => $discounts->firstItem(),
                'to' => $discounts->lastItem(),
            ]
        ], 200);
    }

    /**
     * Get a single discount code for editing
     */
    public function show($id)
    {
        $discount = Discount::with(['eligibilities', 'conditions'])->findOrFail($id);

        return response()->json([
            'message' => 'Success',
            'discount' => $discount
        ], 200);
    }

    /**
     * Create a new discount code
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:discounts,code',
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:percent,fixed,free',
            'value' => 'nullable|required_if:type,percent,fixed|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'eligibilities' => 'array',
            'eligibilities.*.type' => 'required_with:eligibilities|in:inclusion,exclusion',
            'eligibilities.*.target_type' => 'required_with:eligibilities|in:user,course,path,vip,category',
            'eligibilities.*.target_id' => 'nullable|string',
            'conditions' => 'array',
            'conditions.*.condition_type' => 'required_with:conditions|in:min_cart_total,max_cart_total,min_item_price,max_item_price,min_item_count,max_item_count,same_product_quantity,first_purchase,no_purchase_since,min_orders_count,max_orders_count,day_of_week,time_range,date_range,required_item,forbidden_item,required_category,forbidden_category,min_total_spent,max_total_spent,purchased_product_before,not_purchased_product_before,new_user',
            'conditions.*.operator' => 'nullable|in:=,!=,>,<,>=,<=',
            'conditions.*.value' => 'nullable|numeric',
            'conditions.*.item_type' => 'nullable|in:course,path,vip',
            'conditions.*.extra' => 'array',
        ]);

        // Custom validation for percentage type
        if ($request->type === 'percent') {
            $validator->addRules([
                'value' => 'max:100'
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create the discount
            $discount = Discount::create([
                'code' => strtoupper($request->code),
                'title' => $request->title,
                'type' => $request->type,
                'value' => $request->value,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit,
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'is_active' => $request->get('is_active', true),
            ]);

            // Create eligibilities
            if ($request->has('eligibilities')) {
                foreach ($request->eligibilities as $eligibility) {
                    if (!empty($eligibility['type']) && !empty($eligibility['target_type'])) {
                        DiscountEligibility::create([
                            'discount_id' => $discount->id,
                            'type' => $eligibility['type'], // inclusion | exclusion
                            'target_type' => $this->getTargetType($eligibility['target_type']),
                            'target_id' => $eligibility['target_id'] ?? null,
                        ]);
                    }
                }
            }

            // Create conditions
            if ($request->has('conditions')) {
                foreach ($request->conditions as $condition) {
                    if (!empty($condition['condition_type'])) {
                        DiscountCondition::create([
                            'discount_id' => $discount->id,
                            'item_type' => $condition['item_type'] ?? null,
                            'condition_type' => $condition['condition_type'],
                            'operator' => $condition['operator'] ?? null,
                            'value' => $condition['value'] ?? null,
                            'extra' => $condition['extra'] ?? [],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Discount code created successfully',
                'discount' => $discount->load(['eligibilities', 'conditions'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating discount code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing discount code
     */
    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50', Rule::unique('discounts')->ignore($discount->id)],
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:percent,fixed,free',
            'value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'eligibilities' => 'array',
            'eligibilities.*.type' => 'required_with:eligibilities|in:inclusion,exclusion',
            'eligibilities.*.target_type' => 'required_with:eligibilities|in:user,course,path,vip,category',
            'eligibilities.*.target_id' => 'nullable|string',
            'conditions' => 'array',
            'conditions.*.condition_type' => 'required_with:conditions|in:min_cart_total,max_cart_total,min_item_price,max_item_price,min_item_count,max_item_count,same_product_quantity,first_purchase,no_purchase_since,min_orders_count,max_orders_count,day_of_week,time_range,date_range,required_item,forbidden_item,required_category,forbidden_category,min_total_spent,max_total_spent,purchased_product_before,not_purchased_product_before,new_user',
            'conditions.*.operator' => 'nullable|in:=,!=,>,<,>=,<=',
            'conditions.*.value' => 'nullable|numeric',
            'conditions.*.item_type' => 'nullable|in:course,path,vip',
            'conditions.*.extra' => 'array',
        ]);

        // Custom validation for percentage type
        if ($request->type === 'percent') {
            $validator->addRules([
                'value' => 'max:100'
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update the discount
            $discount->update([
                'code' => strtoupper($request->code),
                'title' => $request->title,
                'type' => $request->type,
                'value' => $request->value,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit,
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'is_active' => $request->get('is_active', true),
            ]);

            // Delete existing eligibilities and conditions
            $discount->eligibilities()->delete();
            $discount->conditions()->delete();

            // Create new eligibilities
            if ($request->has('eligibilities')) {
                foreach ($request->eligibilities as $eligibility) {
                    if (!empty($eligibility['type']) && !empty($eligibility['target_type'])) {
                        DiscountEligibility::create([
                            'discount_id' => $discount->id,
                            'type' => $eligibility['type'],
                            'target_type' => $this->getTargetType($eligibility['target_type']),
                            'target_id' => $eligibility['target_id'] ?? null,
                        ]);
                    }
                }
            }

            // Create new conditions
            if ($request->has('conditions')) {
                foreach ($request->conditions as $condition) {
                    if (!empty($condition['condition_type'])) {
                        DiscountCondition::create([
                            'discount_id' => $discount->id,
                            'item_type' => $condition['item_type'] ?? null,
                            'condition_type' => $condition['condition_type'],
                            'operator' => $condition['operator'] ?? null,
                            'value' => $condition['value'] ?? null,
                            'extra' => $condition['extra'] ?? [],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Discount code updated successfully',
                'discount' => $discount->load(['eligibilities', 'conditions'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating discount code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle discount status
     */
    public function toggleStatus($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update(['is_active' => !$discount->is_active]);

        return response()->json([
            'message' => 'Discount status updated successfully',
            'discount' => $discount
        ], 200);
    }

    /**
     * Delete a discount code
     */
    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        
        // Check if discount has been used
        if ($discount->usages()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete discount code that has been used',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete related records
            $discount->eligibilities()->delete();
            $discount->conditions()->delete();
            
            // Delete the discount
            $discount->delete();

            DB::commit();

            return response()->json([
                'message' => 'Discount code deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting discount code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get target type based on eligibility type
     */
    private function getTargetType($type)
    {
        switch ($type) {
            case 'user':
                return 'App\\Models\\User';
            case 'course':
                return 'App\\Models\\Course';
            case 'path':
                return 'App\\Models\\Path'; 
            case 'category':
                return 'App\\Models\\Category';
            default:
                return null;
        }
    }
}
