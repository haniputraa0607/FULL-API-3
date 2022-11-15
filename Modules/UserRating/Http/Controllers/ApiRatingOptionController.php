<?php

namespace Modules\UserRating\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UserRating\Entities\RatingOption;
use App\Lib\MyHelper;

class ApiRatingOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $ratingData = [];
        if ($request->outlet) {
            $ratingData = RatingOption::where('rating_target', 'outlet')->get()->toArray();
        } elseif ($request->doctor) {
            $ratingData = RatingOption::where('rating_target', 'doctor')->get()->toArray();
        } elseif ($request->product) {
            $ratingData = RatingOption::where('rating_target', 'product')->get()->toArray();
        }

        $ratings = array_map(function ($var) {
            $var['value'] = explode(',', $var['star']);
            $var['options'] = explode(',', $var['options']);
            return $var;
        }, $ratingData);
        return MyHelper::checkGet($ratings);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();
        //validation
        $all = [];
        foreach (array_column($post['rule'], 'value') as $val) {
            $all = array_merge($all, $val);
        }
        $all_r = array_flip($all);
        if (count($all) !== count($all_r)) {
            return back()->withInput()->withErrors('Rating option contain duplicate data. Please try again');
        }
        \DB::beginTransaction();
        RatingOption::where('rating_target', $post['rating_target'])->delete();
        foreach ($post['rule'] as $rule) {
            $insert['star'] = implode(',', $rule['value']);
            $insert['question'] = substr($rule['question'], 0, 40);
            $insert['options'] = implode(',', array_map(function ($var) {
                return substr($var, 0, 20);
            }, $rule['options']));
            $insert['rating_target'] = $post['rating_target'];
            $create = RatingOption::create($insert);
            if (!$create) {
                \DB::rollBack();
                return MyHelper::checkCreate($create);
            }
        }
        \DB::commit();
        return MyHelper::checkCreate($create ?? []);
    }
}
