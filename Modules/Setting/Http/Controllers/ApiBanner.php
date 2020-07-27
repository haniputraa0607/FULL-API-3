<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Banner;
use App\Lib\MyHelper;

use DB;

class ApiBanner extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        // get banner with news title
        $banners = DB::table('banners')
            ->leftJoin('news', 'news.id_news', '=', 'banners.id_news')
            ->select('banners.*', 'news.news_title')
            ->orderBy('banners.position')
            ->get();

        // add full url to collection
        $banners = $banners->map(function ($banner, $key) {
            $banner->image_url = config('url.storage_url_api').$banner->image;
            return $banner;
        });
        $banners->all();

        return response()->json(MyHelper::checkGet($banners));
    }


    public function create(Request $request)
    {
        $request->validate([
            'image' => 'required'
        ]);

        try {
            $banner = DB::transaction(function () use ($request) {
                $banner = new Banner($request->except('image'));
                $banner->storeImage($request->image);
                $banner->setPosition();
                $banner->save();
                return $banner;
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => [ $e->getMessage() ]
            ]);
        }

        return response()->json(MyHelper::checkCreate($banner));
    }

    // reorder position
    public function reorder(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        foreach ($post['id_banners'] as $key => $id_banner) {
            // reorder
            $update = Banner::find($id_banner)->update(['position' => $key+1]);

            if (!$update) {
                DB:: rollback();
                return [
                    'status' => 'fail',
                    'messages' => ['Sort banner failed']
                ];
            }
        }
        DB::commit();

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();
        $banner = Banner::find($post['id_banner']);

        if (isset($post['image'])) {
            // check folder
            $path = "img/banner/";
            if(!file_exists($path)){
                mkdir($path, 0777, true);
            }

            // upload image
            $upload = MyHelper::uploadPhotoStrict($post['image'], $path, 750, 375);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['image'] = $upload['path'];
            }
            else {
                $result = [
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
                ];
                return $result;
            }

            // delete old image
            $delete = MyHelper::deletePhoto($banner->image);
        }

        $deep_url = config('url.app_url').'outlet/webview/gofood/list';

        if ($post['type'] == 'gofood') {
            $post['url'] = $deep_url;
        }

        $update = $banner->update($post);

        return response()->json(MyHelper::checkCreate($update));
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();
        $banner = Banner::find($post['id_banner']);
        if (!$banner) {
            return [
                'status' => 'fail',
                'messages' => ['Data not found']
            ];
        }

        // delete image
        $delete_image = MyHelper::deletePhoto($banner->image);

        $delete = $banner->delete();

        // re-order the image
        $banners = Banner::orderBy('position')->get();
        foreach ($banners as $key => $banner) {
            $banner->position = $key + 1;
            $banner->save();
        }

        return response()->json(MyHelper::checkDelete($delete));
    }
}
