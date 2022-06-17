<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class user_hair_stylist_create extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
	public function rules()
	{
		return [
			'fullname'		=> 'required|string',
			'email'			=> 'required|email',
			'gender'		=> 'in:Male,Female|nullable',
            'nationality'   => 'required|string',
            'birthplace'    => 'required|string',
			'birthdate'		=> 'required|date_format:"Y-m-d"',
            'marital_status' => 'in:Single,Married,Widowed,Divorced|nullable'
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
