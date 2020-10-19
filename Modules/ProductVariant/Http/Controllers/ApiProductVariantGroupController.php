<?php

namespace Modules\ProductVariant\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Product;
use Modules\ProductVariant\Entities\ProductVariant;
use DB;
use Illuminate\Support\Facades\Log;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\ProductVariant\Entities\ProductVariantPivot;

class ApiProductVariantGroupController extends Controller
{
    public function productVariantGroup(Request $request){
        $post = $request->all();
        if(isset($request->product_code) && !empty($request->product_code)){

            if(isset($post['data']) && !empty($post['data'])){
                $process = false;
                $id_product = Product::where('product_code', $request->product_code)->first();
                foreach ($post['data'] as $dt){
                    if(!empty($dt['group_id'])){
                        $update = ProductVariantGroup::where('id_product_variant_group', $dt['group_id'])
                            ->update(['product_variant_group_price' => str_replace(".","",$dt['price']),
                                'product_variant_group_code' => $dt['code'],
                                'product_variant_group_visibility' => $dt['visibility']]);

                        if($update){
                            $del = ProductVariantPivot::where('id_product_variant_group', $dt['group_id'])->delete();
                            if($del){
                                $explode = explode(',', $dt['id']);
                                $dt_insert = [];
                                foreach ($explode as $val){
                                    $dt_insert[] = [
                                        'id_product_variant_group' => $dt['group_id'],
                                        'id_product_variant' => $val
                                    ];
                                }
                                $process = ProductVariantPivot::insert($dt_insert);
                            }
                        }

                    }else{
                        $create = ProductVariantGroup::create(
                            [
                                'id_product' => $id_product['id_product'],
                                'product_variant_group_code' =>$dt['code'],
                                'product_variant_group_price' => str_replace(".","",$dt['price']),
                                'product_variant_group_visibility' => $dt['visibility']
                            ]
                        );
                        if($create){
                            $explode = explode(',', $dt['id']);
                            $dt_insert = [];
                            foreach ($explode as $val){
                                $dt_insert[] = [
                                    'id_product_variant_group' => $create['id_product_variant_group'],
                                    'id_product_variant' => $val
                                ];
                            }
                            $process = ProductVariantPivot::insert($dt_insert);
                        }
                    }
                }


                if(isset($post['data_to_delete']) && !empty($post['data_to_delete'])){
                    ProductVariantPivot::whereIn('id_product_variant_group',$post['data_to_delete'])->delete();
                    ProductVariantGroup::whereIn('id_product_variant_group',$post['data_to_delete'])->delete();
                }

                if($process){
                    $updt = ['product_variant_status' => 1];
                    Product::where('product_code', $request->product_code)->update($updt);
                }

                if($process){
                    Product::refreshVariantTree($id_product['id_product'], true);
                    Product::refreshVariantTree($id_product['id_product'], false);
                }

                return response()->json(MyHelper::checkCreate($process));
            }else {
                $get = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
                    ->where('products.product_code', $post['product_code'])
                    ->with(['product_variant_pivot'])
                    ->get()->toArray();
                return response()->json(MyHelper::checkGet($get));
            }
        }else{
            return response()->json(['status' => 'fail', 'messages'=> ['Incompleted Data']]);
        }
    }

    public function listPrice(Request $request){
        $post = $request->all();

        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->with(['product_variant_pivot']);

        if(isset($post['id_outlet']) && !empty($post['id_outlet'])){
            $data = $data->leftJoin('product_variant_group_special_prices as pvgsp', function ($join) use ($post) {
                $join->on('pvgsp.id_product_variant_group', 'product_variant_groups.id_product_variant_group');
                $join->where('pvgsp.id_outlet', '=', $post['id_outlet']);
            })
                ->where(function ($query) use ($post) {
                    $query->where('pvgsp.id_outlet', $post['id_outlet']);
                    $query->orWhereNull('pvgsp.id_outlet');
                })
                ->select('pvgsp.*', 'products.product_name', 'products.product_code', 'product_variant_groups.product_variant_group_code', 'product_variant_groups.id_product_variant_group');
        }else{
            $data = $data->select('product_variant_groups.*', 'products.product_name', 'products.product_code');
        }

        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }

            if($rule == 'and'){
                foreach ($post['rule'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'product_variant_group_code'){
                            $data->where('product_variant_groups.product_variant_group_code', $row['parameter']);
                        }

                        if($row['subject'] == 'product_variant_group_visibility'){
                            $data->where('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['rule'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'product_variant_group_code'){
                                $subquery->orWhere('product_variant_groups.product_variant_group_code', $row['parameter']);
                            }

                            if($row['subject'] == 'product_variant_group_visibility'){
                                $subquery->orWhere('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }

    public function updatePrice(Request $request)
    {
        $id_outlet  = $request->json('id_outlet');
        $insertData = [];
        DB::beginTransaction();
        if (empty($id_outlet)) {
            foreach ($request->json('prices') as $id_product_variant_group => $price) {
                if (!is_numeric($price['product_variant_group_price'])) {
                    continue;
                }
                $insert = ProductVariantGroup::where('id_product_variant_group', $id_product_variant_group)->update(['product_variant_group_price' => $price['product_variant_group_price']]);
                if (!$insert) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Update price fail'],
                    ];
                }
            }
        } else {
            foreach ($request->json('prices') as $id_product_variant_group => $price) {
                if (!($price['product_variant_group_price'] ?? false)) {
                    continue;
                }
                $key = [
                    'id_product_variant_group' => $id_product_variant_group,
                    'id_outlet'           => $id_outlet,
                ];
                $insertData = [
                    'id_product_variant_group' => $id_product_variant_group,
                    'id_outlet'           => $id_outlet,
                    'product_variant_group_price' => $price['product_variant_group_price'],
                ];

                $insert = ProductVariantGroupSpecialPrice::updateOrCreate($key, $insertData);
                if (!$insert) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Update price fail'],
                    ];
                }
            }

        }
        DB::commit();
        return ['status' => 'success'];
    }

    public function listDetail(Request $request)
    {
        $post      = $request->json()->all();
        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->with(['product_variant_pivot']);

        if(isset($post['id_outlet']) && !empty($post['id_outlet'])){
            $data = $data->leftJoin('product_variant_group_details as pvgd', function ($join) use ($post) {
                $join->on('pvgd.id_product_variant_group', 'product_variant_groups.id_product_variant_group');
                $join->where('pvgd.id_outlet', '=', $post['id_outlet']);
            })
                ->where(function ($query) use ($post) {
                    $query->where('pvgd.id_outlet', $post['id_outlet']);
                    $query->orWhereNull('pvgd.id_outlet');
                })
                ->select('pvgd.*', 'products.product_name', 'products.product_code', 'product_variant_groups.product_variant_group_code', 'product_variant_groups.id_product_variant_group');
        }else{
            $data = $data->select('product_variant_groups.*', 'products.product_name', 'products.product_code');
        }

        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }

            if($rule == 'and'){
                foreach ($post['rule'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'product_variant_group_code'){
                            $data->where('product_variant_groups.product_variant_group_code', $row['parameter']);
                        }

                        if($row['subject'] == 'product_variant_group_visibility'){
                            $data->where('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['rule'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'product_variant_group_code'){
                                $subquery->orWhere('product_variant_groups.product_variant_group_code', $row['parameter']);
                            }

                            if($row['subject'] == 'product_variant_group_visibility'){
                                $subquery->orWhere('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }

    public function updateDetail(Request $request)
    {
        $id_outlet  = $request->json('id_outlet');
        $insertData = [];
        DB::beginTransaction();
        foreach ($request->json('detail') as $id_product_modifier => $detail) {
            if (!($detail['product_variant_group_visibility'] ?? false) && !($detail['product_variant_group_visibility'] ?? false)) {
                continue;
            }
            $key = [
                'id_product_variant_group' => $id_product_modifier,
                'id_outlet'           => $id_outlet,
            ];
            $insertData = $key + [
                    'product_variant_group_visibility'   => $detail['product_variant_group_visibility'],
                    'product_variant_group_stock_status' => $detail['product_variant_group_stock_status'],
                ];
            $insert = ProductVariantGroupDetail::updateOrCreate($key, $insertData);
            if (!$insert) {
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Update detail fail'],
                ];
            }
        }
        DB::commit();
        return ['status' => 'success'];
    }

    public function export(Request $request){
        $post = $request->json()->all();
        $productVariant = ProductVariant::whereNull('id_parent')->get()->toArray();
        $products = Product::get()->toArray();

        $arr = [];
        $i = 0;
        foreach ($products as $product){
            $arr[] = [
                'product_name' => $product['product_name'],
                'product_code' => $product['product_code'],
                'use_variant_status' => ($product['product_variant_status'] == 1  ? 'YES' : 'NO')
            ];

            foreach ($productVariant as $pv){
                $arr[$i][$pv['product_variant_name']] = [];
            }
            $i++;
        }
    }
}
