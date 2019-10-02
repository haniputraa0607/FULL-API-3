<?php

namespace Modules\News\Http\Controllers;

use App\Http\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\News\Http\Requests\Category\CreateRequest;
use Modules\News\Http\Requests\Category\UpdateRequest;
use Modules\News\Http\Requests\Category\GetRequest;

use App\Lib\MyHelper;

class ApiNewsCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(GetRequest $request){
        $list=(new NewsCategory)->newQuery();
        if(!$request->json('all')){
            $list->select('id_news_category','category_name');
        }
        if(is_numeric($id=$request->json('id_news_category'))){
            if($id==0){
                $list=[
                    'id_news_category'=>0,
                    'category_name'=>'Uncategories'
                ]; 
            }else{
                $list=$list->where('id_news_category',$id)->first();
            }
        }elseif($id=$request->json('id_news')){
            $list=$list->whereHas('news',function($query) use ($id){
                $query->where('id_news',$id);
            })->first();
            if(!$list){
                $list=[
                    'id_news_category'=>0,
                    'category_name'=>'Uncategories'
                ];
            }
        }else{
            $list=$list->get();
        }
        return MyHelper::checkGet($list);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(CreateRequest $request){
        $post=$request->json()->all();
        $create=NewsCategory::create($post);
        return MyHelper::checkCreate($create);
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(UpdateRequest $request){
        $post=$request->json()->all();
        $update=NewsCategory::where('id_news_category',$post['id_news_category'])->update($post);
        return MyHelper::checkUpdate($update);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request){
        $post=$request->json()->all();
        $delete=NewsCategory::where('id_news_category',$post['id_news_category'])->delete();
        return MyHelper::checkDelete($delete);
    }
}
