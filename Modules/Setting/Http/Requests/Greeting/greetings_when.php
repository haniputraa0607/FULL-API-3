<?php

namespace Modules\Setting\Http\Requests\Greeting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class greetings_when extends FormRequest
{
    public function authorize()
    {
        return true;
    }

	public function rules()
	{
		return [
            'when' => ''
        ];
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
