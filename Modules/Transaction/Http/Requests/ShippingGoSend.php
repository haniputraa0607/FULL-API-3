<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShippingGoSend extends FormRequest
{
    public function rules()
    {
        return [
            'origin.latitude'       => 'required',
            'origin.longitude'      => 'required',
            'destination.latitude'  => 'required',
            'destination.longitude' => 'required',
            'subtotal'              => 'required|integer',
            'total_item'            => 'required|integer',
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
