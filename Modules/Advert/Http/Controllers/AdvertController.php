<?php

namespace Modules\Advert\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\Advert;
use Modules\Advert\Http\Requests\Create;
use Modules\Advert\Http\Requests\Iklan;

use DB;

class AdvertController extends Controller
{
    public $saveImage = "img/advert/";

    /* INDEX */
    public function index(Iklan $request) {
        
        $advert = Advert::with('news')->select('*');

        if ($request->json('id_advert')) {
            $advert->where('id_advert', $request->json('id_advert'));
        }

        if ($request->json('page')) {
            $advert->where('page', $request->json('page'));
        }

        $advert = $advert->orderBy('order', 'ASC')->get()->toArray();

        /* INISIALISASI */
        $newAdv['img_top']     = [];
        $newAdv['img_bottom']  = [];
        $newAdv['text_top']    = [];
        $newAdv['text_bottom'] = [];

        if (!empty($advert)) {
            

            foreach ($advert as $key => $value) {
                if ($value['type'] == "img_top") {
                    array_push($newAdv['img_top'], ['id_advert' => $value['id_advert'], 'value' => $value['value'], 'news' => $value['news']]);
                }

                if ($value['type'] == "img_bottom") {
                    array_push($newAdv['img_bottom'], ['id_advert' => $value['id_advert'], 'value' => $value['value'], 'news' => $value['news']]);
                }

                if ($value['type'] == "text_top") {
                    array_push($newAdv['text_top'], ['id_advert' => $value['id_advert'], 'value' => $value['value'], 'news' => $value['news']]);
                }

                if ($value['type'] == "text_bottom") {
                    array_push($newAdv['text_bottom'], ['id_advert' => $value['id_advert'], 'value' => $value['value'], 'news' => $value['news']]);
                }
            }
        }
        
        $advert = $newAdv;

        return response()->json(MyHelper::checkGet($advert));
    }

    /* CHECK POST */
    function checkInput($post=[]) {
        $data = [];

        if (isset($post['type'])) {
            $data['type'] = $post['type'];
        }

        if (isset($post['page'])) {
            $data['page'] = $post['page'];
        }

        if (isset($post['order'])) {
            $data['order'] = $post['order'];
        }

        if (isset($post['id_news']) && $post['id_news'] != null ){
            $data['id_news'] = $post['id_news'];
        }else{
            $data['id_news'] = null;
        }

        // IMAGE
        if (isset($post['img_top'])) {
            // $deleteImage = $this->deleteImage($post['page'], "img_top");

            $upload = MyHelper::uploadPhotoStrict($post['img_top'], $this->saveImage, 1080, 360);
            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['value'] = env('APP_API_URL').$upload['path'];
            }
            else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];
                return $result;
            }
        }

        // IMAGE
        if (isset($post['img_bottom'])) {
            // $deleteImage = $this->deleteImage($post['page'], "img_bottom");

            $upload = MyHelper::uploadPhotoStrict($post['img_bottom'], $this->saveImage, 1080, 360);
            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['value'] = env('APP_API_URL').$upload['path'];
            }
            else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return $result;
            }
        }

        if (isset($post['value'])) {
            $data['value'] = $post['value'];
        }

        return $data;
    }

    /* CREATE */
    function create(Create $request) {
        $data = $this->checkInput($request->json()->all());

        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
        }

        if ($request->json('add')) {
                        // UPDATE OR CREATE
            $save = Advert::create($data);
        }
        elseif ($request->json('id_advert')) {
            $save = Advert::where('id_advert', $request->json('id_advert'))->update($data);
        }
        else {
            // UPDATE OR CREATE
            $save = Advert::updateOrCreate(['page' => $request->json('page'), 'type' => $request->json('type')], $data);
        }


        return response()->json(MyHelper::checkUpdate($save));
    }

    /* DELETE IMAGE */
    function deleteImage($page, $type) {
        $img = Advert::where('page', $page)->first();

        if ($img) {
            if ($type == "img_top") {
                $delete = MyHelper::deletePhoto($img->img_top);

                return $delete;
            }

            if ($type == "img_bottom") {
                $delete = MyHelper::deletePhoto($img->img_bottom);

                return $delete;
            }
        }
        return true;
    }

    /* DESTROY */
    function destroy(Request $request) {

        $delete = Advert::select('*');

        if ($request->json('id_advert')) {
            $delete->where('id_advert', $request->json('id_advert'));
        }
        if ($request->json('page')) {
            $delete->where('page', $request->json('page'));
        }
        if ($request->json('page') && $request->json('type')) {
            $delete->where('page', $request->json('page'))->where('type', $request->json('type'));
        }

        // delete image
        $get = $delete->first();

        if ($get) {
            if ($get->type == "img_top" || $get->type == "img_bottom") {
                $img         = explode(env('APP_API_URL'), $get->value);
                $deletePhoto = MyHelper::deletePhoto($img[1]);
            }
            
            $delete = $delete->delete();

        }
        else {
            $delete = 1;
        }

        return response()->json(MyHelper::checkDelete($delete));
    }
}
