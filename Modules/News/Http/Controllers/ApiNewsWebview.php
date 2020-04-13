<?php

namespace Modules\News\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\News;
use App\Http\Models\Outlet;
use App\Http\Models\Product;

use App\Lib\MyHelper;

class ApiNewsWebview extends Controller
{

    public function test()
    {
        return view('error', ['msg' => 'testing']);
    }
    public function detail(Request $request, $id)
    {
        $news = News::with('newsOutlet','newsOutlet.outlet','newsProduct','newsProduct.product')->find($id)->toArray();
        $totalOutlet = 0;
        $outlet = Outlet::get()->toArray();
        if ($outlet) {
            $totalOutlet = count($outlet);
        }
        
        $totalOutletNews = 0;

        $totalProduct = 0;
        $product = Product::get()->toArray();
        if ($product) {
            $totalProduct = count($product);
        }
        
        $totalProductNews = 0;
        
        if ($news) {
            // return $news['result'];
            $totalOutletNews = count($news['news_outlet']);
            $totalProductNews = count($news['news_product']);
            return view('news::webview.news', ['news' => [$news], 'total_outlet' => $totalOutlet, 'total_outlet_news' => $totalOutletNews, 'total_product' => $totalProduct, 'total_product_news' => $totalProductNews]);
        }else {
            return view('error', ['msg' => 'Something went wrong, try again']);
        }
    }
    public function detailNews(Request $request)
    {
        $news = News::with('newsOutlet','newsOutlet.outlet','newsProduct','newsProduct.product')->find($request->id_news)->toArray();
        $totalOutlet = 0;
        $outlet = Outlet::get()->toArray();
        if ($outlet) {
            $totalOutlet = count($outlet);
        }
        
        $totalOutletNews = 0;

        $totalProduct = 0;
        $product = Product::get()->toArray();
        if ($product) {
            $totalProduct = count($product);
        }
        
        $totalProductNews = 0;
        
        if ($news) {
            $news['news_image_dalam'] = env('S3_URL_API').$news['news_image_dalam'];
            $news['news_video'] = (is_null($news['news_video'])) ? [] : explode(';', $news['news_video']);
            $totalOutletNews = count($news['news_outlet']);

            if (!empty($news['news_outlet'])) {
                $newsOutlet = $news['news_outlet'];
                unset($news['news_outlet']);
                foreach ($newsOutlet as $keyOutlet => $valOutlet) {
                    $news['news_outlet'][$keyOutlet]['outlet_name']     = $valOutlet['outlet']['outlet_name'];
                    $news['news_outlet'][$keyOutlet]['outlet_image']    = null;
                }
            }

            if (!empty($news['news_product'])) {
                $newsProduct = $news['news_product'];
                unset($news['news_product']);
                foreach ($newsProduct as $keyProduct => $valProduct) {
                    $news['news_product'][$keyProduct]['product_name']  = $valProduct['product']['product_name'];
                    $news['news_product'][$keyProduct]['product_image'] = null;
                }
            }

            $news['news_post_date'] = date('l, d F Y  H:i', strtotime($news['news_post_date']));
            $news['news_event_date'] = date('d', strtotime($news['news_event_date_start'])) . ' - ' . date('d F Y', strtotime($news['news_event_date_end']));
            $news['news_event_hours'] = date('H:i', strtotime($news['news_event_time_start'])) . ' - ' . date('H:i', strtotime($news['news_event_time_end']));
            unset($news['news_publish_date']);
            unset($news['news_expired_date']);
            unset($news['news_event_date_start']);
            unset($news['news_event_date_end']);
            unset($news['news_event_time_start']);
            unset($news['news_event_time_end']);
            unset($news['detail']);
            unset($news['url_webview']);
            unset($news['url_form']);
            unset($news['updated_at']);
            unset($news['created_at']);
            
            return response()->json(MyHelper::checkGet($news));
        }else {
            return response()->json(['status' => 'fail','message' => 'Something went wrong, try again']);
        }
    }
}