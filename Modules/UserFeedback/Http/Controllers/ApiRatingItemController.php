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
        $rating = RatingItem::all()->toArray();
        return MyHelper::checkGet($rating);
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
        $rating_item = $request->json('rating_item')?:[];
        \DB::beginTransaction();
        if(count($rating_item)==2){
            RatingItem::where('rating_value',0)->delete();
        }
        foreach ($rating_item as $item) {
            $data_update = [
                'text' => $item['text'],
                'order' => $item['order']
            ];
            if($item['image']??false){
                $upload = MyHelper::uploadPhotoStrict($item['image'],'img/rating_item/',100,100);
                if($upload['status']!='success'){
                    \DB::rollback();
                    return [
                        'status' => 'fail',
                        'messages' => ['Fail upload file']
                    ];
                }
                $data_update['image'] = $upload['path'];
            }
            if($item['image_selected']??false){
                $upload = MyHelper::uploadPhotoStrict($item['image_selected'],'img/rating_item/',100,100);
                if($upload['status']!='success'){
                    \DB::rollback();
                    return [
                        'status' => 'fail',
                        'messages' => ['Fail upload file']
                    ];
                }
                $data_update['image_selected'] = $upload['path'];
            }
            $update = RatingItem::updateOrCreate([
                'rating_value'=>$item['rating_value']
            ],$data_update);
            if(!$update){
                \DB::rollback();
                return MyHelper::checkUpdate($update);
            }
        }
        \DB::commit();
        return ['status'=>'success'];
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
