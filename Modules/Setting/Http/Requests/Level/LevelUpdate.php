<?php

namespace Modules\Setting\Http\Requests\Level;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LevelUpdate extends FormRequest
{
    public function rules()
    {
        return [
            'id_level'          => 'required|integer',
            'level_name'        => 'required|string',
            'level_parameters'  => 'required|string',
            'level_range_start' => 'required|numeric',
            'level_range_end'   => 'required|numeric',
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
        return $this->json()->all();
    }
}
