<?php

namespace App\Http\Requests\users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class UserUpdateByPhone extends FormRequest
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
            'phone' => 'required|string|min:8|max:15',
            'name' => 'required|string',
            'email' => 'required|sometimes|email',
			'password' => 'required|numeric|digits:6',
            'gender' => 'required|in:Male,Female',
            'id_village' => 'required|sometimes|exists:villages,id_village',
            'address' => 'required|sometimes|max:255',
            'birthday' => 'required|sometimes|date',
            'level' => 'required|sometimes|in:Super Admin,Admin,Customer',
            'mypassword' => 'required'
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
