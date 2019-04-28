<?php

namespace Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class reqTransaction extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'api_key'                             => 'required',
            'api_secret'                          => 'required',
            'store_code'                          => 'required',
            'transactions'                        => 'required|array',
            'transactions.*.member_uid'           => 'nullable',
            'transactions.*.trx_id'               => '',
            'transactions.*.sales_type'           => 'nullable',
            'transactions.*.order_id'             => 'nullable',
            'transactions.*.date_time'            => 'required|date_format:Y-m-d H:i:s',
            'transactions.*.cashier'              => 'required',
            'transactions.*.total'                => 'required',
            'transactions.*.service'              => 'required',
            'transactions.*.tax'                  => 'required',
            'transactions.*.discount'             => 'required',
            'transactions.*.grand_total'          => 'required',
            'transactions.*.payments'             => 'required|array',
            'transactions.*.payments.*.type'      => 'required',
            'transactions.*.payments.*.name'      => 'required',
            'transactions.*.payments.*.nominal'   => 'required',
            // 'transactions.*.menu'                 => 'required|array',
            'transactions.*.menu.*.plu_id'        => 'required',
            'transactions.*.menu.*.name'          => 'required',
            'transactions.*.menu.*.price'         => 'required',
            'transactions.*.menu.*.qty'           => 'required',
            'transactions.*.menu.*.category'      => 'required',
            'transactions.*.voucher'              => 'nullable|array',
            'transactions.*.voucher.voucher_code' => 'nullable|string'
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
