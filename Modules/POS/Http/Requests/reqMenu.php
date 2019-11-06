<?php

namespace Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Rules\UrlImage;

class reqMenu extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'api_key'		    => 'required',
            'api_secret'	    => 'required',
            'store_code'	    => 'required',
            'menu'			    => 'required|array',
            'menu.*.brand_code' => 'required',
            'menu.*.plu_id'	    => 'required',
            'menu.*.name'		=> 'required',
            'menu.*.category'	=> 'required',
            'menu.*.price'	    => 'required',
            'menu.*.price_base'	=> 'nullable',
            'menu.*.price_tax'	=> 'nullable',
            'menu.*.status'	    => 'required',
            'menu.*.photo'      => 'nullable|array',
            'menu.*.photo.*.url' => ['nullable', 'url', new UrlImage],
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
