<?php

namespace Modules\Plastic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlasticTotalPrice extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'item'    => 'required|array',
            'id_outlet'   => 'required|numeric'
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
