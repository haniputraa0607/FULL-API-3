<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DetailSubscription extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_subscription'                   => 'required',
            'subscription_title'                => 'required',
            'subscription_sub_title'            => '',
            'subscription_image'                => '',
            'subscription_start'                => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"',
            'subscription_end'                  => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:subscription_start',
            'subscription_publish_start'        => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"',
            'subscription_publish_end'          => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:subscription_publish_start',
            'prices_by'                         => 'required',
            'deals_voucher_price_point'         => '',
            'deals_voucher_price_cash'          => '',
            'id_outlet'                         => 'sometimes|array',
            'subscription_total_type'           => '',
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
            'purchase_limit'                    => 'required',
            'new_purchase_after'                => '',
            'content_title'                     => 'required',
            'id_subscription_content'           => '',
            'id_content_detail'                 => '',
            'visible'                           => '',
            'content_detail'                    => '',
            'content_detail_order'              => '',
            'subscription_description'          => ''
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
