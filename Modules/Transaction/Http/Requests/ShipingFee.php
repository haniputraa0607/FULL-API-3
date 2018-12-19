<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShipingFee extends FormRequest
{
    public function rules()
    {
        return [
            'from'     => 'required|integer',
            'fromType' => 'required|string',
            'to'       => 'required|integer',
            'toType'   => 'required|string',
            'weight'   => 'required|integer',
            'courier'  => 'required|string'
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
