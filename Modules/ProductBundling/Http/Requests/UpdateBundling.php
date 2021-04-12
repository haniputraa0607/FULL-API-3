<?php

namespace Modules\ProductBundling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBundling extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_bundling' => 'required',
            'bundling_name' => 'required',
            'bundling_start' => 'required',
            'bundling_end' => 'required'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
