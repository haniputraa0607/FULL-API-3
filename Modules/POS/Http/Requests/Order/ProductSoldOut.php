<?php

namespace Modules\POS\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductSoldOut extends FormRequest
{
    public function rules()
    {
        return [
            'api_key'		=> 'required',
            'api_secret'	=> 'required',
            'store_code'	=> 'required',
            'plu_id'        => 'required',
            'product_stock_status'  => 'required|in:Available,Sold Out',
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
