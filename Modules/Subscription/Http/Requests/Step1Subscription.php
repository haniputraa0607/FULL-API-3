<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Step1Subscription extends FormRequest
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
            'subscription_publish_end'          => 'sometimes|nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:subscription_publish_start'
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
