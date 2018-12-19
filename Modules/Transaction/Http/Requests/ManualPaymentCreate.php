<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ManualPaymentCreate extends FormRequest
{
    public function rules()
    {
        return [
            'is_virtual_account'    => 'required',
            'manual_payment_name'   => 'required|string',
            'account_name'  => 'required|string',
            'account_number'    => 'required|numeric',
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
