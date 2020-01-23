<?php

namespace Modules\PromoCampaign\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Step1PromoCampaignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'campaign_name'         => 'required',
            'promo_title'           => 'required',
            'promo_tag.*'           => 'nullable',
            'date_start'            => 'required',
            'date_end'              => 'required',
            'id_promo_campaign'     => 'nullable',

            'code_type'             => 'required',
            // 'promo_code'            => 'sometimes|required_if:code_type,==,single|max:15|regex:/^[abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]+$/u',
            // 'prefix_code'           => 'sometimes|required_if:code_type,==,multiple|max:15|regex:/^[abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]+$/u',
            // 'number_last_code'      => 'sometimes|required_if:code_type,==,multiple|max:2',
            'limitation_usage'      => 'required',
            'total_coupon'          => 'required'
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

    protected function getValidatorInstance() {
        $validator = parent::getValidatorInstance();

        $validator->sometimes('promo_code', 'required|max:15|regex:/^[abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]+$/u', function($input) {
            return ($input->code_type == 'single');
        });

        $validator->sometimes('prefix_code', 'required|max:15|regex:/^[abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]+$/u', function($input) {
            return ($input->code_type == 'multiple');
        });

        $validator->sometimes('number_last_code', 'required|max:2', function($input) {
            return ($input->code_type == 'multiple');
        });

        return $validator;
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
