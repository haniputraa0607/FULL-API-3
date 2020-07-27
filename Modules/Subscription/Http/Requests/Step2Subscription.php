<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Step2Subscription extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        	'subscription_type'         		=> 'required|in:welcome,subscription,inject',
            'id_subscription'                   => 'required',
            'prices_by'                         => 'sometimes|required',
            'deals_voucher_price_point'         => '',
            'deals_voucher_price_cash'          => '',
            'id_outlet'                         => 'sometimes|array',
            'subscription_total_type'                => '',
            'subscription_total'                => '',
            'user_limit'                        => 'required',
            'subscription_voucher_start'        => '',
            'subscription_voucher_expired'      => '',
            'subscription_voucher_duration'     => '',
            'subscription_voucher_total'        => 'required',
            'voucher_type'                      => 'required',
            'subscription_voucher_percent'      => '',
            'subscription_voucher_nominal'      => '',
            'subscription_voucher_percent_max'  => '',
            'subscription_minimal_transaction'  => '',
            'purchase_limit'                    => 'sometimes|required',
            'new_purchase_after'                => '',
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
