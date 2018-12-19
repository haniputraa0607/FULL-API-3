<?php

namespace Modules\Deals\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListDeal extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'deals_type'                => 'required|in:Deals,Hidden,Point,Spin,Subscription',
            'publish'                   => '',
            'voucher_type'              => 'nullable|in:point,paid,free',
            'price_range_start'         => 'nullable|integer',
            'price_range_end'           => 'nullable|integer',
            'key_free'                  => '',
            'alphabetical'              => '',
            'point_range'               => 'nullable|in:0-50,50-100,100-300,500+',
            '050'                       => 'nullable|in:0-50',
            '50100'                     => 'nullable|in:50-100',
            '100300'                    => 'nullable|in:100-300',
            '300500'                    => 'nullable|in:300-500',
            '500up'                     => 'nullable|in:500+',
            'voucher_type_paid'         => '',
            'voucher_type_point'        => '',
            'voucher_type_free'         => '',
            'newest'                    => '',
            'oldest'                    => '',
            'highest_available_voucher' => '',
            'lowest_available_voucher'  => '',
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
