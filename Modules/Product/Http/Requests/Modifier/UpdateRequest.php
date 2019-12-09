<?php

namespace Modules\Product\Http\Requests\Modifier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_product_modifier' => 'numeric|required',
            'modifier_type'       => 'in:Global,Specific|required',
            'type'                => 'string|required',
            'code'                => 'string|required',
            'text'                => 'string|required',
            'id_brand'            => 'array|nullable|sometimes',
            'id_product_category' => 'array|nullable|sometimes',
            'id_product'          => 'array|nullable|sometimes'
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
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'fail', 'messages'  => $validator->errors()->all()], 200));
    }

    protected function validationData()
    {
        return $this->json()->all();
    }
}
