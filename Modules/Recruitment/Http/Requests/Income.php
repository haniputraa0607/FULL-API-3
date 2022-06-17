<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
class Income extends FormRequest
{
    public function rules()
    {
        return [
            'month'        => 'required|date_format:Y-m',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $survey = HairstylistGroup::where(array('hair_stylist_group_code'=>$value))->count();
         if($survey == 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'date_format' => 'Format tanggal yaitu tahun-bulan (2020-01)',
            'unique' => ':attribute tidak boleh duplikat',
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
