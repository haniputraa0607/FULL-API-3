<?php

namespace Modules\Outlet\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Reservation extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'day'        => 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'hour_start' => 'date_format:"H:i"',
            'hour_end'   => 'date_format:"H:i"|after:hour_start',
            'id_outlet'  => 'integer',
            'limit'      => ''
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
