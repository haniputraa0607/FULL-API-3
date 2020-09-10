<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\InBankMethod;
use App\Rules\InBanks;
use App\Rules\InManualPayment;

class Paid extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        'id_subscription'        => 'required|integer',
        'payment_method'         => 'required|in:midtrans,manual,balance,ipay88,shopeepay',
        'id_manual_payment'      => ['required_if:payment_deals,manual', new InManualPayment],
        'id_bank_method'         => ['required_if:payment_deals,manual', new InBankMethod],
        'id_bank'                => ['required_if:payment_deals,manual', new InBanks],
        'payment_date'           => 'required_if:payment_deals,manual',
        'payment_time'           => 'required_if:payment_deals,manual',
        'payment_bank'           => 'required_if:payment_deals,manual',
        'payment_method'         => 'required_if:payment_deals,manual',
        'payment_account_number' => 'required_if:payment_deals,manual',
        'payment_account_name'   => 'required_if:payment_deals,manual',
        'payment_nominal'        => 'required_if:payment_deals,manual',
        'payment_receipt_image'  => 'required_if:payment_deals,manual',
        'payment_note'           => 'required_if:payment_deals,manual',
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
