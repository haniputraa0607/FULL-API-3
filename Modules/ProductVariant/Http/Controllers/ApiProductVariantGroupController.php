<?php

namespace Modules\ProductVariant\Http\Controllers;

use App\Http\Models\Outlet;
use App\Jobs\RefreshVariantTree;
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
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductGlobalPrice;

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
                                'product_variant_group_code' => $dt['code']]);

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
                                'product_variant_group_price' => str_replace(".","",$dt['price'])
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
                    $basePrice = ProductVariantGroup::orderBy('product_variant_group_price', 'asc')->where('id_product', $id_product['id_product'])->first();
                    $getAllOutlets = Outlet::get();
                    foreach ($getAllOutlets as $o){
                        Product::refreshVariantTree($id_product['id_product'], $o);
                        if($o['outlet_different_price'] == 1){
                            $basePriceDiferrentOutlet = ProductVariantGroup::join('product_variant_group_special_prices as pgsp', 'pgsp.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                                ->orderBy('product_variant_group_price', 'asc')
                                ->select(DB::raw('(CASE
                                        WHEN pgsp.product_variant_group_price is NOT NULL THEN pgsp.product_variant_group_price
                                        ELSE product_variant_groups.product_variant_group_price END)  as product_variant_group_price'))
                                ->where('id_product', $id_product['id_product'])->where('id_outlet', $o['id_outlet'])->first();
                            if($basePriceDiferrentOutlet){
                                ProductSpecialPrice::updateOrCreate(['id_outlet' => $o['id_outlet'], 'id_product' => $id_product['id_product']], ['product_special_price' => $basePriceDiferrentOutlet['product_variant_group_price']]);
                            }else{
                                ProductSpecialPrice::updateOrCreate(['id_outlet' => $o['id_outlet'], 'id_product' => $id_product['id_product']], ['product_special_price' => $basePrice['product_variant_group_price']]);
                            }
                        }
                    }
                    ProductGlobalPrice::updateOrCreate(['id_product' => $id_product['id_product']], ['product_global_price' => $basePrice['product_variant_group_price']]);
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
        //update all product
        RefreshVariantTree::dispatch([])->allOnConnection('database');
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
            $key = [
                'id_product_variant_group' => $id_product_modifier,
                'id_outlet'           => $id_outlet,
            ];
            $insertData = $key + [
                    'product_variant_group_stock_status' => $detail['product_variant_group_stock_status']
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
        RefreshVariantTree::dispatch([])->allOnConnection('database');
        return ['status' => 'success'];
    }

    public function export(Request $request){
        $data = Product::with(['product_variant_group'])->get()->toArray();
        $parent = ProductVariant::whereNull('id_parent')->with(['product_variant_child'])->get()->toArray();

        $arr = [];
        foreach ($data as $key=>$dt){
            $arr[$key] = [
                'product_name' => $dt['product_name'],
                'product_code' => $dt['product_code'],
                'use_product_variant_status' => ($dt['product_variant_status'] == 1 ? 'YES' : 'NO')
            ];

            foreach ($parent as $p){
                $name = '';
                if(!empty($p['product_variant_child'])){
                    $child = array_column($p['product_variant_child'], 'product_variant_name');
                    $name = '('.implode(',',$child).')';
                }
                $variant = [];
                foreach ($dt['product_variant_group'] as $pg){
                    if($pg['id_parent'] == $p['id_product_variant'] && array_search($pg['product_variant_name'],$variant) === false){
                        $variant[] = $pg['product_variant_name'];
                    }
                }
                $arr[$key][$p['product_variant_name'].' '.$name] = implode(',', $variant);
            }
        }
        return response()->json(MyHelper::checkGet($arr));
    }

    public function import(Request $request){
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'create' => 0,
            'no_update' => 0,
            'invalid' => 0,
            'failed' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0]??[];

        $getAllVariant = ProductVariant::whereNotNull('id_parent')->get()->toArray();
        foreach ($data as $key => $value) {
            if(empty($value['product_code'])){
                $result['invalid']++;
                continue;
            }

            if(empty($value['use_product_variant_status'])){
                $result['invalid']++;
                continue;
            }
            $arrVariantGroup = [];
            $products = Product::where('product_code', $value['product_code'])->first();
            $update = Product::where('product_code', $value['product_code'])->update(['product_variant_status' => (strtolower($value['use_product_variant_status']) == 'yes' ? 1 : 0)]);
            if(strtolower($value['use_product_variant_status']) == 'yes'){
                unset($value['product_code']);
                unset($value['product_name']);
                unset($value['use_product_variant_status']);
                $newArr = [];
                foreach ($value as $new){
                    $explode = explode(",",$new);
                    $newArr[] = $explode;
                }
                $arrCombinations = $this->combinations($newArr);

                if($arrCombinations){
                    foreach ($arrCombinations as $group){
                        //search id product variant for insert into product variant pivot
                        $arrTmp = [];
                        foreach ($group as $g){
                            $searchId = array_search($g, array_column($getAllVariant, 'product_variant_name'));
                            if($searchId !== false){
                                $arrTmp[] = $getAllVariant[$searchId]['id_product_variant'];
                            }
                        }

                        if($arrTmp){
                            $checkExisting = ProductVariantPivot::join('product_variant_groups as pvg', 'pvg.id_product_variant_group', 'product_variant_pivot.id_product_variant_group')
                                            ->whereIn('product_variant_pivot.id_product_variant', $arrTmp)->where('pvg.id_product', $products['id_product'])
                                            ->groupBy('product_variant_pivot.id_product_variant_group')->havingRaw('COUNT(product_variant_pivot.`id_product_variant`) = '.count($arrTmp))->first();

                            if($checkExisting){
                                $result['updated']++;
                                $dt_insert = [];
                                $delete = ProductVariantPivot::where('id_product_variant_group', $checkExisting['id_product_variant_group'])->delete();
                                if($delete){
                                    foreach ($arrTmp as $val){
                                        $dt_insert[] = [
                                            'id_product_variant_group' => $checkExisting['id_product_variant_group'],
                                            'id_product_variant' => $val
                                        ];
                                    }
                                    ProductVariantPivot::insert($dt_insert);
                                }else{
                                    $result['no_update']++;
                                }
                            }else{
                                $result['create']++;
                                $create = ProductVariantGroup::create(
                                    [
                                        'id_product' => $products['id_product'],
                                        'product_variant_group_code' =>'GENERATEBYSYSTEM_'.$products['product_code'].'_'.implode('',$arrTmp),
                                        'product_variant_group_price' => 0,
                                        'product_variant_group_visibility' => 'Visible'
                                    ]
                                );
                                if($create){
                                    $dt_insert = [];
                                    foreach ($arrTmp as $val){
                                        $dt_insert[] = [
                                            'id_product_variant_group' => $create['id_product_variant_group'],
                                            'id_product_variant' => $val
                                        ];
                                    }
                                    ProductVariantPivot::insert($dt_insert);
                                }
                            }
                        }
                    }
                }
            }
        }
        //update all product
        RefreshVariantTree::dispatch([])->allOnConnection('database');

        $response = [];

        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' product variant group';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new product variant group';
        }
        if($result['no_update']){
            $response[] = $result['no_update'].' product variant group not updated';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' product variant group';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    function combinations($arrays, $i = 0) {
        if (!isset($arrays[$i])) {
            return array();
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }

        // get combinations from subsequent arrays
        $tmp = $this->combinations($arrays, $i + 1);

        $result = array();

        // concat each array from tmp with each element from $arrays[$i]
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ?
                    array_merge(array($v), $t) :
                    array($v, $t);
            }
        }

        return $result;
    }

    public function exportPrice(Request $request){
        $different_outlet = Outlet::where('outlet_different_price',1)->get()->toArray();
        $different_outlet_price = Outlet::select('outlet_code','id_product_variant_group','product_variant_group_price')
            ->leftJoin('product_variant_group_special_prices','outlets.id_outlet','=','product_variant_group_special_prices.id_outlet')
            ->where('outlet_different_price',1)->get()->toArray();
        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->select('product_variant_groups.product_variant_group_price as global_price', 'products.product_name', 'products.product_code', 'product_variant_groups.product_variant_group_code', 'product_variant_groups.id_product_variant_group')
            ->with(['product_variant_pivot'])->get()->toArray();

        $arrProductVariant = [];
        foreach ($data as $key => $pv) {
            $arr = array_column($pv['product_variant_pivot'], 'product_variant_name');
            $name = implode(',',$arr);
            $arrProductVariant[$key] = [
                'product' => $pv['product_code'].' - '.$pv['product_name'],
                'current_product_variant_code' => $pv['product_variant_group_code'],
                'new_product_variant_code' => '',
                'product_variant_group' => $name,
                'global_price' => $pv['global_price']
            ];

            foreach ($different_outlet as $o){
                $arrProductVariant[$key]['price_'.$o['outlet_code']] = 0;
                foreach ($different_outlet_price as $key_o => $o_price){
                    if($o_price['id_product_variant_group'] == $pv['id_product_variant_group'] && $o_price['outlet_code'] == $o['outlet_code']){
                        $arrProductVariant[$key]['price_'.$o['outlet_code']] = $o_price['product_variant_group_price'];
                        unset($key_o);
                    }else{
                        continue;
                    }
                }
            }
        }

        return response()->json(MyHelper::checkGet($arrProductVariant));
    }

    public function importPrice(Request $request){
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'create' => 0,
            'no_update' => 0,
            'invalid' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0]??[];

        foreach ($data as $key => $value) {
            if(empty($value['current_product_variant_code'])){
                $result['invalid']++;
                continue;
            }
            if(empty($value['product'])){
                unset($value['product']);
            }
            if(empty($value['product_variant'])){
                unset($value['product_variant']);
            }

            $productVariantGroup = ProductVariantGroup::where([
                    'product_variant_group_code' => $value['current_product_variant_code']
                ])->first();
            if(!$productVariantGroup){
                $result['not_found']++;
                $result['more_msg_extended'][] = "Product variant with code {$value['current_product_variant_code']} not found";
                continue;
            }

            $datUpdate = ['product_variant_group_price'=>$value['global_price']];
            if(!empty($value['new_product_variant_code'])){
                $datUpdate['product_variant_group_code'] = $value['new_product_variant_code'];
            }
            $update1 = ProductVariantGroup::where([
                'id_product_variant_group' => $productVariantGroup['id_product_variant_group']
            ])->update($datUpdate);

            if($update1){
                $result['updated']++;
            }else{
                $result['no_update']++;
            }

            foreach ($value as $col_name => $col_value) {
                if(!$col_value){
                    $col_value = 0;
                }
                if(strpos($col_name, 'price_') !== false){
                    $outlet_code = str_replace('price_', '', $col_name);
                    $pp = ProductVariantGroupSpecialPrice::join('outlets','outlets.id_outlet','=','product_variant_group_special_prices.id_outlet')
                        ->where([
                            'outlet_code' => $outlet_code,
                            'id_product_variant_group' => $productVariantGroup['id_product_variant_group']
                        ])->first();
                    if($pp){
                        $update = $pp->update(['product_variant_group_price'=>$col_value]);
                    }else{
                        $id_outlet = Outlet::select('id_outlet')->where('outlet_code',$outlet_code)->pluck('id_outlet')->first();
                        if(!$id_outlet){
                            $result['updated_price_fail']++;
                            $result['more_msg_extended'][] = "Failed create new price for product variant group {$value['product_variant_code']} at outlet $outlet_code failed";
                            continue;
                        }
                        $update = ProductVariantGroupSpecialPrice::create([
                            'id_outlet' => $id_outlet,
                            'id_product_variant_group' => $productVariantGroup['id_product_variant_group'],
                            'product_variant_group_price'=>$col_value
                        ]);
                    }
                    if($update){
                        $result['updated']++;
                    }else{
                        $result['no_update']++;
                        $result['more_msg_extended'][] = "Failed set price for product variant group {$value['product_variant_code']} at outlet $outlet_code failed";
                    }
                }
            }
        }

        //update all product
        RefreshVariantTree::dispatch([])->allOnConnection('database');

        $response = [];

        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' product variant group price';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new product variant group';
        }
        if($result['no_update']){
            $response[] = $result['no_update'].' product variant group not updated';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' product variant group';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function deleteProductVariantGroup(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_product_variant_group']) && !empty($post['id_product_variant_group'])){
            $delete = ProductVariantPivot::where('id_product_variant_group',$post['id_product_variant_group'])->delete();
            if($delete){
                $delete = ProductVariantGroup::where('id_product_variant_group',$post['id_product_variant_group'])->delete();
            }
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listProductWithVariant(Request $request){
        $data = Product::select('products.*', DB::raw('(Select COUNT(pvg.id_product_variant_group) from product_variant_groups pvg where pvg.id_product = products.id_product) as count_product_variant_group'));

        if ($keyword = ($request->search['value']??false)) {
            $data->where('product_code', 'like', '%'.$keyword.'%')
                ->orWhere('product_name', 'like', '%'.$keyword.'%');
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }
}
