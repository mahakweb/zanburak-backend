<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckCurrentMobile implements Rule
{
    protected $countryDialCode;
    protected $currentMobile;

    /**
     * Create a new rule instance.
     *
     * @param  string  $currentMobile
     * @param  string  $countryDialCode
     * @return void
     */
    public function __construct($currentMobile, $countryDialCode)
    {
        $this->currentMobile = $currentMobile;
        $this->countryDialCode = $countryDialCode;
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
        return $this->currentMobile !== $this->countryDialCode . $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'شماره موبایل فعلی با شماره جدید باید متفاوت باشد';
    }
}
