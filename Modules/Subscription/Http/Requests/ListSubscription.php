<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListSubscription extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            'publish'                   => 'nullable',
            'subscription_type'         => 'nullable|in:point,paid,free,welcome,subscription,inject',
            'subscription_type_paid'    => 'nullable',
            'subscription_type_point'   => 'nullable',
            'subscription_type_free'    => 'nullable',
            'key_free'                  => 'nullable',

            'price_range_start'         => 'nullable|integer',
            'price_range_end'           => 'nullable|integer',
            'point_range_start'         => 'nullable|integer',
            'point_range_end'           => 'nullable|integer',

            'alphabetical'              => 'nullable',
            'newest'                    => 'nullable',
            'oldest'                    => 'nullable',

            'highest_available_subscription' => 'nullable',
            'lowest_available_subscription'  => 'nullable',
            'highest_point'             => 'nullable',
            'lowest_point'              => 'nullable',
            'highest_price'             => 'nullable',
            'lowest_price'              => 'nullable'

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
