<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductTag;
use App\Http\Models\DailyReportTrx;
use App\Http\Models\DailyReportTrxMenu;
use App\Http\Models\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Report\Http\Requests\DetailReport;
use Modules\Report\Http\Requests\Report;

use App\Lib\MyHelper;
use DB;


class ApiMagicReport extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    /* MAGIC REPORT */
    function magicReport(Report $request) 
    {
       $tagRec = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
                   ->leftJoin('product_tags', 'products.id_product','product_tags.id_product')
                   ->rightJoin('tags', 'product_tags.id_tag', 'tags.id_tag')
                   ->whereDate('daily_report_trx_menu.trx_date', '>=', date('Y-m-d', strtotime($request->json('date_start'))))
                   ->whereDate('daily_report_trx_menu.trx_date', '<=', date('Y-m-d', strtotime($request->json('date_end'))))
                   ->select(DB::raw('tags.id_tag, tags.tag_name, SUM(daily_report_trx_menu.total_rec) as total_rec, SUM(daily_report_trx_menu.total_qty) as total_qty'))
                   ->groupBy('tags.id_tag')
                   ->orderBy('total_rec', 'DESC');

       $tagQty = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
                   ->leftJoin('product_tags', 'products.id_product','product_tags.id_product')
                   ->rightJoin('tags', 'product_tags.id_tag', 'tags.id_tag')
                   ->whereDate('daily_report_trx_menu.trx_date', '>=', date('Y-m-d', strtotime($request->json('date_start'))))
                   ->whereDate('daily_report_trx_menu.trx_date', '<=', date('Y-m-d', strtotime($request->json('date_end'))))
                   ->select(DB::raw('tags.id_tag, tags.tag_name, SUM(daily_report_trx_menu.total_qty) as total_qty'))
                   ->groupBy('tags.id_tag')
                   ->orderBy('total_qty', 'DESC')
                   ->limit(10);

        $productRec = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
                   ->whereDate('daily_report_trx_menu.trx_date', '>=', date('Y-m-d', strtotime($request->json('date_start'))))
                   ->whereDate('daily_report_trx_menu.trx_date', '<=', date('Y-m-d', strtotime($request->json('date_end'))))
                   ->select(DB::raw('products.id_product, products.product_code, products.product_name, SUM(daily_report_trx_menu.total_rec) as total_rec'))
                   ->groupBy('products.id_product')
                   ->orderBy('total_rec', 'DESC')
                   ->orderBy('products.id_product', 'ASC')
                   ->limit(10);

        $productQty = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
                   ->whereDate('daily_report_trx_menu.trx_date', '>=', date('Y-m-d', strtotime($request->json('date_start'))))
                   ->whereDate('daily_report_trx_menu.trx_date', '<=', date('Y-m-d', strtotime($request->json('date_end'))))
                   ->select(DB::raw('products.id_product, products.product_code, products.product_name, SUM(daily_report_trx_menu.total_qty) as total_qty'))
                   ->groupBy('products.id_product')
                   ->orderBy('total_qty', 'DESC')
                   ->orderBy('products.id_product', 'ASC')
                   ->limit(10);

       if ($request->json('id_outlet')) {
           $tagRec = $tagRec->where('daily_report_trx_menu.id_outlet', $request->json('id_outlet'));
           $tagQty = $tagQty->where('daily_report_trx_menu.id_outlet', $request->json('id_outlet'));
           $productRec = $productRec->where('daily_report_trx_menu.id_outlet', $request->json('id_outlet'));
           $productQty = $productQty->where('daily_report_trx_menu.id_outlet', $request->json('id_outlet'));
       }

        $getExclude = null;
        // EXCLUDE PRODUCT
        if(is_array($request->json('exclude_product'))){
            $exclude = implode(',',$request->json('exclude_product'));
        }else{
            $exclude = $request->json('exclude_product');
        }
        $insert = Setting::updateOrCreate(['key' => 'report_exclude_product'],['value' => $exclude]);
        
        $getExclude = $insert;
        if($getExclude){
            if($getExclude->value){
                $getExclude =  explode(',', $getExclude->value);
            }else{
                $getExclude = [];
            }
        }
   
        // EXCLUDE TAG
        if(is_array($request->json('exclude_tag'))){
            $excludeTag = implode(',',$request->json('exclude_tag'));
        }else{
            $excludeTag = $request->json('exclude_tag');
        }
        $insert = Setting::updateOrCreate(['key' => 'report_exclude_tag'],['value' => $excludeTag]);
    
        $getExcludeTag = $insert;
        if($getExcludeTag){
            $getExcludeTag = $getExcludeTag->value;
            if(!empty($getExcludeTag)){
                $getExcludeTag = explode(',', $getExcludeTag);
                $idProducts = array_pluck(ProductTag::whereIn('id_tag', $getExcludeTag)->get(),'id_product');
                if(count($idProducts) > 0){
                    if(!is_array($getExclude)){
                        $getExclude = [];
                    }
                    $getExclude = array_merge($getExclude, $idProducts);
                }
            }
        }

        if(!empty($getExclude)){
           $tagRec = $tagRec->whereNotIn('daily_report_trx_menu.id_product', $getExclude);
           $tagQty = $tagQty->whereNotIn('daily_report_trx_menu.id_product', $getExclude);
           $productRec = $productRec->whereNotIn('daily_report_trx_menu.id_product', $getExclude);
           $productQty = $productQty->whereNotIn('daily_report_trx_menu.id_product', $getExclude);
        }

        $data = ['tagRec'=>$tagRec->get()->toArray(),'tagQty'=>$tagQty->get()->toArray(), 'productRec'=>$productRec->get()->toArray(), 'productQty'=>$productQty->get()->toArray()];
        $data = json_decode(json_encode($data), true);

        return response()->json(MyHelper::checkGet($data));
    }

    /* GET EXCLUDE */
    function getExclude(){
        /* EXCLUDE PRODUCT */
        $getExcludeProduct = Setting::where('key', 'report_exclude_product')->first();
        if($getExcludeProduct){
            if($getExcludeProduct->value != null){
                $getExcludeProduct = explode(',', $getExcludeProduct->value);
            }else{
                $getExcludeProduct = null;
            }
        }else{
            $getExcludeProduct = null;
        }

        /* EXCLUDE TAG */
        $getExcludeTag = Setting::where('key', 'report_exclude_tag')->first();
        if($getExcludeTag){
            if($getExcludeTag->value != null){
                $getExcludeTag = explode(',', $getExcludeTag->value);
            }else{
                $getExcludeTag = null;
            }
        }else{
            $getExcludeTag = null;
        }

        $getExclude['product'] = $getExcludeProduct; 
        $getExclude['tag'] = $getExcludeTag;

        return response()->json(MyHelper::checkGet($getExclude));
    }

    /* GET PRODUCT RECOMMENDATION*/
    function getProductRecommendation(Request $request){
        $post = $request->json()->all();

        $tag = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
                                ->leftJoin('product_tags', 'products.id_product','product_tags.id_product')
                                ->rightJoin('tags', 'product_tags.id_tag', 'tags.id_tag')
                                ->select(DB::raw('tags.id_tag, tags.tag_name, SUM(daily_report_trx_menu.total_rec) as total_rec,SUM(daily_report_trx_menu.total_qty) as total_qty'))
                                ->groupBy('tags.id_tag')
                                ->orderBy('total_rec', 'DESC')
                                ->limit(10);

        $getExclude = Setting::where('key', 'report_exclude_product')->first();
        if($getExclude){
            $getExclude = $getExclude->value;
        }

        if(!empty($getExclude)){
            $excludeProduct = explode(',', $getExclude);
            $tag = $tag->whereNotIn('daily_report_trx_menu.id_product', $excludeProduct);
        }

        $getExcludeTag = Setting::where('key', 'report_exclude_tag')->first();
        if($getExcludeTag){
            $getExcludeTag = $getExcludeTag->value;
        }

        if(!empty($getExcludeTag)){
            $excludeTag = explode(',', $getExcludeTag);
            $tag = $tag->whereNotIn('tags.id_tag', $excludeTag);
        }
        
        $tag = $tag->get()->toArray();
        
        $cekProduct = ['0'];

        $x = -1;
        $y = 0;
        $z = 2;
        $i = 0;
        $loop = 0;
        $plus = 1;
        $startY = 0;
        $loopY = $loop;

       while(count($cekProduct) > 0 && $z < count($tag)) {
            if($i == $loop + $plus){ 
                $plus = $loop + $plus + 1;
                $x = $x - $loop;
                $y = $y - $loop;
                $loop++;
                $loopY = $loop;
                $i = 0;
                $z++;
                $startY = 0;
            }else{
                if($i <= ($loopY + $startY) && $i > $startY){
                    $y++;
                }else{
                    $x++;
                    $y = $x+1;
                    $startY = $i;
                    $loopY--;
                }
            }

            if($z < count($tag) - 1){
                $rec = $tag[$x]['tag_name'].' + '.$tag[$y]['tag_name'].' + '.$tag[$z]['tag_name'];
    
                if(!isset($post['exclude_rec']) || !in_array($rec, $post['exclude_rec'])){
                    
                    $cekProduct1 = Product::leftJoin('product_tags', 'products.id_product', 'product_tags.id_product')
                                        ->where('product_tags.id_tag', $tag[$x]['id_tag']);
                    $cekProduct1 = array_pluck($cekProduct1->get()->toArray(),'id_product');
                    $cekProduct2 = Product::leftJoin('product_tags', 'products.id_product', 'product_tags.id_product')
                                        ->where('product_tags.id_tag', $tag[$y]['id_tag']);
                    $cekProduct2 = array_pluck($cekProduct2->get()->toArray(),'id_product');
                    $cekProduct3 = Product::leftJoin('product_tags', 'products.id_product', 'product_tags.id_product')
                                        ->where('product_tags.id_tag', $tag[$z]['id_tag']);
                    $cekProduct3 = array_pluck($cekProduct3->get()->toArray(),'id_product');
                                        // return $cekProduct3;
                    $cekProduct4 = array_intersect($cekProduct1, $cekProduct2);
                    $cekProduct = array_intersect($cekProduct3, $cekProduct4);
                }
            }
            $i++;
        }

        if($z < count($tag) - 1){
            $recommendationTag = $tag[$x]['tag_name'].' + '.$tag[$y]['tag_name'].' + '.$tag[$z]['tag_name'];
            
            return response()->json([
                'status' => 'success',
                'result' => $recommendationTag
            ]);
        }else{
            return response()->json([
                'status' => 'fail',
                'messages' => ['New product recommendation is empty!']
            ]);
        }

    }

     /* REPORT DETAIL TAG */
     function transactionTagDetail(Request $request) 
     {
         $idProduct = array_pluck(ProductTag::where('id_tag', $request->json('id_tag'))->get(), 'id_product');
         
         $tag = DailyReportTrxMenu::select(DB::raw('trx_date, SUM(daily_report_trx_menu.total_rec) as total_rec,SUM(daily_report_trx_menu.total_qty) as total_qty'))
         ->whereDate('daily_report_trx_menu.trx_date', '>=', date('Y-m-d', strtotime($request->json('date_start'))))
         ->whereDate('daily_report_trx_menu.trx_date', '<=', date('Y-m-d', strtotime($request->json('date_end'))))
         ->whereIn('id_product', $idProduct)
         ->groupBy('trx_date');

         $tagProduct = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
         ->select(DB::raw('product_code, product_name, SUM(daily_report_trx_menu.total_rec) as total_rec,SUM(daily_report_trx_menu.total_qty) as total_qty'))
         ->whereDate('daily_report_trx_menu.trx_date', '>=', date('Y-m-d', strtotime($request->json('date_start'))))
         ->whereDate('daily_report_trx_menu.trx_date', '<=', date('Y-m-d', strtotime($request->json('date_end'))))
         ->whereIn('daily_report_trx_menu.id_product', $idProduct)
         ->groupBy('daily_report_trx_menu.id_product');
 
        if ($request->json('id_outlet')) {
            $tag = $tag->where('daily_report_trx_menu.id_outlet', $request->json('id_outlet'));
            $tagProduct = $tagProduct->where('daily_report_trx_menu.id_outlet', $request->json('id_outlet'));
            $outlet = Outlet::find($request->json('id_outlet'));
            if($outlet){
                $outlet = $outlet->outlet_name;
            }
        }

        $getExclude = Setting::where('key', 'report_exclude_product')->first();
        if($getExclude){
            $getExclude = $getExclude->value;
        }

        if(!empty($getExclude)){
           $excludeProduct = explode(',', $getExclude);
           $tag = $tag->whereNotIn('daily_report_trx_menu.id_product', $excludeProduct);
           $tagProduct = $tagProduct->whereNotIn('daily_report_trx_menu.id_product', $excludeProduct);
        }

        $data = ['tag'=>$tag->get()->toArray(), 'tagProduct'=>$tagProduct->get()->toArray()];
        if(isset($outlet)) $data['outlet'] = $outlet;
        return response()->json(MyHelper::checkGet($data));
     }

     function newTopProduct($type, Request $request){
        $post = $request->json()->all();
        
        if(!isset($post['month'])) $post['month'] = date('m');
        if(!isset($post['year'])) $post['year'] = date('Y');

        // EXCLUDE PRODUCT
        $getExclude = Setting::where('key', 'report_exclude_product')->first();
        if($getExclude){
            if($getExclude->value){
                $getExclude =  explode(',', $getExclude->value);
            }else{
                $getExclude = [];
            }
        }
   
        // // EXCLUDE TAG
        $getExcludeTag = Setting::where('key', 'report_exclude_tag')->first();
        if($getExcludeTag){
            $getExcludeTag = $getExcludeTag->value;
            if(!empty($getExcludeTag)){
                $getExcludeTag = explode(',', $getExcludeTag);
                $idProducts = array_pluck(ProductTag::whereIn('id_tag', $getExcludeTag)->get(),'id_product');
                if(count($idProducts) > 0){
                    if(!is_array($getExclude)){
                        $getExclude = [];
                    }
                    $getExclude = array_merge($getExclude, $idProducts);
                }
            }
        }
        
        $topNow = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
        ->select(DB::raw('MONTH(daily_report_trx_menu.trx_date) as month, YEAR(daily_report_trx_menu.trx_date) as year, products.id_product, products.product_code, products.product_name, SUM(daily_report_trx_menu.total_'.$type.') as total_'.$type))
        ->whereMonth('daily_report_trx_menu.trx_date', '=', $post['month'])
        ->whereYear('daily_report_trx_menu.trx_date', '=', $post['year'])
        ->groupBy('products.id_product')
        ->groupBy('month')
        ->groupBy('year')
        ->orderBy('total_'.$type, 'DESC')
        ->orderBy('products.id_product', 'ASC')
        ->limit(10);
        
        if(!empty($getExclude)){
            $topNow = $topNow->whereNotIn('daily_report_trx_menu.id_product', $getExclude);
        }
        $topNow = array_pluck($topNow->get(), 'id_product');

        $monthYear = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
        ->select(DB::raw('MONTH(daily_report_trx_menu.trx_date) as month, YEAR(daily_report_trx_menu.trx_date) as year'))
        ->whereDate('daily_report_trx_menu.trx_date', '<', $post['year'].'-'.$post['month'].'-01')
        ->distinct()
        ->get();

        if(count($monthYear) > 0){
            foreach ($monthYear as $key => $value) {
                if(count($topNow) > 0){
                    $top = DailyReportTrxMenu::leftJoin('products', 'daily_report_trx_menu.id_product', 'products.id_product')
                                ->select(DB::raw('MONTH(daily_report_trx_menu.trx_date) as month, YEAR(daily_report_trx_menu.trx_date) as year, products.id_product, products.product_code, products.product_name, SUM(daily_report_trx_menu.total_'.$type.') as total_'.$type))
                                ->whereMonth('daily_report_trx_menu.trx_date', '=', $value->month)
                               ->whereYear('daily_report_trx_menu.trx_date', '=', $value->year)
                               ->groupBy('products.id_product')
                               ->groupBy('month')
                               ->groupBy('year')
                               ->orderBy('total_'.$type, 'DESC')
                               ->orderBy('products.id_product', 'ASC')
                               ->limit(10);

                    if(!empty($getExclude)){
                        $top = $top->whereNotIn('daily_report_trx_menu.id_product', $getExclude);
                    }
                    
                    $topNow = array_diff($topNow, array_pluck($top->get(), 'id_product'));
                }else{
                    break;
                }
            }
        }
    
        if(count($topNow) > 0){
            $newTopProduct = array();
            foreach ($topNow as $key => $value) {
                $product = Product::find($value);
                if($product){
                    array_push($newTopProduct, $product);
                } 
            }
            return response()->json(MyHelper::checkGet($newTopProduct));
        }else{
            return response()->json(MyHelper::checkGet($topNow));
        }

     }

     function getMinYear(){
         $minYear = Transaction::min('transaction_date');
         if($minYear){
             return response()->json([
                 'status' => 'success',
                 'result' => date('Y', strtotime($minYear))
             ]);
         }else{
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction is Empty']
            ]);
         }
     }
}
