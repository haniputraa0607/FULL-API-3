<?php

namespace Modules\News\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Create extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'news_slug'             => 'required|unique:news,news_slug',
            'news_title'            => 'required',
            'news_content_short'    => 'required',
            'news_content_long'     => 'required',
            'news_video'            => ["nullable", "regex:/^(http(s)?:\/\/)?((w){3}.)?youtu(be|.be)?(\.com)?\/.+/"],
            'news_image_luar'       => '',
            'news_image_dalam'      => '',
            'news_post_date'        => 'date|date_format:"Y-m-d H:i:s"',
            'news_publish_date'     => 'date|date_format:"Y-m-d H:i:s"',
            'news_expired_date'     => 'nullable|date|date_format:"Y-m-d H:i:s"|after_or_equal:news_publish_date',
            'news_event_date_start'       => 'nullable|date|date_format:"Y-m-d"',
            'news_event_date_end'         => 'nullable|date|date_format:"Y-m-d"|after_or_equal:news_event_date_start',
            'news_event_time_start'       => 'nullable|date_format:"H:i:s"',
            'news_event_time_end'         => 'nullable|date_format:"H:i:s"|after:news_event_time_start',
            'news_event_location_name'    => '',
            'news_event_location_phone'   => '',
            'news_event_location_address' => '',
            'news_event_location_map'     => '',
            'news_event_latitude'         => '',
            'news_event_longitude'        => '',
            'news_outlet_text'      => '',
            'news_product_text'     => '',
            'news_treatment_text'   => ''
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
