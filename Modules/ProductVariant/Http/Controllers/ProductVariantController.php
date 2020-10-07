<?php

namespace Modules\ProductVariant\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\ProductVariant\Entities\ProductVariant;
use DB;
use Illuminate\Support\Facades\Log;

class ProductVariantController extends Controller
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

        if(isset($post['page'])){
            $product_variant = $product_variant->paginate($request->length?:10);
        }else{
            $product_variant = $product_variant->get()->toArray();
        }

        return MyHelper::checkGet($product_variant);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('productvariant::create');
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
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('productvariant::show');
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
    public function destroy($id)
    {

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
}
