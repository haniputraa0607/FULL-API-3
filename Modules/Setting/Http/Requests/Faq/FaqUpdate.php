<?php

namespace Modules\Setting\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FaqUpdate extends FormRequest
{
    public function rules()
    {
        return [
            'id_faq'    => 'required|integer',
            'question'  => 'required|string',
            'answer'    => 'required|string'
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
