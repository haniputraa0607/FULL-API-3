<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use App\Http\Models\Product;


class CreateGroupCommission extends FormRequest
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
         if($survey == 0){
             return true;
         } return false;
        }); 
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $request = $validator->getData();
         $global = Product::where(array('products.id_product'=>$request['id_product']))->join('product_global_price','product_global_price.id_product','products.id_product')->first();
         if(isset($request['percent'])){
             if($value>=1&& $value<=99 ){
                 return true;
             }else{
                 return false;
             }
         }else{
             if($global){
                 if($global->product_global_price > $value){
                      return true;
                 }return false;
             }else{
                 return true;
             }
         }
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Produk sudah ada ',
            'cek' => 'Percent maksimal minimal 1% maksimal 99%. Nominal commission lebih besar dari pada harga product'
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
