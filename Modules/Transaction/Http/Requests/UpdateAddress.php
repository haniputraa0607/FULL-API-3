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
            'receiver_name'  => 'required|string',
            'receiver_phone'  => 'required|string',
            'receiver_email'  => 'required|string',
            'id_city'   => 'required|integer',
            'id_subdistrict'  => 'required|integer',
            'address'   => 'required|string'
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
