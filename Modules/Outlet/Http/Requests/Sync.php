<?php

namespace Modules\Outlet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Sync extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'outlet'                      => 'required',
            'outlet.*.outlet_code'        => 'required',
            'outlet.*.outlet_name'        => 'required',
            'outlet.*.outlet_address'     => '',
            'outlet.*.city'               => 'required',
            'outlet.*.outlet_postal_code' => 'integer',
            'outlet.*.outlet_phone'       => 'required',
            'outlet.*.outlet_email'       => 'email',
            'outlet.*.outlet_open_hours'  => 'date_format:"H:i:s"',
            'outlet.*.outlet_close_hours' => 'date_format:"H:i:s"|after:outlet.*.outlet_open_hours',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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
