<?php

namespace Modules\Reward\Http\Requests;

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
            'id_reward'             => 'required',
            'reward_name'           => 'required',
            'reward_description'    => 'required',
            'reward_image'          => '',
            'reward_coupon_point'   => 'required',
            'reward_start'          => 'date',
            'reward_end'            => 'date|after_or_equal:reward_start',
            'reward_publish_start'  => 'date',
            'reward_publish_end'    => 'date|after_or_equal:reward_publish_start',
            'count_winner'          => 'required|integer',
            'winner_type'           => 'required|in:Choosen,Highest Coupon,Random',
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
