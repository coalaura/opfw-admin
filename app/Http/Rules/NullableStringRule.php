<?php
namespace App\Http\Rules;

use Illuminate\Contracts\Validation\Rule;

class NullableStringRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) || $value === false || $value === null;
    }

    public function message(): string
    {
        return 'The :attribute must be a string, null or false.';
    }
}
