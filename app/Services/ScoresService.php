<?php

namespace App\Services;

use App\Models\User;
use App\Models\Score;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ScoresService
{
    /**
     * نرخ تبدیل امتیاز → تومان (از site_settings با fallback به config/env)
     */
    public function getConversionRate(): float
    {
        return (float) app(SiteSettingService::class)->get(
            'scores.conversion_rate',
            config('scores.conversion_rate', 1)
        );
    }

    /**
     * حداقل امتیاز لازم برای تبدیل
     */
    public function getMinScores(): int
    {
        return (int) app(SiteSettingService::class)->get(
            'scores.min_scores',
            config('scores.min_scores', 1000)
        );
    }

    /**
     * ذخیره تنظیمات تبدیل امتیاز در دیتابیس
     */
    public function updateConversionSettings(float $conversionRate, int $minScores, ?int $updatedBy = null): array
    {
        app(SiteSettingService::class)->putMany([
            'scores.conversion_rate' => [
                'value' => $conversionRate,
                'type' => 'float',
                'group' => 'scores',
            ],
            'scores.min_scores' => [
                'value' => $minScores,
                'type' => 'integer',
                'group' => 'scores',
            ],
        ], $updatedBy);

        return [
            'conversion_rate' => $this->getConversionRate(),
            'min_scores' => $this->getMinScores(),
        ];
    }

    /**
     * اعطای امتیاز به کاربر
     *
     * @param User $user کاربری که باید امتیاز دریافت کند
     * @param string $description توضیحات امتیاز
     * @param int $scores مقدار امتیاز
     * @return Score|null
     */
    public function awardScores(User $user, string $description, int $scores): ?Score
    {
        if ($scores <= 0) {
            return null;
        }

        try {
            // ایجاد رکورد امتیاز
            $score = $user->scores()->create([
                'description' => $description,
                'score' => $scores,
            ]);

            Log::info("Awarded {$scores} scores to user {$user->id}: {$description}");

            return $score;
        } catch (\Exception $e) {
            Log::error("Error awarding scores: " . $e->getMessage());
            return null;
        }
    }

    /**
     * تبدیل امتیاز به پول نقد
     *
     * @param User $user
     * @param int $scores مقدار امتیاز برای تبدیل
     * @param float $conversionRate نرخ تبدیل (پیش‌فرض: 1 = 1 امتیاز = 1 تومان)
     * @param int $minScores حداقل امتیاز برای تبدیل (پیش‌فرض: 1000)
     * @return array
     */
    public function convertScoresToMoney(User $user, int $scores, float $conversionRate = 1, int $minScores = 1000): array
    {
        try {
            // محاسبه مبلغ
            $amount = (int) ($scores * $conversionRate);

            // بررسی حداقل امتیاز برای تبدیل
            if ($scores < $minScores) {
                return [
                    'success' => false,
                    'message' => "حداقل {$minScores} امتیاز برای تبدیل لازم است",
                ];
            }

            // بررسی موجودی امتیاز کاربر
            $userTotalScores = $user->currentScore();
            if ($scores > $userTotalScores) {
                return [
                    'success' => false,
                    'message' => 'امتیاز کافی ندارید',
                ];
            }

            // استفاده از transaction برای اطمینان از صحت عملیات
            DB::beginTransaction();

            try {
                // 1. ثبت رکورد منفی در score (کسر امتیاز)
                $score = $user->scores()->create([
                    'description' => "تبدیل {$scores} امتیاز به {$amount} تومان",
                    'score' => -$scores, // امتیاز منفی برای کسر
                ]);

                // 2. محاسبه موجودی جدید کیف پول
                $afterBalance = $user->wallet_balance + $amount;

                // 3. ثبت رکورد افزایش در wallet (افزودن پول)
                $wallet = $user->wallets()->create([
                    'description' => "تبدیل {$scores} امتیاز به {$amount} تومان",
                    'amount' => $amount,
                    'after_balance' => $afterBalance,
                    'type' => 'increase',
                ]);

                // 4. آپدیت موجودی کیف پول کاربر
                $user->update([
                    'wallet_balance' => $afterBalance,
                ]);

                DB::commit();

                Log::info("Scores converted to money: User {$user->id}, Scores: {$scores}, Amount: {$amount}");

                return [
                    'success' => true,
                    'message' => "{$scores} امتیاز با موفقیت به {$amount} تومان تبدیل شد",
                    'amount' => $amount,
                    'scores' => $scores,
                    'score_id' => $score->id,
                    'wallet_id' => $wallet->id,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error("Error converting scores to money: " . $e->getMessage(), [
                'user_id' => $user->id,
                'scores' => $scores,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'خطا در تبدیل امتیاز به پول',
            ];
        }
    }

    /**
     * دریافت امتیاز کل کاربر
     *
     * @param User $user
     * @return int
     */
    public function getTotalScores(User $user): int
    {
        return $user->currentScore();
    }

    /**
     * دریافت تاریخچه امتیازهای کاربر
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScoresHistory(User $user, int $limit = 50)
    {
        return $user->scores()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

