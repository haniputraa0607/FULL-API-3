<?php

namespace Modules\ProductBundling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBundling extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'bundling_name' => 'required|max:50',
            'bundling_description' => 'required',
            'price' => 'required|numeric',
            'discount' => 'required',
            'all_outlet' => 'required',
            'created_by' => 'required|integer',
            'id_bundling' => 'required|integer',
            'jumlah' => 'required|integer',
            'id_brand' => 'required',
            'id_product' => 'required',
            'id_outlet' => 'required'
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
