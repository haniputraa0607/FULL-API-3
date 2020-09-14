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
                    $id = $store['product_variant_id'];
                    foreach ($data_request['child'] as $key=>$child){
                        $parent_id = NULL;

                        if($child['parent'] == 0){
                            $parent_id = $id;
                        }elseif(isset($data_request['child'][(int)$child['parent']]['id'])){
                            $parent_id = $data_request['child'][(int)$child['parent']]['id'];
                        }

                        $visible = 'Hidden';
                        if(isset($child['product_variant_visibility'])){
                            $visible = 'Visible';
                        }

                        $store = ProductVariant::create([
                            'product_variant_name' => $child['product_variant_name'],
                            'product_variant_visibility' => $visible,
                            'parent_id' => $parent_id]);

                        if($store){
                            $data_request['child'][$key]['id'] = $store['product_variant_id'];
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

        if(isset($post['product_variant_id']) && !empty($post['product_variant_id'])){
            $get_all_parent = ProductVariant::where(function ($q){
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })->get()->toArray();

            $product_variant = ProductVariant::where('product_variant_id', $post['product_variant_id'])->with(['product_variant_parent', 'product_variant_child'])->first();

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

        if(isset($post['product_variant_id']) && !empty($post['product_variant_id'])){
            DB::beginTransaction();
            if(isset($post['product_variant_name'])){
                $data_update['product_variant_name'] = $post['product_variant_name'];
            }

            if(isset($post['parent_id'])){
                $data_update['parent_id'] = $post['parent_id'];
            }

            if(isset($post['product_variant_visibility'])){
                $data_update['product_variant_visibility'] = $post['product_variant_visibility'];
            }

            $update = ProductVariant::where('product_variant_id', $post['product_variant_id'])->update($data_update);

            if($update){
                if(isset($post['child']) && !empty($post['child'])){
                    foreach ($post['child'] as $child){
                        $data_update_child['parent_id'] = $post['product_variant_id'];
                        if(isset($child['product_variant_name'])){
                            $data_update_child['product_variant_name'] = $child['product_variant_name'];
                        }

                        if(isset($child['product_variant_visibility'])){
                            $data_update_child['product_variant_visibility'] = 'Visible';
                        }else{
                            $data_update_child['product_variant_visibility'] = 'Hidden';
                        }

                        $update = ProductVariant::updateOrCreate(['product_variant_id' => $child['product_variant_id']], $data_update_child);

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
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })->with('product_variant_child')->get()->toArray();
            return MyHelper::checkGet($data);
        }else{
            foreach ($request->position as $position => $product_variant_id) {
                ProductVariant::where('product_variant_id', $product_variant_id)->update(['product_variant_order' => $position]);
            }
            return MyHelper::checkUpdate(true);
        }
    }
}
