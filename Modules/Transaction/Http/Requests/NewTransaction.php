<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NewTransaction extends FormRequest
{
    public function rules()
    {
        return [
            'item'    => 'required|array',
            'courier' => 'required|string',
            'service' => 'required|string',
            'cost'    => 'required|integer',
            'etd'     => 'required|string',
            'name'    => 'nullable|string',
            'phone'   => 'nullable|numeric',
            'address' => 'nullable|string',
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
