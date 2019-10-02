<?php

namespace Modules\Setting\Http\Requests\FeaturedDeal;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {        
        return [
            'id_deals'=>'required|exists:deals,id_deals',
            'start_date'=>'required|date|after_or_equal:'.date('Y-m-d H:i:s'),
            'end_date'=>'required|date|after_or_equal:start_date',
        ];
    }
    public function all($key=null)
    {
        $post=parent::all($key);
        $start_date=&$post['start_date'];
        $start_date = date('Y-m-d H:i:s',strtotime($start_date));
        $end_date=&$post['end_date'];
        $end_date = date('Y-m-d H:i:s',strtotime($end_date));
        $this->merge($post);
        return $post;
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
