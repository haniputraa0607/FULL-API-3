<?php

namespace Modules\Transaction\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NewTransaction extends FormRequest
{
    public function rules()
    {
        return [
            'item'                     => 'required|array',
            'id_outlet'                => 'required_unless:type,Advance Order|integer',
            'type'                     => 'required|in:Delivery,Pickup Order,GO-SEND,Advance Order',
            'notes'                    => 'nullable|string',
            'pickup_type'              => 'required_if:type,Pickup Order|in:set time,right now,at arrival',
            'pickup_at'                => 'required_if:pickup_type,set time|date_format:Y-m-d H:i:s',
            'payment_type'             => 'nullable|in:Midtrans,Manual,Balance,Ovo,Ipay88,Shopeepay',
            
            'shipping'                 => 'required_if:type,Delivery|integer',
            'cour_service'             => 'nullable|string',
            'cour_etd'                 => 'nullable|string',
            'id_user_address'          => 'required_if:type,Delivery|integer',
            
            // 'destination.name'         => 'required_if:type,GO-SEND',
            // 'destination.phone'        => 'required_if:type,GO-SEND',
            'destination.address'      => 'required_if:type,GO-SEND',
            'destination.latitude'     => 'required_if:type,GO-SEND',
            'destination.longitude'    => 'required_if:type,GO-SEND',
            'destination.description'  => 'nullable',
            'destination.id_user_address' => 'nullable',
            'destination.type'  => 'nullable',
            
            // 'id_manual_payment_method' => 'required_if:payment_type,Manual|integer',
            // 'payment_date'             => 'required_if:payment_type,Manual|date_format:Y-m-d',
            // 'payment_time'             => 'required_if:payment_type,Manual|date_format:H:i:s',
            // 'payment_bank'             => 'required_if:payment_type,Manual|string',
            // 'payment_method'           => 'required_if:payment_type,Manual|string',
            // 'payment_method'           => 'required_if:payment_type,Manual|string',
            // 'payment_account_number'   => 'required_if:payment_type,Manual|numeric',
            // 'payment_account_name'     => 'required_if:payment_type,Manual|string',
            // 'payment_receipt_image'    => 'required_if:payment_type,Manual',
        ];
    }

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
