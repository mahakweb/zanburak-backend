<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class JalalianBirthDateParts implements Rule
{
    protected $errorType;
    protected $currentJalaliYear;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->currentJalaliYear = Jalalian::fromCarbon(Carbon::now())->getYear();
        $this->errorType = '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_null($value) || trim($value) === '') {
            return true;
        }
        
        if (!preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $value)) {
            $this->errorType = 'فرمت تاریخ باید به صورت yyyy/mm/dd باشد.';
            return false;
        }

        [$year, $month, $day] = explode('/', $value);

        if (strlen($year) !== 4) {
            $this->errorType = 'سال باید 4 رقم باشد.';
            return false;
        }
        if (strlen($month) !== 2) {
            $this->errorType = 'ماه باید 2 رقم باشد.';
            return false;
        }
        if (strlen($day) !== 2) {
            $this->errorType = 'روز باید 2 رقم باشد.';
            return false;
        }

        if ($year < 1300 || $year > $this->currentJalaliYear) {
            $this->errorType = 'سال باید بین 1300 تا ' . $this->currentJalaliYear . ' باشد.';
            return false;
        }

        if ($month < 1 || $month > 12) {
            $this->errorType = 'ماه باید بین 01 تا 12 باشد.';
            return false;
        }

        if ($day < 1 || $day > 31) {
            $this->errorType = 'روز باید بین 01 تا 31 باشد.';
            return false;
        }

        try {
            Jalalian::fromFormat('Y/m/d', $value);
        } catch (\Exception $e) {
            $this->errorType = 'تاریخ وارد شده معتبر نیست.';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorType ?: 'تاریخ وارد شده نامعتبر است.';
    }
}
