<?php

namespace Modules\Report\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTrxReport extends FormRequest
{
    public function rules()
    {
        return [
            'date_start' => 'required|date|date_format:"Y-m-d"',
            'date_end'   => 'required|date|date_format:"Y-m-d"|after_or_equal:date_start'
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
