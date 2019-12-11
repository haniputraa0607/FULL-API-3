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
}