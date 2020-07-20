<?php

namespace Modules\OutletApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportSummary extends FormRequest
{
    public function rules()
    {
        return [
            'date'		=> 'required|date',
            'is_all'    => 'nullable',
            'product'	=> 'nullable',
            'modifier'	=> 'nullable'
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
