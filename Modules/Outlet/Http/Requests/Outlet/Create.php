<?php

namespace Modules\Outlet\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Create extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'outlet_code'        => 'unique:outlets,outlet_code',
            'outlet_name'        => 'required',
            'outlet_address'     => '',
            'id_city'            => 'required|integer',
            'outlet_phone'       => '',
            'outlet_email'       => 'email',
            'outlet_latitude'    => '',
            'outlet_longitude'   => '',
            'outlet_open_hours'  => 'date_format:"H:i:s"',
            // 'outlet_close_hours' => 'date_format:"H:i:s"|after:outlet_open_hours',
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
