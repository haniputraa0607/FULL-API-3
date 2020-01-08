<?php

namespace Modules\UserFeedback\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UserFeedback\Entities\RatingItem;

use App\Lib\MyHelper;

class ApiRatingItemController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return MyHelper::checkGet(RatingItem::get());
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        $upload = MyHelper::uploadPhoto($post['image'],'img/rating_item/');
        if($upload['status']!='success'){
            return [
                'status' => 'fail',
                'messages' => ['Fail upload file']
            ];
        }
        $post['image'] = $upload['path'];
        $upload2 = MyHelper::uploadPhoto($post['image_selected'],'img/rating_item/');
        if($upload['status']!='success'){
            return [
                'status' => 'fail',
                'messages' => ['Fail upload file']
            ];
        }
        $post['image_selected'] = $upload2['path'];
        $create = RatingItem::create($post);
        return MyHelper::checkCreate($create);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $id_rating_item = $request->json('id_rating_item');
        $post = $request->json()->all();
        if($post['image']??false){
            $upload = MyHelper::uploadFile($post['image'],'img/rating_item/');
            if($upload['status']!='success'){
                return [
                    'status' => 'fail',
                    'messages' => ['Fail upload file']
                ];
            }
            $post['image'] = $upload['path'];
        }else{
            unset($post['image']);
        }
        if($post['image_selected']){
            $upload2 = MyHelper::uploadFile($post['image_selected'],'img/rating_item/');
            if($upload['status']!='success'){
                return [
                    'status' => 'fail',
                    'messages' => ['Fail upload file']
                ];
            }
            $post['image_selected'] = $upload2['path'];
        }else{
            unset($post['image_selected']);
        }
        $update = RatingItem::where('id_rating_item',$id_rating_item)->update($post);
        return MyHelper::checkUpdate($update);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $id_rating_item = $request->json('id_rating_item');
        $delete = RatingItem::where('id_rating_item',$id_rating_item)->delete();
        return MyHelper::checkDelete($delete);
    }
}
