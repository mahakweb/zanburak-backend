<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PhoneNumberLength implements Rule
{
    protected $countryCode;
    protected $phoneNumberLengths;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($countryCode)
    {
        $this->countryCode = $countryCode;

        $path = public_path('assets/json/countries-with-number-length.json');
        $this->phoneNumberLengths = json_decode(file_get_contents($path), true);
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
        if (isset($this->phoneNumberLengths)) {
            foreach ($this->phoneNumberLengths as $country) {
                if ($country['code'] === $this->countryCode) {
                    return strlen($value) === $country['mobile_number_length'];
                }
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "تعداد ارقام شماره موبایل برای کشور {$this->countryCode} نامعتبر است.";
    }
}
