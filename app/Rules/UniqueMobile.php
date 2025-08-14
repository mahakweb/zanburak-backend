<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UniqueMobile implements Rule
{
    protected $ignoreUserId;
    protected $country_dial_code;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($ignoreUserId = null, $country_dial_code)
    {
        $this->ignoreUserId = $ignoreUserId;
        $this->country_dial_code = $country_dial_code;
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
        $query = User::where('mobile', $this->country_dial_code . $value);

        if ($this->ignoreUserId) {
            $query->where('id', '!=', $this->ignoreUserId);
        }

        return !$query->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'این شماره موبایل قبلاً ثبت شده است.';
    }
}
