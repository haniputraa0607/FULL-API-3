<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductPromoCategory;

use App\Lib\MyHelper;

class ApiPromoCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $data = ProductPromoCategory::select('id_product_promo_category','product_promo_category_name')
            ->withCount('products')
            ->orderBy('product_promo_category_order')
            ->orderBy('id_product_promo_category');
        if($request->keyword){
            $data->where('product_promo_category_name','like',"%{$request->keyword}%");
        }
        if($request->page){
            return $data->paginate();
        }else{
            return $data->get();
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        $create = ProductPromoCategory::create($post);
        return MyHelper::checkCreate($create);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $data = ProductPromoCategory::find($request->json('id_product_promo_category'));
        return MyHelper::checkGet($data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();
        $ppc = ProductPromoCategory::find($request->json('id_product_promo_category'));
        if(!$ppc){
            return MyHelper::checkGet([]);
        }
        $update = $ppc->update($post);
        return MyHelper::checkUpdate($update);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $delete = ProductPromoCategory::find($request->json('id_product_promo_category'))->delete();
        return MyHelper::checkDelete($delete);
    }
}
