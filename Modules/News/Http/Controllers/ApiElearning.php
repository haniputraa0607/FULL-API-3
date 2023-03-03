<?php

namespace Modules\News\Http\Controllers;

use App\Http\Models\News;
use App\Http\Models\NewsFavorite;
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
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public $saveImage = "img/news/";
    public $endPoint  = "http://localhost/crmsys-api/public/";

    public function home(Request $request)
    {
        $post = $request->json()->all();
        $now = date('Y-m-d');
        $list = News::whereDate('news_publish_date', '<=', $now)->where(function ($query) use ($now) {
            $query->whereDate('news_expired_date', '>=', $now)
                ->orWhere('news_expired_date', null);
        })->where('news_featured_status', 1)->orderBy('news_publish_date', 'desc');

        if (!empty($post['search_key'])) {
            $list = $list->where(function ($query) use ($post) {
                $query->where('news_title', 'like', '%' . $post['search_key'] . '%')
                    ->orWhere('news_content_short', 'like', '%' . $post['search_key'] . '%');
            });
        }

        $list = $list->get()->toArray();
        $video = [];
        $article = [];
        $onlineClass = [];

        foreach ($list as $value) {
            if ($value['news_type'] == 'video') {
                $video[] = [
                    'slug' => $value['news_slug'],
                    'title' => $value['news_title'],
                    'image' => $value['url_news_image_dalam'],
                    'link_video' => $value['news_video'],
                    'short_description' => $value['news_content_short'],
                    'post_date' => MyHelper::dateFormatInd($value['news_publish_date'], true, false, false)
                ];
            } elseif ($value['news_type'] == 'article') {
                $article[] = [
                    'slug' => $value['news_slug'],
                    'title' => $value['news_title'],
                    'image' => $value['url_news_image_dalam'],
                    'short_description' => $value['news_content_short'],
                    'post_date' => MyHelper::dateFormatInd($value['news_publish_date'], true, false, false)
                ];
            } elseif ($value['news_type'] == 'online_class') {
                $date = '';
                if (!empty($value['news_event_date_start'])) {
                    $dateEventStart = MyHelper::dateFormatInd($value['news_event_date_start'], true, false);
                    $dateEventEnd = MyHelper::dateFormatInd($value['news_event_date_end'], true, false);

                    if ($dateEventStart == $dateEventEnd) {
                        $date = $dateEventStart;
                    } else {
                        $date = $dateEventStart . ' - ' . $dateEventEnd;
                    }
                }

                $onlineClass[] = [
                    'slug' => $value['news_slug'],
                    'title' => $value['news_title'],
                    'image' => $value['url_news_image_dalam'],
                    'class_date' => $date,
                    'class_by' => $value['news_by'],
                    'post_date' => MyHelper::dateFormatInd($value['news_publish_date'], true, false, false)
                ];
            }
        }

        $bannerTmp = [];
        $bannerVideo = $this->queryList('video', ['news_featured_status' => 1]);
        $bannerArticle = $this->queryList('article', ['news_featured_status' => 1]);
        $bannerOnlineClass = $this->queryList('online_class', ['news_featured_status' => 1]);

        if (count($bannerArticle) >= 2) {
            $bannerTmp[] = $bannerArticle[0];
            $bannerTmp[] = $bannerArticle[1];
        }

        if (count($bannerVideo) >= 1) {
            $bannerTmp[] = $bannerVideo[0];
        }

        if (count($bannerOnlineClass) >= 1) {
            $bannerTmp[] = $bannerOnlineClass[0];
        }

        if (count($bannerTmp) != 4) {
            $listBanner = News::whereDate('news_publish_date', '<=', $now)->where(function ($query) use ($now) {
                $query->whereDate('news_expired_date', '>=', $now)
                    ->orWhere('news_expired_date', null);
            })->where('news_featured_status', 1)->orderBy('news_publish_date', 'desc')->get()->toArray();
            shuffle($listBanner);
            $bannerTmp = array_slice($listBanner, 0, 5);
        } else {
            shuffle($bannerTmp);
        }

        $banners = [];
        foreach ($bannerTmp as $tmp) {
            $banners[] = [
                'type' => $tmp['news_type'],
                'slug' => $tmp['news_slug'],
                'title' => $tmp['news_title'],
                'link_video' => $tmp['news_video'],
                'short_description' => $tmp['news_content_short'],
                'image' => $tmp['url_news_image_dalam'],
                'post_date' => MyHelper::dateFormatInd($tmp['news_publish_date'], true, false, false)
            ];
        }

        $res = [
            'banner' => $banners,
            'top_video' => $video,
            'top_article' => $article,
            'top_online_class' => $onlineClass
        ];
        return response()->json(MyHelper::checkGet($res));
    }

    public function queryList($type, $post)
    {
        $now = date('Y-m-d');
        $list = News::whereDate('news_publish_date', '<=', $now)->where(function ($query) use ($now) {
                    $query->whereDate('news_expired_date', '>=', $now)
                        ->orWhere('news_expired_date', null);
        })->where('news_type', $type);

        if (!empty($post['search_key'])) {
            $list = $list->where(function ($query) use ($post) {
                        $query->where('news_title', 'like', '%' . $post['search_key'] . '%')
                            ->orWhere('news_content_short', 'like', '%' . $post['search_key'] . '%');
            });
        }

        if (isset($post['news_featured_status'])) {
            $list = $list->where('news_featured_status', $post['news_featured_status']);
        }

        $list = $list->orderBy('news_publish_date', 'desc')->get()->toArray();

        return $list;
    }

    public function videoList(Request $request)
    {
        $post = $request->json()->all();

        $list = $this->queryList('video', $post);
        $res = [];
        foreach ($list as $value) {
            $res[] = [
                'slug' => $value['news_slug'],
                'title' => $value['news_title'],
                'image' => $value['url_news_image_dalam'],
                'link_video' => $value['news_video'],
                'short_description' => $value['news_content_short']
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function videoDetail(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['slug'])) {
            $detail = News::where('news_slug', $post['slug'])->first();

            if (!empty($detail)) {
                $favorite = NewsFavorite::where('id_user', $request->user()->id)->where('id_news', $detail['id_news'])->first();
                $res = [
                    'slug' => $detail['news_slug'],
                    'title' => $detail['news_title'],
                    'image' => $detail['url_news_image_dalam'],
                    'link_video' => $detail['news_video'],
                    'short_description' => $detail['news_content_short'],
                    'favorite' => (!empty($favorite) ? 1 : 0)
                ];
            }
            return response()->json(MyHelper::checkGet($res ?? $detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Slug can not be empty']]);
        }
    }

    public function articleList(Request $request)
    {
        $post = $request->json()->all();

        $list = $this->queryList('article', $post);
        $res = [];
        foreach ($list as $value) {
            $res[] = [
                'slug' => $value['news_slug'],
                'title' => $value['news_title'],
                'image' => $value['url_news_image_dalam'],
                'short_description' => $value['news_content_short']
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function articleDetail(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['slug'])) {
            $news = News::where('news_slug', $post['slug'])->with('newsOutlet', 'newsOutlet.outlet', 'newsProduct.product.photos')->first();

            if (!empty($news)) {
                $favorite = NewsFavorite::where('id_user', $request->user()->id)->where('id_news', $news['id_news'])->first();
                $res = [
                    'slug' => $news['news_slug'],
                    'title' => $news['news_title'],
                    'image' => $news['url_news_image_dalam'],
                    'post_date' => MyHelper::dateFormatInd($news['news_post_date'], true, false),
                    'creator_by' => $news['news_by'],
                    'short_description' => $news['news_content_short'],
                    'description' => str_replace('font-family: Arial', 'font-family: Montserrat Regular', $news['news_content_long']),
                    'favorite' => (!empty($favorite) ? 1 : 0)
                ];

                $res['video_text'] = $news['news_video_text'];
                $res['video_link'] = (is_null($news['news_video'])) ? [] : explode(';', $news['news_video']);

                $res['location'] = null;
                if (!empty($news['news_event_location_name'])) {
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
                if (!empty($news['newsOutlet'])) {
                    $newsOutlet = $news['newsOutlet'];
                    unset($news['newsOutlet']);
                    foreach ($newsOutlet as $keyOutlet => $valOutlet) {
                        $res['outlets'][$keyOutlet]['id_outlet']     = $valOutlet['outlet']['id_outlet'];
                        $res['outlets'][$keyOutlet]['outlet_name']     = $valOutlet['outlet']['outlet_name'];
                        $res['outlets'][$keyOutlet]['outlet_image']    = $valOutlet['outlet']['url_outlet_image_cover'] ?? null;
                    }
                }

                $res['products_text'] = $news['news_product_text'];
                $res['products'] = [];
                if (!empty($news['newsProduct'])) {
                    $newsProduct = $news['newsProduct'];
                    unset($news['newsProduct']);
                    foreach ($newsProduct as $keyProduct => $valProduct) {
                        $res['products'][$keyProduct]['id_product']  = $valProduct['product']['id_product'];
                        $res['products'][$keyProduct]['product_name']  = $valProduct['product']['product_name'];
                        $res['products'][$keyProduct]['product_image'] = config('url.storage_url_api') . ($valProduct['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
                    }
                }

                $res['event_date'] = null;
                if ($news['news_event_date_start'] != null && $news['news_event_time_end'] != null) {
                    $dateEventStart = MyHelper::dateFormatInd($news['news_event_date_start'], true, false);
                    $dateEventEnd = MyHelper::dateFormatInd($news['news_event_date_end'], true, false);

                    if ($dateEventStart == $dateEventEnd) {
                        $res['event_date'] = $dateEventStart;
                    } else {
                        $res['event_date'] = $dateEventStart . ' - ' . $dateEventEnd;
                    }
                }

                $res['event_hours'] = null;
                if ($news['news_event_time_start'] != null && $news['news_event_time_end'] != null) {
                    $res['event_hours'] = date('H:i', strtotime($news['news_event_time_start'])) . ' - ' . date('H:i', strtotime($news['news_event_time_end']));
                }

                $res['button_text'] = $news['news_button_text'];
                $res['button_link'] = $news['news_button_link'];
            }
            return response()->json(MyHelper::checkGet($res ?? $news));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Slug can not be empty']]);
        }
    }

    public function onlineClassBanner()
    {
        $now = date('Y-m-d');
        $banner = News::whereRaw('"' . $now . '" BETWEEN DATE(news_event_date_start) AND  DATE(news_event_date_end)')
                    ->where('news_type', 'online_class')->first();

        $res = null;
        if (!empty($banner)) {
            if (!empty($banner['news_event_date_start'])) {
                $dateEventStart = MyHelper::dateFormatInd($banner['news_event_date_start'], true, false);
                $dateEventEnd = MyHelper::dateFormatInd($banner['news_event_date_end'], true, false);

                if ($dateEventStart == $dateEventEnd) {
                    $date = $dateEventStart;
                } else {
                    $date = $dateEventStart . ' - ' . $dateEventEnd;
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

    public function onlineClassList(Request $request)
    {
        $post = $request->json()->all();

        $list = $this->queryList('online_class', $post);
        $res = [];
        foreach ($list as $value) {
            $date = '';
            if (!empty($value['news_event_date_start'])) {
                $dateEventStart = MyHelper::dateFormatInd($value['news_event_date_start'], true, false);
                $dateEventEnd = MyHelper::dateFormatInd($value['news_event_date_end'], true, false);

                if ($dateEventStart == $dateEventEnd) {
                    $date = $dateEventStart;
                } else {
                    $date = $dateEventStart . ' - ' . $dateEventEnd;
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

    public function onlineClassDetail(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['slug'])) {
            $news = News::where('news_slug', $post['slug'])->with('newsOutlet', 'newsOutlet.outlet', 'newsProduct.product.photos')->first();

            if (!empty($news)) {
                $favorite = NewsFavorite::where('id_user', $request->user()->id)->where('id_news', $news['id_news'])->first();
                $res = [
                    'slug' => $news['news_slug'],
                    'title' => $news['news_title'],
                    'image' => $news['url_news_image_dalam'],
                    'post_date' => MyHelper::dateFormatInd($news['news_post_date'], true, false),
                    'class_by' => $news['news_by'],
                    'short_description' => $news['news_content_short'],
                    'description' => str_replace('font-family: Arial', 'font-family: Montserrat Regular', $news['news_content_long']),
                    'favorite' => (!empty($favorite) ? 1 : 0)
                ];

                $res['class_date'] = null;
                if ($news['news_event_date_start'] != null && $news['news_event_time_end'] != null) {
                    $dateEventStart = MyHelper::dateFormatInd($news['news_event_date_start'], true, false);
                    $dateEventEnd = MyHelper::dateFormatInd($news['news_event_date_end'], true, false);

                    if ($dateEventStart == $dateEventEnd) {
                        $res['class_date'] = $dateEventStart;
                    } else {
                        $res['class_date'] = $dateEventStart . ' - ' . $dateEventEnd;
                    }
                }

                $res['class_hours'] = null;
                if ($news['news_event_time_start'] != null && $news['news_event_time_end'] != null) {
                    $res['class_hours'] = date('H:i', strtotime($news['news_event_time_start'])) . ' - ' . date('H:i', strtotime($news['news_event_time_end']));
                }

                $res['button_text'] = $news['news_button_text'];
                $res['button_link'] = $news['news_button_link'];
            }
            return response()->json(MyHelper::checkGet($res ?? $news));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Slug can not be empty']]);
        }
    }

    public function favoriteList(Request $request)
    {
        $idUser = $request->user()->id;
        $post = $request->json()->all();
        $now = date('Y-m-d');
        $list = News::join('news_favorites', 'news_favorites.id_news', 'news.id_news')
        ->whereDate('news_publish_date', '<=', $now)->where(function ($query) use ($now) {
            $query->whereDate('news_expired_date', '>=', $now)
                ->orWhere('news_expired_date', null);
        })->where('news_favorites.id_user', $idUser)->orderBy('news_publish_date', 'desc')->groupBy('news.id_news');

        if (!empty($post['search_key'])) {
            $list = $list->where(function ($query) use ($post) {
                $query->where('news_title', 'like', '%' . $post['search_key'] . '%')
                    ->orWhere('news_content_short', 'like', '%' . $post['search_key'] . '%');
            });
        }

        $list = $list->get()->toArray();
        $video = [];
        $article = [];
        $onlineClass = [];

        foreach ($list as $value) {
            if ($value['news_type'] == 'video') {
                $video[] = [
                    'slug' => $value['news_slug'],
                    'title' => $value['news_title'],
                    'image' => $value['url_news_image_dalam'],
                    'link_video' => $value['news_video'],
                    'short_description' => $value['news_content_short'],
                    'post_date' => MyHelper::dateFormatInd($value['news_publish_date'], true, false, false)
                ];
            } elseif ($value['news_type'] == 'article') {
                $article[] = [
                    'slug' => $value['news_slug'],
                    'title' => $value['news_title'],
                    'image' => $value['url_news_image_dalam'],
                    'short_description' => $value['news_content_short'],
                    'post_date' => MyHelper::dateFormatInd($value['news_publish_date'], true, false, false)
                ];
            } elseif ($value['news_type'] == 'online_class') {
                $date = '';
                if (!empty($value['news_event_date_start'])) {
                    $dateEventStart = MyHelper::dateFormatInd($value['news_event_date_start'], true, false);
                    $dateEventEnd = MyHelper::dateFormatInd($value['news_event_date_end'], true, false);

                    if ($dateEventStart == $dateEventEnd) {
                        $date = $dateEventStart;
                    } else {
                        $date = $dateEventStart . ' - ' . $dateEventEnd;
                    }
                }

                $onlineClass[] = [
                    'slug' => $value['news_slug'],
                    'title' => $value['news_title'],
                    'image' => $value['url_news_image_dalam'],
                    'class_date' => $date,
                    'class_by' => $value['news_by'],
                    'post_date' => MyHelper::dateFormatInd($value['news_publish_date'], true, false, false)
                ];
            }
        }

        $res = [
            'video' => $video,
            'article' => $article,
            'online_class' => $onlineClass
        ];
        return response()->json(MyHelper::checkGet($res));
    }

    public function favoriteAdd(Request $request)
    {
        $post = $request->json()->all();
        $idUser = $request->user()->id;

        if (empty($post['slug'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Slug news can not be empty']]);
        }
        $idNews = News::where('news_slug', $post['slug'])->first()['id_news'] ?? null;
        if (empty($idNews)) {
            return response()->json(['status' => 'fail', 'messages' => ['News not found']]);
        }

        $save = NewsFavorite::updateOrCreate([
            'id_user' => $idUser,
            'id_news' => $idNews
        ], [
            'id_user' => $idUser,
            'id_news' => $idNews,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function favoriteDelete(Request $request)
    {
        $post = $request->json()->all();
        $idUser = $request->user()->id;

        if (empty($post['slug'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Slug news can not be empty']]);
        }
        $idNews = News::where('news_slug', $post['slug'])->first()['id_news'] ?? null;
        if (empty($idNews)) {
            return response()->json(['status' => 'fail', 'messages' => ['News not found']]);
        }
        NewsFavorite::where('id_user', $idUser)->where('id_news', $idNews)->delete();
        return response()->json(['status' => 'success']);
    }
}
