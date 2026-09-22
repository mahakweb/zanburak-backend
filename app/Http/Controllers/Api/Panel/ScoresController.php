<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Services\ScoresService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScoresController extends Controller
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    /**
     * دریافت امتیاز کل و تاریخچه با فیلتر
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        
        $totalScores = $this->scoresService->getTotalScores($user);
        
        // محاسبه کل امتیازاتی که تا الان گرفته (فقط مثبت)
        $totalEarned = $user->scores()->where('score', '>', 0)->sum('score');
        
        // محاسبه کل امتیازاتی که تبدیل کرده (فقط منفی که شامل "تبدیل" در description است)
        $totalConverted = abs($user->scores()
            ->where('score', '<', 0)
            ->where('description', 'like', '%تبدیل%')
            ->sum('score'));
        
        // تعداد تبدیل‌ها
        $conversionCount = $user->scores()
            ->where('score', '<', 0)
            ->where('description', 'like', '%تبدیل%')
            ->count();
        
        // فیلترها
        $query = $user->scores();
        
        // فیلتر بر اساس نوع (مثبت/منفی)
        if ($request->has('type')) {
            if ($request->type === 'positive') {
                $query->where('score', '>', 0);
            } elseif ($request->type === 'negative') {
                $query->where('score', '<', 0);
            }
        }
        
        // فیلتر بر اساس تاریخ
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // جستجو در توضیحات
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        
        // مرتب‌سازی
        $sortBy = $request->get('sort_by', 'date_desc');
        switch ($sortBy) {
            case 'date_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'date_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'score_desc':
                $query->orderBy('score', 'desc');
                break;
            case 'score_asc':
                $query->orderBy('score', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $history = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'total_scores' => $totalScores, // امتیاز فعلی
                'total_earned' => $totalEarned, // کل امتیازاتی که تا الان گرفته
                'total_converted' => $totalConverted, // کل امتیازاتی که تبدیل کرده
                'conversion_count' => $conversionCount, // تعداد تبدیل‌ها
                'history' => $history->items(),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                ],
            ],
        ]);
    }

    /**
     * تبدیل امتیاز به پول نقد
     */
    public function convertToMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scores' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth('api')->user();
        
        $conversionRate = $this->scoresService->getConversionRate();
        $minScores = $this->scoresService->getMinScores();
        
        $result = $this->scoresService->convertScoresToMoney($user, $request->scores, $conversionRate, $minScores);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'scores' => $result['scores'],
                    'amount' => $result['amount'],
                    'wallet_balance' => $user->fresh()->wallet_balance,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * دریافت اطلاعات تبدیل (نرخ تبدیل و حداقل امتیاز)
     */
    public function conversionInfo()
    {
        $rate = $this->scoresService->getConversionRate();
        $minScores = $this->scoresService->getMinScores();

        return response()->json([
            'success' => true,
            'data' => [
                'conversion_rate' => $rate,
                'min_scores' => $minScores,
                'description' => "هر 1 امتیاز = {$rate} تومان",
            ],
        ]);
    }
}

