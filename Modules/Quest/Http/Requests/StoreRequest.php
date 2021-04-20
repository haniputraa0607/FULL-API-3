<?php

namespace Modules\Quest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quest.name'                       => 'required|string',
            'quest.publish_start'              => 'required|date',
            'quest.date_start'                 => 'required|date',
            'quest.short_description'    => 'required|string',
            'quest.image'                => 'required|string',
            'detail.*.name'              => 'required|string',
            'detail.*.short_description' => 'required|string',
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
}
