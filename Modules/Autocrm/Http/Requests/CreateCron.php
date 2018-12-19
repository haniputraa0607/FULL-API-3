<?php 
 
namespace Modules\Autocrm\Http\Requests; 
 
use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Contracts\Validation\Validator; 
use Illuminate\Http\Exceptions\HttpResponseException; 
 
class CreateCron extends FormRequest 
{ 
    /** 
     * Get the validation rules that apply to the request. 
     * 
     * @return array 
     */ 
    public function rules() 
    { 
        return [ 
            'autocrm_title'         => 'required|unique:autocrms,autocrm_title', 
            'autocrm_type'          => 'required', 
            'autocrm_trigger'       => 'required|in:Daily,Weekly,Monthly,Yearly', 
            'autocrm_cron_reference'=> 'required_if:autocrm_trigger,Weekly,Monthly,Yearly', 
            // 'rule'     => 'required' 
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