<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class users_new extends FormRequest
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
			'phone'			=> 'required|string|max:18',
			'pin'			=> 'required|string|digits:6',
			'name'			=> 'required|string',
			'email'			=> 'required|email',
			'gender'		=> 'in:Male,Female|nullable',
			'birthday'		=> 'date|nullable',
			'id_kota'		=> 'integer|max:499',
			'is_verified'	=> 'integer',
			'is_admin'		=> 'integer'
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
