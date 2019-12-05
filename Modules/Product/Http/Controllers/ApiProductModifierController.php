<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierBrand;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierProduct;
use App\Http\Models\ProductModifierProductCategory;

use Modules\Product\Http\Requests\Modifier\CreateRequest;
use Modules\Product\Http\Requests\Modifier\UpdateRequest;

use App\Lib\MyHelper;
use DB;

class ApiProductModifierController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        if($request->page){
            $modifiers = ProductModifier::paginate(10);
        }else{
            $modifiers = ProductModifier::get();
        }
        return MyHelper::checkGet($modifiers);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateRequest $request)
    {
        $post = $request->json()->all();
        $data = [
            'modifier_type'=>$post['modifier_type'],
            'type'=>$post['type'],
            'code'=>$post['code'],
            'text'=>$post['text'],
        ];
        DB::beginTransaction();
        $createe = ProductModifier::create($data);
        if(!$createe){
            DB::rollback();
            return [
                'status'=>'fail',
                'messages'=>['Failed create product modifier']
            ];
        }
        if($post['modifier_type']=='Specific'){
            $id_product_modifier = $createe->id_product_modifier;
            if($brands = ($post['id_brand']??false)){
                foreach ($brands as $id_brand) {
                    $data = [
                        'id_brand' => $id_brand,
                        'id_product_modifier' => $id_product_modifier
                    ];
                    $create = ProductModifierBrand::create($data);
                    if(!$create){
                        DB::rollback();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed assign id brand to product modifier']
                        ];
                    }
                }
            }
            if($products = ($post['id_product']??false)){
                foreach ($products as $id_product) {
                    $data = [
                        'id_product' => $id_product,
                        'id_product_modifier' => $id_product_modifier
                    ];
                    $create = ProductModifierProduct::create($data);
                    if(!$create){
                        DB::rollback();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed assign id brand to product modifier']
                        ];
                    }
                }
            }
            if($product_categories = ($post['id_product_category']??false)){
                foreach ($product_categories as $id_product_category) {
                    $data = [
                        'id_product_category' => $id_product_category,
                        'id_product_modifier' => $id_product_modifier
                    ];
                    $create = ProductModifierProductCategory::create($data);
                    if(!$create){
                        DB::rollback();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed assign id brand to product modifier']
                        ];
                    }
                }
            }
        }
        DB::commit();
        return MyHelper::checkCreate($createe);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $result = ProductModifier::with(['products','product_categories','brands'])->find($request->json('id_product_modifier'));
        return MyHelper::checkGet($result);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(UpdateRequest $request)
    {
        $post = $request->json()->all();
        $id_product_modifier =$post['id_product_modifier'];
        $product_modifier = ProductModifier::find($id_product_modifier);
        if(!$product_modifier){
            return [
                'status'=>'fail',
                'messages'=>['product modifier not found']
            ];
        }
        DB::beginTransaction();
        // delete relationship
        ProductModifierBrand::where('id_product_modifier',$id_product_modifier)->delete();
        ProductModifierProduct::where('id_product_modifier',$id_product_modifier)->delete();
        ProductModifierProductCategory::where('id_product_modifier',$id_product_modifier)->delete();
        $data = [
            'modifier_type'=>$post['modifier_type'],
            'type'=>$post['type'],
            'code'=>$post['code'],
            'text'=>$post['text'],
        ];
        $update = $product_modifier->update($data);
        if(!$update){
            DB::rollback();
            return MyHelper::checkUpdate($update);
        }
        if($post['modifier_type']=='Specific'){
            if($brands = ($post['id_brand']??false)){
                foreach ($brands as $id_brand) {
                    $data = [
                        'id_brand' => $id_brand,
                        'id_product_modifier' => $id_product_modifier
                    ];
                    $create = ProductModifierBrand::create($data);
                    if(!$create){
                        DB::rollback();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed assign id brand to product modifier']
                        ];
                    }
                }
            }
            if($products = ($post['id_product']??false)){
                foreach ($products as $id_product) {
                    $data = [
                        'id_product' => $id_product,
                        'id_product_modifier' => $id_product_modifier
                    ];
                    $create = ProductModifierProduct::create($data);
                    if(!$create){
                        DB::rollback();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed assign id brand to product modifier']
                        ];
                    }
                }
            }
            if($product_categories = ($post['id_product_category']??false)){
                foreach ($product_categories as $id_product_category) {
                    $data = [
                        'id_product_category' => $id_product_category,
                        'id_product_modifier' => $id_product_modifier
                    ];
                    $create = ProductModifierProductCategory::create($data);
                    if(!$create){
                        DB::rollback();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed assign id brand to product modifier']
                        ];
                    }
                }
            }
        }
        DB::commit();
        return MyHelper::checkCreate($update);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request) {
        $id_product_modifier = $request->json('id_product_modifier');
        $delete = ProductModifier::where('id_product_modifier',$id_product_modifier)->delete();
        return MyHelper::checkDelete($delete);
    }
}
