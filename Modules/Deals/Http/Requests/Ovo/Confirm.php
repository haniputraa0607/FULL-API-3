<?php

namespace Modules\Deals\Http\Requests\Ovo;

use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Contracts\Validation\Validator; 
use Illuminate\Http\Exceptions\HttpResponseException; 

class Confirm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_deals_user' => 'exists:deals_users,id_deals_user',
            'phone' => 'required|numeric'
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
