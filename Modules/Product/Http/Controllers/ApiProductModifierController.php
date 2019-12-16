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
use Modules\Brand\Entities\BrandOutlet;

use Modules\Product\Http\Requests\Modifier\CreateRequest;
use Modules\Product\Http\Requests\Modifier\ShowRequest;
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
            'product_modifier_visibility'=>($post['product_modifier_visibility']??false)?'Visible':'Hidden',
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
    public function show(ShowRequest $request)
    {
        if($request->json('id_product_modifier')){
            $col = 'id_product_modifier';
            $val = $request->json('id_product_modifier');
        }else{
            $col = 'code';
            $val = $request->json('code');
        }
        $result = ProductModifier::with(['products','product_categories','brands'])->where($col,$val)->first();
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
        $data = [
            'modifier_type'=>$post['modifier_type'],
            'type'=>$post['type'],
            'code'=>$post['code'],
            'text'=>$post['text'],
            'product_modifier_visibility'=>($post['product_modifier_visibility']??false)?'Visible':'Hidden',
        ];
        $update = $product_modifier->update($data);
        if(!$update){
            DB::rollback();
            return MyHelper::checkUpdate($update);
        }
        if(!($post['patch']??false)){
            ProductModifierBrand::where('id_product_modifier',$id_product_modifier)->delete();
            ProductModifierProduct::where('id_product_modifier',$id_product_modifier)->delete();
            ProductModifierProductCategory::where('id_product_modifier',$id_product_modifier)->delete();
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

    public function listType() {
        $data = ProductModifier::select('type')->groupBy('type')->get()->pluck('type');
        return MyHelper::checkGet($data);
    }

    public function listPrice(Request $request) {
        $id_outlet = $request->json('id_outlet');
        $brands = BrandOutlet::select('id_brand')->where('id_outlet',$id_outlet)->get()->pluck('id_brand');
        $data = ProductModifier::leftJoin('product_modifier_brands','product_modifier_brands.id_product_modifier','=','product_modifiers.id_product_modifier')
        ->where(function($query) use($brands){
            $query->where('modifier_type','Global');
            $query->orWhereNull('id_brand');
            $query->orWhereIn('id_brand',$brands);
        })
        ->select('product_modifiers.id_product_modifier','product_modifiers.code','product_modifiers.text','product_modifier_prices.product_modifier_price','product_modifier_prices.product_modifier_visibility','product_modifier_prices.product_modifier_status','product_modifier_prices.product_modifier_stock_status')->leftJoin('product_modifier_prices',function($join) use ($id_outlet){
            $join->on('product_modifiers.id_product_modifier','=','product_modifier_prices.id_product_modifier');
            $join->where('product_modifier_prices.id_outlet','=',$id_outlet);
        })->where(function($query) use ($id_outlet){
            $query->where('product_modifier_prices.id_outlet',$id_outlet);
            $query->orWhereNull('product_modifier_prices.id_outlet');
        })->groupBy('product_modifiers.id_product_modifier');
        if($request->page){
            $data = $data->paginate(10);
        }else{
            $data = $data->get();
        }
        return MyHelper::checkGet($data);
    }

    /**
     * Bulk Update price modifier table
     * @param  Request $request [description]
     * @return array           Update status
     */
    public function updatePrice(Request $request) {
        $id_outlet = $request->json('id_outlet');
        $insertData = [];
        DB::beginTransaction();
        foreach ($request->json('prices') as $id_product_modifier => $price) {
            if(!($price['product_modifier_price']??false)){
                continue;
            }
            $key = [
                'id_product_modifier' => $id_product_modifier,
                'id_outlet' => $id_outlet
            ];
            $insertData = $key + [
                'product_modifier_price' => $price['product_modifier_price'],
                'product_modifier_visibility' => $price['product_modifier_visibility'],
                'product_modifier_stock_status' => $price['product_modifier_stock_status'],
            ];
            $insert = ProductModifierPrice::updateOrCreate($key,$insertData);
            if(!$insert){
                DB::rollback();
                return [
                    'status' => 'fail',
                    'messages' => ['Update price fail']
                ];
            }
        }
        DB::commit();
        return ['status'=>'success'];
    }
}
