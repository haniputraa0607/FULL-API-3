<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
class InviteHS extends FormRequest
{
    public function rules()
    {
        return [
            'id_user_hair_stylist'        => 'required',
            'id_hairstylist_group'        => 'required',
           ]; 
    }
  
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            
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
