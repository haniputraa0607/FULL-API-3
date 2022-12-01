<?php

namespace Modules\Merchant\Http\Requests\UserReseller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Merchant\Entities\UserResellerMerchant;
use Illuminate\Support\Facades\Auth;

class Register extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function withValidator($validator)
    {
        $validator->addExtension('cek_pending', function ($attribute, $value, $parameters, $validator) {
            $user =  Auth::user();
            $pending = UserResellerMerchant::where(array(
            'id_user' => $user['id'],
            'id_merchant' => $value,
            'reseller_merchant_status' => 'Pending'
            ))->first();
            if ($pending) {
                return false;
            }
            return true;
        });
        $validator->addExtension('cek_active', function ($attribute, $value, $parameters, $validator) {
            $user =  Auth::user();
            $pending = UserResellerMerchant::where(array(
            'id_user' => $user['id'],
            'id_merchant' => $value,
            'reseller_merchant_status' => 'Active'
            ))->first();
            if ($pending) {
                return false;
            }
            return true;
        });
    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'cek_pending' => 'Pengajuan reseller sudah ditambahkan',
            'cek_active' => 'Anda sudah menjadi reseller dari merchant ini',
        ];
    }
    public function rules()
    {
        return [
            'id_merchant'               => 'required|cek_pending|cek_active|exists:merchants',
            'notes_user'                => 'required',
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
