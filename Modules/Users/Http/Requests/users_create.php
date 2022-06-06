<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class users_create extends FormRequest
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
			'phone'			=> 'required|unique:users,phone|max:18',
			'pin'			=> [
                'nullable',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
            ],
			'name'			=> 'required|string',
			'email'			=> 'required|email|unique:users,email',
			'gender'		=> 'in:Male,Female|nullable',
			'birthday'		=> 'date_format:"m/d/Y"|nullable',
			'id_city'		=> 'integer|max:501',
			'is_verified'	=> 'integer',
			'level'			=> 'in:Admin,Admin Outlet,Customer,',
			'sent_pin'		=> 'required|in:Yes,No'
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
