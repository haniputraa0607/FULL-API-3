<?php

namespace Modules\Deals\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Create extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'deals_type'                => 'required|in:Deals,Hidden,Point,Spin,Subscription,WelcomeVoucher',
            'deals_voucher_type'        => 'nullable|required|in:Auto generated,List Vouchers,Unlimited',
            'deals_promo_id'            => 'nullable',
            'deals_title'               => 'required',
            'deals_second_title'        => '',
            'deals_description'         => '',
            'deals_short_description'   => '',
            'deals_image'               => '',
            'deals_video'               => '',
            'id_product'                => 'nullable|integer',
            'deals_start'               => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:'.date('Y-m-d').'',
            'deals_end'                 => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:deals_start',
            'deals_publish_start'       => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"',
            'deals_publish_end'         => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:deals_publish_start',
            'deals_voucher_duration'    => '',
            'deals_voucher_start'     => 'nullable|date|date_format:"Y-m-d H:i:s"|after:deals_start',
            'deals_voucher_expired'     => 'nullable|date|date_format:"Y-m-d H:i:s"|after:deals_voucher_start',
            'deals_voucher_price_point' => '',
            'deals_voucher_price_cash'  => '',
            'deals_total_voucher'       => '',
            'deals_total_claimed'       => '',
            'deals_total_redeemed'      => '',
            'deals_total_used'          => '',
            'id_outlet'                 => 'sometimes|array'
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
