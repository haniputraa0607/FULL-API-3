<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
class UpdateGroup extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group'        => 'required',
            'hair_stylist_group_name'        => 'required',
            'hair_stylist_group_code'        => 'required|unik',
            'hair_stylist_group_description' => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $request = $validator->getData();
         $survey = HairstylistGroup::where(array('hair_stylist_group_code'=>$value))->where('id_hairstylist_group','=',$request['id_hairstylist_group'])->count();
         if($survey > 0){
             return true;
         }
         $survey2 = HairstylistGroup::where(array('hair_stylist_group_code'=>$value))->where('id_hairstylist_group','!=',$request['id_hairstylist_group'])->count();
         if($survey2 == 0){
         return true;
         }
         return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => ':attribute tidak boleh duplikat',
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
