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

class ApiProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        $product_variant = ProductVariant::with(['product_variant_parent', 'product_variant_child']);

        if ($keyword = ($request->search['value']??false)) {
            $product_variant->where('product_variant_name', 'like', '%'.$keyword.'%')
                        ->orWhereHas('product_variant_parent', function($q) use ($keyword) {
                                $q->where('product_variant_name', 'like', '%'.$keyword.'%');
                            })
                        ->orWhereHas('product_variant_child', function($q) use ($keyword) {
                            $q->where('product_variant_name', 'like', '%'.$keyword.'%');
                        });
        }

        if(isset($post['get_child']) && $post['get_child'] == 1){
            $product_variant = $product_variant->whereNotNull('id_parent');
        }

        if(isset($post['page'])){
            $product_variant = $product_variant->paginate($request->length?:10);
        }else{
            $product_variant = $product_variant->get()->toArray();
        }

        return MyHelper::checkGet($product_variant);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->all();
        if(isset($post['data']) && !empty($post['data'])){
            DB::beginTransaction();
            $data_request = $post['data'];

            $visible = 'Hidden';
            if(isset($data_request[0]['product_variant_visibility'])){
                $visible = 'Visible';
            }
            $store = ProductVariant::create([
                            'product_variant_name' => $data_request[0]['product_variant_name'],
                            'product_variant_visibility' => $visible]);

            if($store){
                if(isset($data_request['child'])){
                    $id = $store['id_product_variant'];
                    foreach ($data_request['child'] as $key=>$child){
                        $id_parent = NULL;

                        if($child['parent'] == 0){
                            $id_parent = $id;
                        }elseif(isset($data_request['child'][(int)$child['parent']]['id'])){
                            $id_parent = $data_request['child'][(int)$child['parent']]['id'];
                        }

                        $visible = 'Hidden';
                        if(isset($child['product_variant_visibility'])){
                            $visible = 'Visible';
                        }

                        $store = ProductVariant::create([
                            'product_variant_name' => $child['product_variant_name'],
                            'product_variant_visibility' => $visible,
                            'id_parent' => $id_parent]);

                        if($store){
                            $data_request['child'][$key]['id'] = $store['id_product_variant'];
                        }else{
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add product variant']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add product variant']]);
            }

            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit(Request $request)
    {
        $post = $request->all();

        if(isset($post['id_product_variant']) && !empty($post['id_product_variant'])){
            $get_all_parent = ProductVariant::where(function ($q){
                $q->whereNull('id_parent')->orWhere('id_parent', 0);
            })->get()->toArray();

            $product_variant = ProductVariant::where('id_product_variant', $post['id_product_variant'])->with(['product_variant_parent', 'product_variant_child'])->first();

            return response()->json(['status' => 'success', 'result' => [
                'all_parent' => $get_all_parent,
                'product_variant' => $product_variant
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->all();

        if(isset($post['id_product_variant']) && !empty($post['id_product_variant'])){
            DB::beginTransaction();
            if(isset($post['product_variant_name'])){
                $data_update['product_variant_name'] = $post['product_variant_name'];
            }

            if(isset($post['id_parent'])){
                $data_update['id_parent'] = $post['id_parent'];
            }

            if(isset($post['product_variant_visibility'])){
                $data_update['product_variant_visibility'] = $post['product_variant_visibility'];
            }

            $update = ProductVariant::where('id_product_variant', $post['id_product_variant'])->update($data_update);

            if($update){
                if(isset($post['child']) && !empty($post['child'])){
                    foreach ($post['child'] as $child){
                        $data_update_child['id_parent'] = $post['id_product_variant'];
                        if(isset($child['product_variant_name'])){
                            $data_update_child['product_variant_name'] = $child['product_variant_name'];
                        }

                        if(isset($child['product_variant_visibility'])){
                            $data_update_child['product_variant_visibility'] = 'Visible';
                        }else{
                            $data_update_child['product_variant_visibility'] = 'Hidden';
                        }

                        $update = ProductVariant::updateOrCreate(['id_product_variant' => $child['id_product_variant']], $data_update_child);

                        if(!$update){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed update child product variant']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update product variant']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $id_product_variant = $request->json('id_product_variant');
        $delete              = ProductVariant::where('id_product_variant', $id_product_variant)->delete();

        if($delete){
            $delete = $this->deleteChild($id_product_variant);
        }

        return MyHelper::checkDelete($delete);
    }

    public function deleteChild($id_parent){
        $get = ProductVariant::where('id_parent', $id_parent)->first();
        if($get){
            $delete  = ProductVariant::where('id_parent', $id_parent)->delete();
            $this->deleteChild($get['id_product_variant']);
            return $delete;
        }else{
            return true;
        }
    }

    public function position(Request $request){
        $post = $request->all();

        if(empty($post)){
            $data = ProductVariant::orderBy('product_variant_order', 'asc')->where(function ($q){
                $q->whereNull('id_parent')->orWhere('id_parent', 0);
            })->with('product_variant_child')->get()->toArray();
            return MyHelper::checkGet($data);
        }else{
            foreach ($request->position as $position => $id_product_variant) {
                ProductVariant::where('id_product_variant', $id_product_variant)->update(['product_variant_order' => $position]);
            }
            return MyHelper::checkUpdate(true);
        }
    }

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
}
