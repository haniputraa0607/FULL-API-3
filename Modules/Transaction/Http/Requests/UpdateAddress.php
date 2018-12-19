<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAddress extends FormRequest
{
    public function rules()
    {
        return [
            'id_user_address' => 'required|integer',
            'name'            => 'required|string',
            'phone'           => 'required|numeric',
            'id_city'         => 'required|integer',
            'address'         => 'required|string',
            'postal_code'     => 'required|string',
        ];
    }

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
