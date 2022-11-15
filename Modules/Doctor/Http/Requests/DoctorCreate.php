<?php

namespace Modules\Doctor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DoctorCreate extends FormRequest
{
    public function rules()
    {
        return [
            'doctor_name' => 'required|string',
            'doctor_phone' => 'required|string',
            'gender' => 'required|string',
            'birthday' => 'required|string',
            'id_outlet' => 'required|integer',
            'doctor_session_price' => 'required|string',
            'alumni' => 'required|string',
            'registration_certificate_number' => 'required|string',
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
        return $this->all();
    }
}
