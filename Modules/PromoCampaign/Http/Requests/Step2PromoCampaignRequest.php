<?php

namespace Modules\PromoCampaign\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Step2PromoCampaignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if($this->json('promo_type')=='Product Discount'){
            $rules=[
                'filter_user'       => 'required',
                'filter_outlet'     => 'required',
                'filter_product'    => 'required',
                'max_product'       => 'required',
                'discount_type'     => 'required',
                'discount_value'    => 'required'
            ];
        }elseif ($this->json('promo_type')=='Tier discount') {
            $rules=[
                'filter_user'           => 'required',
                'filter_outlet'         => 'required',
                'product'               => 'required',
                'discount_type'         => 'required',
                'promo_rule'                  => 'required',
                'promo_rule.*.min_qty'        => 'required|numeric|min:1',
                'promo_rule.*.max_qty'        => 'required|numeric|min:1',
                'promo_rule.*.discount_value' => 'required|numeric|min:1'
            ];
            if($this->json('discount_type')=='Percent'){
                $rules['promo_rule.*.discount_value'] = 'required|numeric|min:1|max:100';
            }
        }elseif ($this->json('promo_type')=='Buy X Get Y') {
            $rules=[
                'filter_user'                       => 'required',
                'filter_outlet'                     => 'required',
                'product'                           => 'required',
                'promo_rule'                              => 'required',
                'promo_rule.*.min_qty_requirement'        => 'required|numeric|min:1',
                'promo_rule.*.max_qty_requirement'        => 'required|numeric|min:1',
                // 'promo_rule.*.benefit_qty'                => 'required|numeric|min:0',
                'promo_rule.*.benefit_id_product'         => 'required',
                'promo_rule.*.discount_percent'           => 'nullable|numeric|min:1|max:100',
                'promo_rule.*.discount_nominal'           => 'nullable|numeric'
            ];
        }
        return $rules;
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


    public function messages()
    {
        return [
            'product.integer' => 'Product is required.'
        ];
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
