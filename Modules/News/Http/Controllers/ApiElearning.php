<?php

namespace Modules\News\Http\Controllers;

use App\Http\Models\News;
use App\Http\Models\NewsFormStructure;
use App\Http\Models\NewsFormData;
use App\Http\Models\NewsFormDataDetail;
use App\Http\Models\NewsOutlet;
use App\Http\Models\NewsProduct;
use App\Http\Models\Configs;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use File;
use Auth;

use Modules\News\Http\Requests\Create;
use Modules\News\Http\Requests\Update;
use Modules\News\Http\Requests\CreateRelation;
use Modules\News\Http\Requests\DeleteRelation;

class ApiElearning extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public $saveImage = "img/news/";
    public $endPoint  = "http://localhost/crmsys-api/public/";

    public function queryList($type, $post){
        $now = date('Y-m-d');
        $list = News::whereDate('news_publish_date', '<=', $now)->where(function ($query) use ($now) {
            $query->whereDate('news_expired_date', '>=', $now)
                ->orWhere('news_expired_date', null);
        })->where('news_type', $type);

        if(!empty($post['search_key'])){
            $list = $list->where('news_title', 'like', '%'.$post['search_key'].'%');
        }

        $list = $list->get()->toArray();

        return $list;
    }

    public function videoList(Request $request){
        $post = $request->json()->all();

        $list = $this->queryList('video', $post);
        $res = [];
        foreach ($list as $value){
            $res[] = [
                'slug' => $value['news_slug'],
                'title' => $value['news_title'],
                'link_video' => $value['news_video']
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function videoDetail(Request $request){
        $post = $request->json()->all();

        if(!empty($post['slug'])){
            $detail = News::where('news_slug', $post['slug'])->first();

            if(!empty($detail)){
                $res = [
                    'slug' => $detail['news_slug'],
                    'title' => $detail['news_title'],
                    'link_video' => $detail['news_video']
                ];
            }
            return response()->json(MyHelper::checkGet($res??$detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Slug can not be empty']]);
        }
    }

    public function articleList(Request $request){
        $post = $request->json()->all();

        $list = $this->queryList('article', $post);
        $res = [];
        foreach ($list as $value){
            $res[] = [
                'slug' => $value['news_slug'],
                'title' => $value['news_title'],
                'image' => $value['url_news_image_dalam']
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function articleDetail(Request $request){
        $post = $request->json()->all();

        if(!empty($post['slug'])){
            $news = News::where('news_slug', $post['slug'])->with('newsOutlet','newsOutlet.outlet','newsProduct.product.photos')->first();

            if(!empty($news)){
                $res = [
                    'slug' => $news['news_slug'],
                    'title' => $news['news_title'],
                    'image' => $news['url_news_image_dalam'],
                    'post_date' => MyHelper::dateFormatInd($news['news_post_date'], true, false),
                    'creator_by' => $news['news_by'],
                    'description' => $news['news_content_long'],
                ];

                $res['video_text'] = $news['news_video_text'];
                $res['video_link'] = (is_null($news['news_video'])) ? [] : explode(';', $news['news_video']);

                $res['location'] = NULL;
                if(!empty($news['news_event_location_name'])){
                    $res['location'] = [
                        "name" => $news['news_event_location_name'],
                        "phone" => $news['news_event_location_phone'],
                        "address" => $news['news_event_location_address'],
                        "latitude" => $news['news_event_latitude'],
                        "longitude" => $news['news_event_longitude']
                    ];
                }

                $res['outlets_text'] = $news['news_outlet_text'];
                $res['outlets'] = [];
                if (!empty($news['news_outlet'])) {
                    $newsOutlet = $news['news_outlet'];
                    unset($news['news_outlet']);
                    foreach ($newsOutlet as $keyOutlet => $valOutlet) {
                        $res['outlets'][$keyOutlet]['outlet_name']     = $valOutlet['outlet']['outlet_name'];
                        $res['outlets'][$keyOutlet]['outlet_image']    = null;
                    }
                }

                $res['products_text'] = $news['news_product_text'];
                $res['products'] = [];
                if (!empty($news['news_product'])) {
                    $newsProduct = $news['news_product'];
                    unset($news['news_product']);
                    foreach ($newsProduct as $keyProduct => $valProduct) {
                        $res['products'][$keyProduct]['product_name']  = $valProduct['product']['product_name'];
                        $res['products'][$keyProduct]['product_image'] = config('url.storage_url_api').($valProduct['product']['photos'][0]['product_photo']??'img/product/item/default.png');
                    }
                }

                $res['event_date'] = NULL;
                if($news['news_event_date_start'] != null && $news['news_event_time_end'] != null){
                    $dateEventStart = MyHelper::dateFormatInd($news['news_event_date_start'], true, false);
                    $dateEventEnd = MyHelper::dateFormatInd($news['news_event_date_end'], true, false);

                    if($dateEventStart == $dateEventEnd){
                        $res['event_date'] = $dateEventStart;
                    }else{
                        $res['event_date'] = $dateEventStart.' - '.$dateEventEnd;
                    }
                }

                $res['event_hours'] = NULL;
                if($news['news_event_time_start'] != null && $news['news_event_time_end'] != null) {
                    $res['event_hours'] = date('H:i', strtotime($news['news_event_time_start'])) . ' - ' . date('H:i', strtotime($news['news_event_time_end']));
                }

                $res['button_text'] = $news['news_button_text'];
                $res['button_link'] = $news['news_button_link'];
            }
            return response()->json(MyHelper::checkGet($res??$news));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Slug can not be empty']]);
        }
    }

    public function onlineClassBanner(){
        $now = date('Y-m-d');
        $banner = News::whereRaw('"'.$now.'" BETWEEN DATE(news_event_date_start) AND  DATE(news_event_date_end)')
                    ->where('news_type', 'online_class')->first();

        $res = null;
        if(!empty($banner)){
            if(!empty($banner['news_event_date_start'])){
                $dateEventStart = MyHelper::dateFormatInd($banner['news_event_date_start'], true, false);
                $dateEventEnd = MyHelper::dateFormatInd($banner['news_event_date_end'], true, false);

                if($dateEventStart == $dateEventEnd){
                    $date = $dateEventStart;
                }else{
                    $date = $dateEventStart.' - '.$dateEventEnd;
                }
            }

            $res = [
                'slug' => $banner['news_slug'],
                'title' => $banner['news_title'],
                'short_description' => $banner['news_content_short'],
                'image' => $banner['url_news_image_dalam'],
                'class_date' => $date,
                'class_by' => $banner['news_by']
            ];
        }
        return response()->json(MyHelper::checkGet($res));
    }

    public function onlineClassList(Request $request){
        $post = $request->json()->all();

        $list = $this->queryList('online_class', $post);
        $res = [];
        foreach ($list as $value){
            $date = '';
            if(!empty($value['news_event_date_start'])){
                $dateEventStart = MyHelper::dateFormatInd($value['news_event_date_start'], true, false);
                $dateEventEnd = MyHelper::dateFormatInd($value['news_event_date_end'], true, false);

                if($dateEventStart == $dateEventEnd){
                    $date = $dateEventStart;
                }else{
                    $date = $dateEventStart.' - '.$dateEventEnd;
                }
            }

            $res[] = [
                'slug' => $value['news_slug'],
                'title' => $value['news_title'],
                'image' => $value['url_news_image_dalam'],
                'class_date' => $date,
                'class_by' => $value['news_by']
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function onlineClassDetail(Request $request){
        $post = $request->json()->all();

        if(!empty($post['slug'])){
            $news = News::where('news_slug', $post['slug'])->with('newsOutlet','newsOutlet.outlet','newsProduct.product.photos')->first();

            if(!empty($news)){
                $res = [
                    'slug' => $news['news_slug'],
                    'title' => $news['news_title'],
                    'image' => $news['url_news_image_dalam'],
                    'post_date' => MyHelper::dateFormatInd($news['news_post_date'], true, false),
                    'class_by' => $news['news_by'],
                    'description' => $news['news_content_long'],
                ];

                $res['class_date'] = NULL;
                if($news['news_event_date_start'] != null && $news['news_event_time_end'] != null){
                    $dateEventStart = MyHelper::dateFormatInd($news['news_event_date_start'], true, false);
                    $dateEventEnd = MyHelper::dateFormatInd($news['news_event_date_end'], true, false);

                    if($dateEventStart == $dateEventEnd){
                        $res['class_date'] = $dateEventStart;
                    }else{
                        $res['class_date'] = $dateEventStart.' - '.$dateEventEnd;
                    }
                }

                $res['class_hours'] = NULL;
                if($news['news_event_time_start'] != null && $news['news_event_time_end'] != null) {
                    $res['class_hours'] = date('H:i', strtotime($news['news_event_time_start'])) . ' - ' . date('H:i', strtotime($news['news_event_time_end']));
                }

                $res['button_text'] = $news['news_button_text'];
                $res['button_link'] = $news['news_button_link'];
            }
            return response()->json(MyHelper::checkGet($res??$news));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Slug can not be empty']]);
        }
    }
}
