<?php

namespace Modules\IPay88\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class Ipay88Response extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          "MerchantCode" => 'string',
          "PaymentId" => 'sometimes',
          "RefNo" => 'string',
          "Amount" => 'integer',
          "Currency" => 'string',
          "Remark" => 'sometimes',
          "TransId" => 'string',
          "AuthCode" => 'sometimes',
          "Status" => 'string',
          "ErrDesc" => 'sometimes',
          "Signature" => 'sometimes',
          'xfield1' => 'sometimes'
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
        return $this->post();
    }
}
