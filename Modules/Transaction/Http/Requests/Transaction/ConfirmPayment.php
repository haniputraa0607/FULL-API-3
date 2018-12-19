<?php

namespace Modules\Transaction\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmPayment extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment_type'             => 'required|in:Midtrans,Manual,Balance',
            'id'                       => 'required|string',
            'id_manual_payment_method' => 'nullable|integer',
            'id_bank_method'           => 'required_if:payment_type,Manual|integer',
            'id_bank'                  => 'required_if:payment_type,Manual|integer',
            'id_manual_payment'        => 'required_if:payment_type,Manual|integer',
            'payment_date'             => 'required_if:payment_type,Manual|date_format:Y-m-d',
            'payment_time'             => 'required_if:payment_type,Manual|date_format:H:i:s',
            'payment_bank'             => 'required_if:payment_type,Manual|string',
            'payment_method'           => 'required_if:payment_type,Manual|string',
            'payment_method'           => 'required_if:payment_type,Manual|string',
            'payment_account_number'   => 'required_if:payment_type,Manual|numeric',
            'payment_account_name'     => 'required_if:payment_type,Manual|string',
            'payment_receipt_image'    => 'required_if:payment_type,Manual',
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
