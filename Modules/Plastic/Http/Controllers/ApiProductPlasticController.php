<?php

namespace Modules\Plastic\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Validator;
use Hash;
use DB;
use Mail;

class ApiProductPlasticController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    function index(){
        $data = Product::where('product_type', 'plastic')->get()->toArray();

        foreach ($data as $key => $dt){
            $globalPrice = ProductGlobalPrice::where('id_product', $dt['id_product'])->first();
            $data[$key]['global_price'] = number_format($globalPrice['product_global_price'])??"";
        }
        return response()->json(MyHelper::checkGet($data));
    }

    function store(Request $request){
        $post = $request->json()->all();
        if(isset($post['product_code']) && !empty($post['product_code'])
            && isset($post['product_name']) && !empty($post['product_name'])
            && isset($post['product_capacity']) && !empty($post['product_capacity'])){
            $price = $post['global_price'];
            unset($post['global_price']);

            $check = Product::where('product_code', $post['product_code'])->first();
            if(!empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Product code already exist']]);
            }

            $post['product_name_pos'] = " ";
            $create = Product::create($post);

            if($create && !empty($price)){
                ProductGlobalPrice::updateOrCreate(['id_product' => $create['id_product']],
                    ['product_global_price' => str_replace(".","",$price)]);
            }
            return response()->json(MyHelper::checkCreate($create));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    function detail(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_product']) && !empty($post['id_product'])){
            $detail = Product::where('id_product', $post['id_product'])->first();
            if(!empty($detail)){
                $globalPrice = ProductGlobalPrice::where('id_product', $post['id_product'])->first();
                $detail['global_price'] = number_format($globalPrice['product_global_price'])??null;
            }

            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    function update(Request $request)
    {
        $post = $request->json()->all();
        if(isset($post['id_product']) && !empty($post['id_product'])
            && isset($post['product_name']) && !empty($post['product_name'])
            && isset($post['product_capacity']) && !empty($post['product_capacity'])){

            $price = $post['global_price'];
            unset($post['global_price']);

            $post['product_name_pos'] = " ";
            $create = Product::where('id_product', $post['id_product'])->update($post);

            if($create && !empty($price)){
                $price = str_replace(".","",$price);
                $price = str_replace(",","",$price);

                ProductGlobalPrice::updateOrCreate(['id_product' => $post['id_product']],
                    ['id_product' => $post['id_product'], 'product_global_price' => $price]);
            }
            return response()->json(MyHelper::checkCreate($create));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }
    }

    function destroy(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_product']) && !empty($post['id_product'])){
            $check = TransactionProduct::where('id_product', $post['id_product'])->first();
            if(!empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Product already use']]);
            }

            $delete = Product::where('id_product', $post['id_product'])->delete();
            ProductGlobalPrice::where('id_product', $post['id_product'])->delete();
            ProductSpecialPrice::where('id_product', $post['id_product'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    function visibility(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_product']) && !empty($post['id_product'])){
            $update = Product::where('id_product', $post['id_product'])->update(['product_visibility' => $post['product_visibility']]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
