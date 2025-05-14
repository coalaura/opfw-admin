<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Rules\NullableStringRule;

class CharacterUpdateRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['string', 'required'],
            'last_name' => ['string', 'required'],
            'date_of_birth' => ['string', 'required'],
            'gender' => [Rule::in([0, 1]), 'required'],
            'backstory' => ['string', 'required'],
            'job_name' => [new NullableStringRule()],
            'department_name' => [new NullableStringRule()],
            'position_name' => [new NullableStringRule()],
        ];
    }

}
