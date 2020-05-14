<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\NewsProduct;
use App\Http\Models\TransactionProduct;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierBrand;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\Product\Entities\ProductProductPromoCategory;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Image;

use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\Brand;

use Modules\Product\Http\Requests\product\Create;
use Modules\Product\Http\Requests\product\Update;
use Modules\Product\Http\Requests\product\Delete;
use Modules\Product\Http\Requests\product\UploadPhoto;
use Modules\Product\Http\Requests\product\UpdatePhoto;
use Modules\Product\Http\Requests\product\DeletePhoto;
use Modules\Product\Http\Requests\product\Import;
use Modules\Product\Http\Requests\product\UpdateAllowSync;

class ApiProductController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/product/item/";

    function checkInputProduct($post=[], $type=null) {
    	$data = [];

    	if (empty($post['id_product_category']) || isset($post['id_product_category'])) {

            if (empty($post['id_product_category'])) {
                $data['id_product_category'] = NULL;
            }
            else {
                $data['id_product_category'] = $post['id_product_category'];
            }
    	}
    	if (isset($post['product_code'])) {
    		$data['product_code'] = $post['product_code'];
    	} else {
            $data['product_code'] = MyHelper::createrandom(3);
        }
    	if (isset($post['product_name'])) {
    		$data['product_name'] = $post['product_name'];
    	}
        if (isset($post['product_name_pos'])) {
            $data['product_name_pos'] = $post['product_name_pos'];
        }
    	if (isset($post['product_description'])) {
    		$data['product_description'] = $post['product_description'];
    	}
    	if (isset($post['product_video'])) {
    		$data['product_video'] = $post['product_video'];
    	}
    	if (isset($post['product_price'])) {
    		$data['product_price'] = $post['product_price'];
    	}
    	if (isset($post['product_weight'])) {
    		$data['product_weight'] = $post['product_weight'];
    	}
    	if (isset($post['product_visibility'])) {
    		$data['product_visibility'] = $post['product_visibility'];
    	}
    	if (isset($post['product_order'])) {
    		$data['product_order'] = $post['product_order'];
    	}
        if (isset($post['product_brands'])) {
            $data['product_brands'] = $post['product_brands'];
        }

        // search position
        if ($type == "create") {
            if (isset($post['id_product_category'])) {
                $data['position'] = $this->searchLastSorting($post['id_product_category']);
            }
            else {
                $data['position'] = $this->searchLastSorting(null);
            }
        }

    	return $data;
    }

    /**
     * cari urutan ke berapa
     */
    function searchLastSorting($id_product_category=null) {
        $sorting = Product::select('position')->orderBy('position', 'DESC');

        if (is_null($id_product_category)) {
            $sorting->whereNull('id_product_category');
        }
        else {
            $sorting->where('id_product_category', $id_product_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        }
        else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->position)) {
                return 1;
            }
            else {
                $sorting = $sorting->position + 1;
                return $sorting;
            }
        }
    }

    public function priceUpdate(Request $request) {
		$post = $request->json()->all();
        $date_time = date('Y-m-d H:i:s');
		foreach ($post['id_product_price'] as $key => $id_product_price) {
			if($id_product_price == 0){
				$update = ProductPrice::create(['id_product' => $post['id_product'],
												'id_outlet' => $post['id_outlet'][$key],
												'product_price' => $post['product_price'][$key],
												'product_price_base' => $post['product_price_base'][$key],
												'product_price_tax' => $post['product_price_tax'][$key],
												'product_stock_status' => $post['product_stock_status'][$key],
												'product_visibility' => $post['product_visibility'][$key]
												]);
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $post['id_outlet'][$key],
                    'date_time' => $date_time,
                    'new_status' => $post['product_stock_status'][$key],
                    'id_outlet_app_otp' => null
                ]);
			}
			else{
                $pp = ProductPrice::where('id_product_price','=',$id_product_price)->first();
                if(!$pp){continue;}
                $old_status = $pp->product_stock_status;
                if(strtolower($old_status) != strtolower($post['product_stock_status'][$key])){
                    $create = ProductStockStatusUpdate::create([
                        'id_product' => $post['id_product'],
                        'id_user' => $request->user()->id,
                        'user_type' => 'users',
                        'id_outlet' => $post['id_outlet'][$key],
                        'date_time' => $date_time,
                        'new_status' => $post['product_stock_status'][$key],
                        'id_outlet_app_otp' => null
                    ]);
                }
				$update = ProductPrice::where('id_product_price','=',$id_product_price)->update(['product_price' => $post['product_price'][$key], 'product_price_base' => $post['product_price_base'][$key], 'product_price_tax' => $post['product_price_tax'][$key],'product_stock_status' => $post['product_stock_status'][$key],'product_visibility' => $post['product_visibility'][$key]]);
			}
		}
		return response()->json(MyHelper::checkUpdate($update));
	}

    public function updateProductDetail(Request $request) {
        $post = $request->json()->all();
        $date_time = date('Y-m-d H:i:s');
        foreach ($post['id_product_detail'] as $key => $id_product_detail) {
            if($id_product_detail == 0){
                $update = ProductDetail::create(['id_product' => $post['id_product'],
                    'id_outlet' => $post['id_outlet'][$key],
                    'product_stock_status' => $post['product_detail_stock_status'][$key],
                    'product_visibility' => $post['product_detail_visibility'][$key]
                ]);
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $post['id_outlet'][$key],
                    'date_time' => $date_time,
                    'new_status' => $post['product_detail_stock_status'][$key],
                    'id_outlet_app_otp' => null
                ]);
            }
            else{
                $pp = ProductDetail::where('id_product_detail','=',$id_product_detail)->first();
                if(!$pp){continue;}
                $old_status = $pp->product_stock_status;
                if(strtolower($old_status) != strtolower($post['product_detail_stock_status'][$key])){
                    $create = ProductStockStatusUpdate::create([
                        'id_product' => $post['id_product'],
                        'id_user' => $request->user()->id,
                        'user_type' => 'users',
                        'id_outlet' => $post['id_outlet'][$key],
                        'date_time' => $date_time,
                        'new_status' => $post['product_detail_stock_status'][$key],
                        'id_outlet_app_otp' => null
                    ]);
                }
                $update = ProductDetail::where('id_product_detail','=',$id_product_detail)->update(['product_detail_stock_status' => $post['product_detail_stock_status'][$key],'product_detail_visibility' => $post['product_detail_visibility'][$key]]);
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updatePriceDetail(Request $request) {
        $post = $request->json()->all();

        foreach ($post['id_product_special_price'] as $key => $id_product_special_price) {
            if($id_product_special_price == 0){
                if(!is_null($post['product_price'][$key])){
                    $update = ProductSpecialPrice::create(['id_product' => $post['id_product'],
                        'id_outlet' => $post['id_outlet'][$key],
                        'product_special_price' => str_replace(".","",$post['product_price'][$key])
                    ]);
                }
            }
            else{
                $pp = ProductSpecialPrice::where('id_product_special_price','=',$id_product_special_price)->first();
                if(!$pp){continue;}
                if(!is_null($post['product_price'][$key])) {
                    $update = ProductSpecialPrice::where('id_product_special_price', '=', $id_product_special_price)
                        ->update(['product_special_price' => str_replace(".","",$post['product_price'][$key])]);
                }
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function categoryAssign(Request $request) {
		$post = $request->json()->all();
		foreach ($post['id_product'] as $key => $idprod) {
            $count = BrandProduct::where('id_product',$idprod)->count();
			if($post['id_product_category'][$key] == 0){
				$update = Product::where('id_product','=',$idprod)->update(['id_product_category' => null, 'product_name' => $post['product_name'][$key]]);
                if($count){
                    BrandProduct::where(['id_product'=>$idprod])->update(['id_product_category' => null]);
                }else{
                    BrandProduct::create(['id_product'=>$idprod,'id_product_category' => null]);
                }
			}else{
				$update = Product::where('id_product','=',$idprod)->update(['id_product_category' => $post['id_product_category'][$key], 'product_name' => $post['product_name'][$key]]);
                if($count){
                    BrandProduct::where(['id_product'=>$idprod])->update(['id_product_category' => $post['id_product_category'][$key]]);
                }else{
                    BrandProduct::create(['id_product'=>$idprod,'id_product_category' => $post['id_product_category'][$key]]);
                }
            }
		}
		return response()->json(MyHelper::checkUpdate($update));
	}

    /**
     * Export data product
     * @param Request $request Laravel Request Object
     */
    public function import(Request $request) {
        $post = $request->json()->all();
        $result = [
            'processed' => 0,
            'invalid' => 0,
            'updated' => 0,
            'updated_price' => 0,
            'updated_price_fail' => 0,
            'create' => 0,
            'create_category' => 0,
            'no_update' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        switch ($post['type']) {
            case 'global':
                // update or create if not exist
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->exists();
                if($check_brand){
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['product_code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['product_name'])){
                            unset($value['product_name']);
                        }
                        if(empty($value['product_description'])){
                            unset($value['product_description']);
                        }
                        $product = Product::where('product_code',$value['product_code'])->first();
                        if($product){
                            if($product->update($value)){
                                $result['updated']++;
                            }else{
                                $result['no_update']++;
                            }
                        }else{
                            $product = Product::create($value);
                            if($product){
                                $result['create']++;
                            }else{
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Product with product code {$value['product_code']} failed to be created";
                                continue;
                            }
                        }
                        $update = BrandProduct::updateOrCreate([
                            'id_brand'=>$post['id_brand'],
                            'id_product'=>$product->id_product
                        ]);
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'detail':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['product_code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['product_name'])){
                            unset($value['product_name']);
                        }
                        if(empty($value['product_description'])){
                            unset($value['product_description']);
                        }
                        if(empty($value['position'])){
                            unset($value['position']);
                        }
                        if(empty($value['product_visibility'])){
                            unset($value['product_visibility']);
                        }
                        $product = Product::join('brand_product','products.id_product','=','brand_product.id_product')
                            ->where([
                                'id_brand' => $check_brand->id_brand,
                                'product_code' => $value['product_code']
                            ])->first();
                        if(!$product){
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product with product code {$value['product_code']} in selected brand not found";
                            continue;
                        }
                        if(empty($value['product_category_name'])){
                            unset($value['product_category_name']);
                        }else{
                            $pc = ProductCategory::where('product_category_name',$value['product_category_name'])->first();
                            if(!$pc){
                                $result['create_category']++;
                                $pc = ProductCategory::create([
                                    'product_category_name' => $value['product_category_name']
                                ]);
                            }
                            $value['id_product_category'] = $pc->id_product_category;
                            unset($value['product_category_name']);
                        }
                        $update1 = $product->update($value);
                        if($value['id_product_category']??false){
                            $update2 = BrandProduct::where('id_product',$product->id_product)->update(['id_product_category'=>$value['id_product_category']]);
                        }
                        if($update1 || $update2){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'price':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    $global_outlets = Outlet::select('id_outlet','outlet_code')->where([
                        'outlet_different_price' => 0
                    ])->get();
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['product_code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['product_name'])){
                            unset($value['product_name']);
                        }
                        if(empty($value['product_description'])){
                            unset($value['product_description']);
                        }
                        if(empty($value['global_price'])){
                            unset($value['global_price']);
                        }
                        $product = Product::join('brand_product','products.id_product','=','brand_product.id_product')
                            ->where([
                                'id_brand' => $check_brand->id_brand,
                                'product_code' => $value['product_code']
                            ])->first();
                        if(!$product){
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product with product code {$value['product_code']} in selected brand not found";
                            continue;
                        }
                        $update1 = $product->update($value);
                        if($update1){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                        if($value['global_price']??false){
                            foreach ($global_outlets as $outlet) {
                                $pp = ProductGlobalPrice::where([
                                    'id_product' => $product->id_product
                                ])->first();
                                if($pp){
                                    $update = $pp->update(['product_global_price'=>$value['global_price']]);
                                }else{
                                    $update = ProductGlobalPrice::create([
                                        'id_product' => $product->id_product,
                                        'product_global_price'=>$value['global_price']
                                    ]);
                                }
                                if($update){
                                    $result['updated_price']++;
                                }else{
                                    if($update !== 0){
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed set price for product {$value['product_code']} at outlet {$outlet->outlet_code} failed";
                                    }
                                }
                            }
                        }
                        foreach ($value as $col_name => $col_value) {
                            if(!$col_value){
                                continue;
                            }
                            if(strpos($col_name, 'price_') !== false){
                                $outlet_code = str_replace('price_', '', $col_name);
                                $pp = ProductSpecialPrice::join('outlets','outlets.id_outlet','=','product_special_price.id_outlet')
                                ->where([
                                    'outlet_code' => $outlet_code,
                                    'id_product' => $product->id_product
                                ])->first();
                                if($pp){
                                    $update = $pp->update(['product_special_price'=>$col_value]);
                                }else{
                                    $id_outlet = Outlet::select('id_outlet')->where('outlet_code',$outlet_code)->pluck('id_outlet')->first();
                                    if(!$id_outlet){
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed create new price for product {$value['product_code']} at outlet $outlet_code failed";
                                        continue;
                                    }
                                    $update = ProductSpecialPrice::create([
                                        'id_outlet' => $id_outlet,
                                        'id_product' => $product->id_product,
                                        'product_special_price'=>$col_value
                                    ]);
                                }
                                if($update){
                                    $result['updated_price']++;
                                }else{
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set price for product {$value['product_code']} at outlet $outlet_code failed";
                                }
                            }
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'modifier-price':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    $global_outlets = Outlet::select('id_outlet','outlet_code')->where([
                        'outlet_different_price' => 0
                    ])->get();
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['name'])){
                            unset($value['name']);
                        }else{
                            $value['text'] = $value['name'];
                            unset($value['name']);
                        }
                        if(empty($value['type'])){
                            unset($value['type']);
                        }
                        if(empty($value['global_price'])){
                            unset($value['global_price']);
                        }
                        $product = ProductModifier::select('product_modifiers.*')->leftJoin('product_modifier_brands','product_modifiers.id_product_modifier','=','product_modifier_brands.id_product_modifier')
                            ->where('code',$value['code'])->where(function($q) use ($post) {
                                $q->where('id_brand',$post['id_brand'])->orWhere('modifier_type','<>','Global Brand');
                            })->first();
                        if(!$product){
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product modifier with code {$value['code']} in selected brand not found";
                            continue;
                        }
                        $update1 = $product->update($value);
                        if($update1){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                        if($value['global_price']??false){
                            $update = ProductModifierGlobalPrice::create([
                                'id_product_modifier' => $product->id_product_modifier,
                                'product_modifier_price'=>$value['global_price']
                            ]);
                            if($update){
                                $result['updated_price']++;
                            }else{
                                if($update !== 0){
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set global price for product modifier {$value['code']}";
                                }
                            }
                        }
                        foreach ($value as $col_name => $col_value) {
                            if(!$col_value){
                                continue;
                            }
                            if(strpos($col_name, 'price_') !== false){
                                $outlet_code = str_replace('price_', '', $col_name);
                                $pp = ProductModifierPrice::join('outlets','outlets.id_outlet','=','product_modifier_prices.id_outlet')
                                ->where([
                                    'outlet_code' => $outlet_code,
                                    'id_product_modifier' => $product->id_product_modifier
                                ])->first();
                                if($pp){
                                    $update = $pp->update(['product_modifier_price'=>$col_value]);
                                }else{
                                    $id_outlet = Outlet::select('id_outlet')->where('outlet_code',$outlet_code)->pluck('id_outlet')->first();
                                    if(!$id_outlet){
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed create new price for product modifier {$value['code']} at outlet $outlet_code failed";
                                        continue;
                                    }
                                    $update = ProductModifierPrice::create([
                                        'id_outlet' => $id_outlet,
                                        'id_product_modifier' => $product->id_product_modifier,
                                        'product_modifier_price'=>$col_value
                                    ]);
                                }
                                if($update){
                                    $result['updated_price']++;
                                }else{
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set price for product modifier {$value['code']} at outlet $outlet_code failed";
                                }
                            }
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product modifier\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'modifier':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['name'])){
                            unset($value['name']);
                        }else{
                            $value['text'] = $value['name'];
                            unset($value['name']);
                        }
                        if(empty($value['type'])){
                            unset($value['type']);
                        }
                        $product = ProductModifier::select('product_modifiers.*')->leftJoin('product_modifier_brands','product_modifiers.id_product_modifier','=','product_modifier_brands.id_product_modifier')
                            ->where('code',$value['code'])->where(function($q) use ($post) {
                                $q->where('id_brand',$post['id_brand'])->orWhere('modifier_type','<>','Global Brand');
                            })->first();
                        if(!$product){
                            $value['modifier_type'] = 'Global Brand';
                            $product = ProductModifier::create($value);
                            if($product){
                                ProductModifierBrand::create(['id_product_modifier'=>$product->id_product_modifier,'id_brand'=>$post['id_brand']]);
                                $result['create']++;
                            }else{
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Product modifier with code {$value['code']} failed to be created";
                            }
                            continue;
                        }
                        $update1 = $product->update($value);
                        if($product->modifier_type == 'Global Brand'){
                            ProductModifierBrand::updateOrCreate(['id_product_modifier'=>$product->id_product_modifier,'id_brand'=>$post['id_brand']]);
                        }
                        if($update1){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product modifier\'s brand does not match with selected brand']
                    ];
                }
                break;

            default:
                # code...
                break;
        }
        $response = [];
        if($result['invalid']+$result['processed']<=0){
            return MyHelper::checkGet([],'File empty');
        }else{
            $response[] = $result['invalid']+$result['processed'].' total data found';
        }
        if($result['processed']){
            $response[] = $result['processed'].' data processed';
        }
        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' product';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new product';
        }
        if($result['create_category']){
            $response[] = 'Create '.$result['create_category'].' new category';
        }
        if($result['no_update']){
            $response[] = $result['no_update'].' product not updated';
        }
        if($result['invalid']){
            $response[] = $result['invalid'].' row data invalid';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' product';
        }
        if($result['not_found']){
            $response[] = $result['not_found'].' product not found';
        }
        if($result['updated_price']){
            $response[] = 'Update '.$result['updated_price'].' product price';
        }
        if($result['updated_price_fail']){
            $response[] = 'Update '.$result['updated_price_fail'].' product price fail';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    /**
     * Export data product
     * @param Request $request Laravel Request Object
     */
    public function export(Request $request) {
        $post = $request->json()->all();
        switch ($post['type']) {
            case 'global':
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = Product::select('product_code','product_name','product_description')
                    ->join('brand_product','brand_product.id_product','=','products.id_product')
                    ->where('id_brand',$post['id_brand'])
                    ->groupBy('products.id_product')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                break;

            case 'detail':
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = Product::select('product_categories.product_category_name','products.position','product_code','product_name','product_description','products.product_visibility')
                    ->join('brand_product','brand_product.id_product','=','products.id_product')
                    ->where('id_brand',$post['id_brand'])
                    ->leftJoin('product_categories','product_categories.id_product_category','=','brand_product.id_product_category')
                    ->groupBy('products.id_product')
                    ->groupBy('product_category_name')
                    ->orderBy('product_category_name')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                break;

            case 'price':
                $different_outlet = Outlet::select('outlet_code','id_product','product_special_price.product_special_price as product_price')
                    ->leftJoin('product_special_price','outlets.id_outlet','=','product_special_price.id_outlet')
                    ->where('outlet_different_price',1)->get();
                $do = MyHelper::groupIt($different_outlet,'outlet_code',null,function($key,&$val){
                    $val = MyHelper::groupIt($val,'id_product');
                    return $key;
                });
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = Product::select('products.id_product','product_code','product_name','product_description','product_global_price.product_global_price as global_price')
                    ->join('brand_product','brand_product.id_product','=','products.id_product')
                    ->leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                    ->where('id_brand',$post['id_brand'])
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                foreach ($data['products'] as $key => &$product) {
                    $inc = 0;
                    foreach ($do as $outlet_code => $x) {
                        $inc++;
                        $product['price_'.$outlet_code] = $x[$product['id_product']][0]['product_price']??'';
                        if($inc === count($do)){
                            unset($product['id_product']);
                        }
                    }
                }
                break;

            case 'modifier-price':
                $subquery = str_replace('?','0',ProductModifierPrice::select(\DB::raw('id_product_modifier,MAX(product_modifier_price) as global_price'))->leftJoin('outlets','outlets.id_outlet','=','product_modifier_prices.id_outlet')
                    ->where('outlets.outlet_different_price','=',0)
                    ->groupBy('id_product_modifier')
                    ->toSql());
                $different_outlet = Outlet::select('outlet_code','id_product_modifier','product_modifier_price')
                    ->leftJoin('product_modifier_prices','outlets.id_outlet','=','product_modifier_prices.id_outlet')
                    ->where('outlet_different_price',1)->get();
                $do = MyHelper::groupIt($different_outlet,'outlet_code',null,function($key,&$val){
                    $val = MyHelper::groupIt($val,'id_product_modifier');
                    return $key;
                });
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = ProductModifier::select('product_modifiers.id_product_modifier','type','code','text as name','global_prices.global_price')
                    ->leftJoin('product_modifier_brands','product_modifier_brands.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->leftJoin(DB::raw('('.$subquery.') as global_prices'),'product_modifiers.id_product_modifier','=','global_prices.id_product_modifier')
                    ->where(function($q) use ($post){
                        $q->where('id_brand',$post['id_brand'])
                            ->orWhere('modifier_type','<>','Global Brand');
                    })
                    ->orderBy('type')
                    ->orderBy('text')
                    ->orderBy('product_modifiers.id_product_modifier')
                    ->distinct()
                    ->get();
                foreach ($data['products'] as $key => &$product) {
                    $inc = 0;
                    foreach ($do as $outlet_code => $x) {
                        $inc++;
                        $product['price_'.$outlet_code] = $x[$product['id_product_modifier']][0]['product_modifier_price']??'';
                        if($inc === count($do)){
                            unset($product['id_product_modifier']);
                        }
                    }
                }
                break;

            case 'modifier':
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = ProductModifier::select('type','code','text as name')
                    ->leftJoin('product_modifier_brands','product_modifier_brands.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->where(function($q) use ($post){
                        $q->where('id_brand',$post['id_brand'])
                            ->orWhere('modifier_type','<>','Global Brand');
                    })
                    ->orderBy('type')
                    ->orderBy('text')
                    ->orderBy('product_modifiers.id_product_modifier')
                    ->distinct()
                    ->get();
                break;

            default:
                # code...
                break;
        }
        return MyHelper::checkGet($data);
    }

    /* Pengecekan code unique */
    function cekUnique($id, $code) {
        $cek = Product::where('product_code', $code)->first();

        if (empty($cek)) {
            return true;
        }
        else {
            if ($cek->id_product == $id) {
                return true;
            }
            else {
                return false;
            }
        }
    }

    /**
     * list product
     */
    function listProduct(Request $request) {
        $post = $request->json()->all();

		if (isset($post['id_outlet'])) {
            $product = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                                ->leftJoin('product_special_price','product_special_price.id_product','=','products.id_product')
									->where('product_detail.id_outlet','=',$post['id_outlet'])
									->where('product_detail.product_detail_visibility','=','Visible')
                                    ->where('product_detail.product_detail_status','=','Active')
                                    ->with(['category', 'discount']);

            if (isset($post['visibility'])) {

                if($post['visibility'] == 'Hidden'){
                    $idVisible = ProductDetail::join('products', 'products.id_product','=', 'product_detail.id_product')
                                            ->where('product_detail.product_detail_visibility', 'Visible')
                                            ->where('product_detail.product_detail_status', 'Active')
                                            ->whereNotNull('id_product_category')
                                            ->where('id_outlet', $post['id_outlet'])
                                            ->select('product_detail.id_product')->get();
                    $product = Product::whereNotIn('products.id_product', $idVisible)->with(['category', 'discount']);
                }else{
                    $product = $product->whereNotNull('id_product_category');
                }

                unset($post['id_outlet']);
            }
		} else {
		    if(isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price'){
                $product = Product::with(['category', 'discount', 'product_special_price', 'global_price']);
            }elseif(isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail'){
                $product = Product::with(['category', 'discount', 'product_detail']);
            }else{
                $product = Product::with(['category', 'discount']);
            }
		}

		if(isset($post['rule'])){
            foreach ($post['rule'] as $rule){
                if($rule[0] !== 'all_product'){
                    if($post['operator'] == 'or'){
                        if(isset($rule[2])){
                            $product->orWhere('products.'.$rule[0], $rule[1],$rule[2]);
                        }else{
                            $product->orWhere('products.'.$rule[0], $rule[1]);
                        }
                    }else{
                        if(isset($rule[2])){
                            $product->where('products.'.$rule[0], $rule[1],$rule[2]);
                        }else{
                            $product->where('products.'.$rule[0], $rule[1]);
                        }
                    }
                }
            }
        }

        if (isset($post['id_product'])) {
            $product->with('category')->where('products.id_product', $post['id_product'])->with(['brands']);
        }

        if (isset($post['product_code'])) {
            $product->with(['global_price','product_special_price','product_tags','brands','product_promo_categories'=>function($q){$q->select('product_promo_categories.id_product_promo_category');}])->where('products.product_code', $post['product_code']);
        }

        if (isset($post['product_name'])) {
            $product->where('products.product_name', 'LIKE', '%'.$post['product_name'].'%');
        }

        if(isset($post['orderBy'])){
            $product = $product->orderBy($post['orderBy']);
        }
        else{
            $product = $product->orderBy('position');
        }

        if(isset($post['admin_list'])){
            $product = $product->withCount('product_detail')->withCount('product_detail_hiddens');
        }

        if(isset($post['pagination'])){
            $product = $product->paginate(10);
        }else{
            $product = $product->get();
        }

        if (!empty($product)) {
            foreach ($product as $key => $value) {
                $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("'.env('S3_URL_API').'", product_photo)), "'.env('S3_URL_API').'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
            }
        }

        $product = $product->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    function listProductImage(Request $request) {
        $post = $request->json()->all();

        if (isset($post['image']) && $post['image'] == 'null') {
            $product = Product::leftJoin('product_photos','product_photos.id_product','=','products.id_product')
                            ->whereNull('product_photos.product_photo')->get();
        } else {
            $product = Product::get();
            if (!empty($product)) {
                foreach ($product as $key => $value) {
                    unset($product[$key]['product_price_base']);
                    unset($product[$key]['product_price_tax']);
                    $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("'.env('S3_URL_API').'", product_photo)), "'.env('S3_URL_API').'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
                }
            }
        }

        $product = $product->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    function imageOverride(Request $request) {
        $post = $request->json()->all();

        if (isset($post['status'])) {
            try {
                Setting::where('key', 'image_override')->update(['value' => $post['status']]);
                return response()->json(MyHelper::checkGet('true'));
            } catch (\Exception $e) {
                return response()->json(MyHelper::checkGet($e));
            }
        }

        $setting = Setting::where('key', 'image_override')->first();

        if (!$setting) {
            Setting::create([
                'key'       => 'image_override',
                'value'     => 0
            ]);

            $setting = 'false';
        } else {
            if ($setting->value == 0) {
                $setting = 'false';
            } else {
                $setting = 'true';
            }
        }

        return response()->json(MyHelper::checkGet($setting));
    }

    /**
     * create  product
     */
    function create(Create $request) {
        $post = $request->json()->all();

        // check data
        $data = $this->checkInputProduct($post, $type="create");
        // return $data;
        $save = Product::create($data);

		if($save){
			$listOutlet = Outlet::get()->toArray();
			foreach($listOutlet as $outlet){
				$data = [];
				$data['id_product'] = $save->id_product;
				$data['id_outlet'] = $outlet['id_outlet'];
				$data['product_price'] = null;
				// $data['product_visibility'] = 'Visible';

                ProductPrice::create($data);
            }

            if(is_array($brands=$data['product_brands']??false)){
                foreach ($brands as $id_brand) {
                    BrandProduct::create([
                        'id_product'=>$save['id_product'],
                        'id_brand'=>$id_brand
                    ]);
                }
            }

            //create photo
            if(isset($post['photo'])){

                $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $dataPhoto['product_photo'] = $upload['path'];
                }
                else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload image']
                    ];

                    return response()->json($result);
                }

                $dataPhoto['id_product']          = $save->id_product;
                $dataPhoto['product_photo_order'] = $this->cekUrutanPhoto($save['id_product']);
                $save                             = ProductPhoto::create($dataPhoto);
            }

		}

        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update product
     */
    function update(Update $request) {
    	$post = $request->json()->all();

    	// check data
        DB::beginTransaction();
        if(is_array($brands=$post['product_brands']??false)){
            if(in_array('*', $post['product_brands'])){
                $brands=Brand::select('id_brand')->get()->toArray();
                $brands=array_column($brands, 'id_brand');
            }
            BrandProduct::where('id_product',$request->json('id_product'))->delete();
            foreach ($brands as $id_brand) {
                BrandProduct::create([
                    'id_product'=>$request->json('id_product'),
                    'id_brand'=>$id_brand,
                    'id_product_category'=>$request->json('id_product_category')
                ]);
            }
        }
        unset($post['product_brands']);
        // promo_category
        ProductProductPromoCategory::where('id_product',$post['id_product'])->delete();
        ProductProductPromoCategory::insert(array_map(function($id_product_promo_category) use ($post) {
            return [
                'id_product' =>$post['id_product'],
                'id_product_promo_category' => $id_product_promo_category
            ];
        },$post['id_product_promo_category']??[]));
        unset($post['id_product_promo_category']);

    	$data = $this->checkInputProduct($post);

        if (isset($post['product_code'])) {
            if (!$this->cekUnique($post['id_product'], $post['product_code'])) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['product code already used.']
                ]);
            }
        }

    	$save = Product::where('id_product', $post['id_product'])->update($data);

    	if($save){
            if(isset($post['photo'])){
                //delete all photo
                $delete = $this->deletePhoto($post['id_product']);


                    //create photo
                    $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $dataPhoto['product_photo'] = $upload['path'];
                    }
                    else {
                        $result = [
                            'status'   => 'fail',
                            'messages' => ['fail upload image']
                        ];

                        return response()->json($result);
                    }

                    $dataPhoto['id_product']          = $post['id_product'];
                    $dataPhoto['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
                    $save                        = ProductPhoto::create($dataPhoto);


            }

            if(isset($post['product_global_price'])){
                ProductGlobalPrice::updateOrCreate(['id_product' => $post['id_product']],
                    ['product_global_price' => str_replace(".","",$post['product_global_price'])]);
            }
        }
        if($save){
            DB::commit();
        }else{
            DB::rollBack();
        }


    	return response()->json(MyHelper::checkUpdate($save));
    }

    /**
     * delete product
     */
    function delete(Delete $request) {
        $product = Product::with('prices')->find($request->json('id_product'));

    	$check = $this->checkDeleteProduct($request->json('id_product'));

    	if ($check) {
    		// delete photo
    		$deletePhoto = $this->deletePhoto($request->json('id_product'));

    		// delete product
    		$delete = Product::where('id_product', $request->json('id_product'))->delete();

            if($delete){
                $result = [
                    'status' => 'success',
                    'product' => [
                        'id_product' => $product['id_product'],
                        'plu_id' => $product['product_code'],
                        'product_name' => $product['product_name'],
                        'product_name_pos' => $product['product_name_pos'],
                        'product_prices' => $product['prices'],
                    ],
                ];
            }
			else{
                $result = ['status' => 'fail', 'messages' => ['failed to delete data']];
            }

    		return response()->json($result);

    	}
    	else {
    		return response()->json([
				'status'   => 'fail',
				'messages' => ['product has been used.']
    		]);
    	}

    }

    /**
     * delete photo product
     */
    function deletePhoto($id) {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product', $id)->get()->toArray();

        if (!empty($dataPhoto)) {
            foreach ($dataPhoto as $key => $value) {
                MyHelper::deletePhoto($value['product_photo']);
            }
        }

    	$delete = ProductPhoto::where('id_product', $id)->delete();

    	return $delete;
    }

    /**
     * checking delete
     */
    function checkDeleteProduct($id) {

    	// jika true semua maka boleh dihapus
    	if ( ($this->checkAtNews($id)) && ($this->checkAtTrx($id)) && $this->checkAtDiskon($id)) {
    		return true;
    	}
    	// klo ada yang sudah digunakan
    	else {
    		return false;
    	}
    }

    // check produk di transaksi
    function checkAtTrx($id) {
    	$check = TransactionProduct::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    // check product di diskon
    function checkAtDiskon($id) {
    	$check = ProductDiscount::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    // check product di news
    function checkAtNews($id) {
    	$check = NewsProduct::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    /**
     * upload photo
     */
    function uploadPhotoProduct(UploadPhoto $request) {
    	$post = $request->json()->all();

    	$data = [];

    	if (isset($post['photo'])) {

    	    $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

    	    if (isset($upload['status']) && $upload['status'] == "success") {
    	        $data['product_photo'] = $upload['path'];
    	    }
    	    else {
    	        $result = [
    	            'status'   => 'fail',
    	            'messages' => ['fail upload image']
    	        ];

    	        return response()->json($result);
    	    }
    	}

    	if (empty($data)) {
    		return reponse()->json([
    			'status' => 'fail',
    			'messages' => ['fail save to database']
    		]);
    	}
    	else {
            $data['id_product']          = $post['id_product'];
            $data['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
            $save                        = ProductPhoto::create($data);

    		return response()->json(MyHelper::checkCreate($save));
    	}
    }

    function uploadPhotoProductAjax(Request $request) {
    	$post = $request->json()->all();
    	$data = [];
        $checkCode = Product::where('product_code', $post['name'])->first();
    	if ($checkCode) {
            $checkSetting = Setting::where('key', 'image_override')->first();
            if ($checkSetting['value'] == 1) {
                $productPhoto = ProductPhoto::where('id_product', $checkCode->id_product)->first();
                if (file_exists($productPhoto->product_photo)) {
                    unlink($productPhoto->product_photo);
                }
            }
            $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300, $post['name'].'-'.strtotime("now"));

    	    if (isset($upload['status']) && $upload['status'] == "success") {
    	        $data['product_photo'] = $upload['path'];
    	    }
    	    else {
    	        $result = [
    	            'status'   => 'fail',
    	            'messages' => ['fail upload image']
    	        ];
    	        return response()->json($result);
    	    }
    	}
    	if (empty($data)) {
    		return reponse()->json([
    			'status' => 'fail',
    			'messages' => ['fail save to database']
    		]);
    	}
    	else {
            $data['id_product']          = $checkCode->id_product;
            $data['product_photo_order'] = $this->cekUrutanPhoto($checkCode->id_product);
            $save                        = ProductPhoto::updateOrCreate(['id_product' => $checkCode->id_product],$data);
    		return response()->json(MyHelper::checkCreate($save));
    	}
    }

    /*
    cek urutan
    */
    function cekUrutanPhoto($id) {
        $cek = ProductPhoto::where('id_product', $id)->orderBy('product_photo_order', 'DESC')->first();

        if (empty($cek)) {
            $cek = 1;
        }
        else {
            $cek = $cek->product_photo_order + 1;
        }

        return $cek;
    }

    /**
     * update photo
     */
    function updatePhotoProduct(UpdatePhoto $request) {
        $update = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->update([
            'product_photo_order' => $request->json('product_photo_order')
        ]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * delete photo
     */
    function deletePhotoProduct(DeletePhoto $request) {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->get()->toArray();

        $delete    = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->delete();

        if (!empty($dataPhoto)) {
            MyHelper::deletePhoto($dataPhoto[0]['product_photo']);
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* harga */
    function productPrices(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if($post['id_outlet'] == 0){
            if (isset($post['product_price'])) {
                $dataGlobalPrice['product_global_price'] = $post['product_price'];
            }
            $save = ProductGlobalPrice::updateOrCreate([
                'id_product' => $data['id_product']
            ], $dataGlobalPrice);
        }else{
            if (isset($post['product_price'])) {
                $dataSpecialPrice['product_special_price'] = $post['product_price'];
            }
            $save = ProductSpecialPrice::updateOrCreate([
                'id_product' => $data['id_product'],
                'id_outlet'  => $data['id_outlet']
            ], $dataSpecialPrice);
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    function productDetail(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['product_visibility']) || $post['product_visibility'] == null) {
            if($post['product_visibility'] == null){
                $data['product_detail_visibility'] = 'Hidden';
            }else{
                $data['product_detail_visibility'] = $post['product_visibility'];
            }
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if (isset($post['product_stock_status'])) {
            $data['product_detail_stock_status'] = $post['product_stock_status'];
        }
        $product = ProductDetail::where([
            'id_product' => $data['id_product'],
            'id_outlet'  => $data['id_outlet']
        ])->first();

        if(($data['product_detail_stock_status']??false) && (($data['product_detail_stock_status']??false) != $product['product_detail_stock_status']??false)){
            $create = ProductStockStatusUpdate::create([
                'id_product' => $data['id_product'],
                'id_user' => $request->user()->id,
                'user_type' => 'users',
                'id_outlet' => $data['id_outlet'],
                'date_time' => date('Y-m-d H:i:s'),
                'new_status' => $data['product_detail_stock_status'],
                'id_outlet_app_otp' => null
            ]);
        }

        $save = ProductDetail::updateOrCreate([
            'id_product' => $data['id_product'],
            'id_outlet'  => $data['id_outlet']
        ], $data);

        return response()->json(MyHelper::checkUpdate($save));
    }

    function updateAllowSync(UpdateAllowSync $request) {
        $post = $request->json()->all();

        if($post['product_allow_sync'] == "true"){
            $allow = '1';
        }else{
            $allow = '0';
        }
    	$update = Product::where('id_product', $post['id_product'])->update(['product_allow_sync' => $allow]);

    	return response()->json(MyHelper::checkUpdate($update));
    }

    function visibility(Request $request)
    {
        $post = $request->json()->all();
        foreach ($post['id_visibility'] as $key => $value) {
            if($value){
                $id = explode('/', $value);
                $save = ProductDetail::updateOrCreate(['id_product' => $id[0], 'id_outlet' => $id[1]], ['product_detail_visibility' => $post['visibility']]);
                if(!$save){
                    return response()->json(MyHelper::checkUpdate($save));
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    /* product position */
    public function positionProductAssign(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['product_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product id is required']
            ];
        }
        // update position
        foreach ($post['product_ids'] as $key => $product_id) {
            $update = Product::find($product_id)->update(['position'=>$key+1]);
        }

        return ['status' => 'success'];
    }

    public function photoDefault(Request $request){
        $post = $request->json()->all();

         //create photo
         if (!file_exists('img/product/item/')) {
            mkdir('img/product/item/', 0777, true);
        }
         $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/product/item/', 300, 300, 'default', '.png');

         if (isset($upload['status']) && $upload['status'] == "success") {
            $result = [
                'status'   => 'success',
            ];
         }
         else {
             $result = [
                 'status'   => 'fail',
                 'messages' => ['fail upload image']
             ];

        }
        return response()->json($result);
    }

    public function updateVisibility(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_product'])) {
            return [
                'status' => 'fail',
                'messages' => ['Id product is required']
            ];
        }
        if (!isset($post['product_visibility'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product visibility is required']
            ];
        }
        // update visibility
        $update = Product::find($post['id_product'])->update(['product_visibility'=>$post['product_visibility']]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    function listProductPriceByOutlet(Request $request, $id_outlet) {
        $product = Product::with(['all_prices'=> function($q) use ($id_outlet){
            $q->where('id_outlet', $id_outlet);
        }])->get();
        return response()->json(MyHelper::checkGet($product));
    }
    function getNextID($id){
        $product = Product::where('id_product', '>', $id)->orderBy('id_product')->first();
        return response()->json(MyHelper::checkGet($product));
    }
    public function detail(Request $request) {
        $post = $request->json()->all();
        if(!($post['id_outlet']??false)){
            $post['id_outlet'] = Setting::where('key','default_outlet')->pluck('value')->first();
        }
        $outlet = Outlet::find($post['id_outlet']);
        if(!$outlet){
            return MyHelper::checkGet([],'Outlet not found');
        }
        //get product
        $product = Product::select('id_product','product_code','product_name','product_description','product_code','product_visibility')
        ->where('id_product',$post['id_product'])
        ->whereHas('brand_category')
        ->whereHas('product_detail',function($query) use ($post){
            $query->where('id_outlet',$post['id_outlet'])
            ->where('product_detail_status','=','Active');
        })
        ->with(['photos','brand_category'=>function($query) use ($post){
            $query->where('id_product',$post['id_product']);
            $query->where('id_brand',$post['id_brand']);
        },'product_detail'=>function($query) use ($post){
            $query->select('id_product','id_outlet','product_detail_status','product_detail_visibility','max_order');
            $query->where('id_outlet',$post['id_outlet']);
        }])
        ->first();
        if(!$product){
            return MyHelper::checkGet([]);
        }else{
            // toArray error jika $product Null,
            $product = $product->append('photo')->toArray();
            unset($product['photos']);
        }
        $max_order = $product['product_detail'][0]['max_order'];
        if($max_order==null){
            $max_order = Outlet::select('max_order')->where('id_outlet',$post['id_outlet'])->pluck('max_order')->first();
            if($max_order == null){
                $max_order = Setting::select('value')->where('key','max_order')->pluck('value')->first();
                if($max_order == null){
                    $max_order = 100;
                }
            }
        }

        if(isset($product['product_detail']['product_detail_visibility']) && !(empty($product['product_detail']['product_detail_visibility'])&&$product['product_detail_visibility']=='Visible') && ($product['product_detail'][0]['product_detail_visibility']??false)!='Visible'){
            return MyHelper::checkGet([]);
        }
        unset($product['product_detail']);
        $post['id_product_category'] = $product['brand_category'][0]['id_product_category']??0;
        if($post['id_product_category'] === 0){
            return MyHelper::checkGet([]);
        }
        //get modifiers
        $product_modifiers = ProductModifier::select('product_modifiers.id_product_modifier','code','text','product_modifier_stock_status','product_modifier_price as price')
            ->where(function($query) use($post){
                $query->where('modifier_type','Global')
                ->orWhere(function($query) use ($post){
                    $query->whereHas('products',function($query) use ($post){
                        $query->where('products.id_product',$post['id_product']);
                    });
                    $query->orWhereHas('product_categories',function($query) use ($post){
                        $query->where('product_categories.id_product_category',$post['id_product_category']);
                    });
                    $query->orWhereHas('brands',function($query) use ($post){
                        $query->where('brands.id_brand',$post['id_brand']);
                    });
                });
            })
            ->leftJoin('product_modifier_details', function($join) use ($post) {
                $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet',$post['id_outlet']);
            })
            ->where(function($q){
                $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
            })
            ->where(function($q){
                $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
            })
            ->where(function($query){
                $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            });

        $product['product_price'] = 0;
        if($outlet->outlet_different_price){
            $product_modifiers->join('product_modifier_prices',function($join) use ($post){
                $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
            });

            $productSpecialPrice = ProductSpecialPrice::where('id_product',$post['id_product'])
                ->where('id_outlet',$post['id_outlet'])->first();
            if($productSpecialPrice){
                $product['product_price'] = $productSpecialPrice['product_special_price'];
            }
        }else{
            $product_modifiers->join('product_modifier_global_prices',function($join) use ($post){
                $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
            });

            $productGlobalPrice = ProductGlobalPrice::where('id_product',$post['id_product'])->first();
            if($productGlobalPrice){
                $product['product_price'] = $productGlobalPrice['product_global_price'];
            }
        }
        $product['modifiers'] = $product_modifiers->get()->toArray();
        foreach ($product['modifiers'] as $key => &$modifier) {
            $modifier['price'] = (int) $modifier['price'];
            unset($modifier['product_modifier_prices']);
        }
        $product['max_order'] = (int) $max_order;
        $product['max_order_alert'] = MyHelper::simpleReplace(Setting::select('value_text')->where('key','transaction_exceeds_limit_text')->pluck('value_text')->first()?:'Transaksi anda melebihi batas! Maksimal transaksi untuk %product_name% : %max_order%',
                    [
                        'product_name' => $product['product_name'],
                        'max_order' => $max_order
                    ]
                );
        $product['outlet'] = Outlet::select('id_outlet','outlet_code','outlet_address','outlet_name')->find($post['id_outlet']);

        return MyHelper::checkGet($product);
    }

    public function ajaxProductBrand(Request $request)
    {
    	$post=$request->except('_token');
        $q= (new Product)->newQuery();
        if($post['select']??false){
            $q->select($post['select']);
        }

        if($condition=$post['condition']??false){
            $this->filterList($q,$condition['rules']??'',$condition['operator']??'and');
        }
        return MyHelper::checkGet($q->get());
    }

    public function filterList($model,$rule,$operator='and'){
        $newRule=[];
        $where=$operator=='and'?'where':'orWhere';
        foreach ($rule as $var) {
            $var1=['operator'=>$var['operator']??'=','parameter'=>$var['parameter']??null];
            if($var1['operator']=='like'){
                $var1['parameter']='%'.$var1['parameter'].'%';
            }
            $newRule[$var['subject']][]=$var1;
        }

        if($rules=$newRule['id_brand']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('brands',function($query) use ($rul){
                    $query->where('brands.id_brand',$rul['operator'],$rul['parameter']);
                });
            }
        }
    }
}
