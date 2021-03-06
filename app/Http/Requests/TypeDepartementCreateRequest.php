<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypeDepartementCreateRequest extends TypeDepartementRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can(\App\TypeDepartement::cancreate());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return \App\TypeDepartement::createRules();
    }
}
