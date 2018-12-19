<?php

namespace Modules\Enquiries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Update extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_enquiry'      => 'required|integer',
            'enquiry_name'    => 'sometimes|required',
            'enquiry_phone'   => 'sometimes|required',
            'enquiry_email'   => '',
            'enquiry_subject' => 'sometimes|required|in:Question,Complaint,Partnership',
            'enquiry_content' => 'sometimes|required',
            'enquiry_photo'   => '',
            'enquiry_status'  => 'sometimes|in:Read,Unread'
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
