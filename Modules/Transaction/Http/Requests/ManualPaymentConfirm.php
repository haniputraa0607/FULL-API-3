<?php 
 
namespace Modules\Transaction\Http\Requests; 
 
use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Contracts\Validation\Validator; 
use Illuminate\Http\Exceptions\HttpResponseException; 
 
class ManualPaymentConfirm extends FormRequest 
{ 
    public function rules() 
    { 
        return [ 
            'id_transaction_payment_manual'  => 'required', 
            'status'                         => 'required|in:accept,decline', 
            'payment_note_confirm'           => '', 
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