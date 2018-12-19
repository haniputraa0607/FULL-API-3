<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class users_profile extends FormRequest
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
			'phone'		=> 'required|string|max:18',
			'pin'		=> 'required|string|digits:6',
			'name'		=> 'required|max:200',
			'email'		=> 'required|email',
			'gender'	=> 'required|in:Male,Female',
			'birthday'	=> 'required|date',
			'id_city'	=> 'required|integer|max:501'
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
