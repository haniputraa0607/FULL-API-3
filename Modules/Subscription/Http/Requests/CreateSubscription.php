<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSubscription extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'subscription_title'                => 'required',
            'subscription_sub_title'            => '',
            'subscription_image'                => '',
            'subscription_start'                => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:'.date('Y-m-d').'',
            'subscription_end'                  => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:subscription_start',
            'subscription_publish_start'        => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"',
            'subscription_publish_end'          => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:subscription_publish_start',
            'subscription_voucher_price_point'  => '',
            'subscription_voucher_price_cash'   => '',
            'subscription_description'          => '',
            'subscription_voucher_duration'     => '',
            'subscription_voucher_start'        => 'nullable|date|date_format:"Y-m-d H:i:s"|after:subscription_start',
            'subscription_voucher_expired'      => 'nullable|date|date_format:"Y-m-d H:i:s"|after:subscription_voucher_start',
            'subscription_total'                => '',
            'user_limit'                        => '',
            'subscription_voucher_total'        => '',
            'subscription_voucher_nominal'      => '',
            'subscription_voucher_percent'      => '',
            'subscription_voucher_percent_max'  => '',
            'subscription_minimal_transaction'  => '',
            'id_outlet'                         => 'sometimes|array',
            'id_brand'                          => 'required|exists:brands,id_brand',
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
