<?php

namespace Modules\Outlet\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class Update extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        return [
            'id_outlet'          => 'integer|required',
            'outlet_code'        => 'required|unique:outlets,outlet_code,'.$request->json('id_outlet').',id_outlet|max:3',
            'outlet_name'        => 'required',
            'outlet_address'     => '',
            'id_city'            => 'required|integer',
            'outlet_postal_code' => '',
            'outlet_phone'       => '',
            'outlet_email'       => 'email',
            'outlet_latitude'    => '',
            'outlet_longitude'   => '',
            // 'outlet_open_hours'  => 'date_format:"H:i:s"',
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
