<?php

namespace Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Rules\UrlImage;

class reqBulkMenu extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'api_key'		            => 'required',
            'api_secret'	            => 'required',
            'store'                     => 'required|array',
            'store.*.store_code'	    => 'required',
            'store.*.store_name'	    => 'nullable',
            'store.*.menu'			    => 'required|array',
            'store.*.menu.*.plu_id'	    => 'required',
            'store.*.menu.*.name'		=> 'required',
            'store.*.menu.*.category'	=> 'required',
            'store.*.menu.*.price'	    => 'required',
            'store.*.menu.*.price_base'	=> 'nullable',
            'store.*.menu.*.price_tax'	=> 'nullable',
            'store.*.menu.*.status'	    => 'required',
            'store.*.menu.*.photo'      => 'nullable|array',
            'store.*.menu.*.photo.*.url' => ['nullable', 'url', new UrlImage],
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
