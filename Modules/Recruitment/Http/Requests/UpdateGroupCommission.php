<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
class UpdateGroupCommission extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group'        => 'required',
            'id_product'                  => 'required|unik',
            'commission_percent'          => 'required|cek',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $request = $validator->getData();
         $survey = HairstylistGroupCommission::where(array('id_product'=>$value,'id_hairstylist_group'=>$request['id_hairstylist_group']))->count();
         if($survey == 1){
             return true;
         } return false;
        }); 
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $request = $validator->getData();
         if(isset($request['percent'])){
             if((int)$value>=1&&(int)$value<=99 ){
                 return true;
             }else{
                 return false;
             }
         }else{
                 return true;
         }
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unique' => 'Data tidak ada',
            'cek' => 'Percent maksimal minimal 1% maksimal 99%. Nominal'
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
