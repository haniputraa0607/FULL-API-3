<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Sync extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "product"                      => "required",
            "product.*.product_code"       => "required",
            "product.*.product_name"       => "required",
            "product.*.product_price"      => "required",
            "product.*.product_visibility" => "in:Visible,Hidden",
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
