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
			'pin'		=> 'nullable|string|min:8|max:10',
            'pin_new'	=> 'nullable|string|digits:6',
            'pin_new'	=> 'nullable|string|min:8|max:10',
			'name'		=> 'nullable|max:200',
			'email'		=> 'nullable|email',
			'gender'	=> 'nullable|in:Male,Female',
			'birthday'	=> 'nullable|date',
			'id_city'	=> 'nullable|integer'
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
