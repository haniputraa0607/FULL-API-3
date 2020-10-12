<?php

namespace Modules\ProductBundling\Http\Controllers;

use App\Http\Models\Product;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ProductBundling\Entities\Bundling;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductBundling\Http\Requests\CreateBundling;

class ApiBundlingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Bundling $bundling)
    {
        $bundling = Bundling::with(['bundling_product', 'bundling_outlet']);
        
        return MyHelper::checkGet($bundling->get());
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
    }

     /**
     * Check the input request for Bundling
     * @return Response
     */
    public function checkInputBundling($post = [], $type = null)
    {
        $data = [];
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateBundling $request)
    {
        $post = $request->json()->all();
        // dd($post);
        if (isset($post['image'])) {
            $upload = MyHelper::uploadPhoto($post['image'], $path = 'img/bundling/image/');
            if ($upload['status'] == "success") {
                $post['image'] = $upload['path'];
            } else {
                $result = [
                    'status' => 'fail',
                    'messages' => 'fail upload image'
                ];
                return response()->json($result);
            }
        }
        
        try {
            $data = [
                'bundling_name' => $post['bundling_name'],
                'bundling_description' => $post['bundling_description'],
                'price' => $post['price'],
                'discount' => $post['discount_type'],
                'all_outlet' => $post['all_outlet'],
                'created_by' => Auth::id(),
                'start_date' => $post['start_date'],
                'end_date' => $post['end_date']
            ];
    
            $bundling = Bundling::create($data);

            if ($bundling)
            {   
                if(is_array($outlets = $post['id_outlet'] ?? false)){
                    foreach ($outlets as $id_outlet) {
                        BundlingOutlet::create([
                            'id_bundling' => $bundling->id,
                            'id_outlet' => $id_outlet
                        ]);
                    }
                }

                if(is_array($products = $post['id_product'] ?? false)){
                    foreach ($products as $id_product) {
                        BundlingProduct::create([
                            'id_bundling' => $bundling->id,
                            'id_product' => $id_product,
                            'id_brand' => $post['id_brand'],
                            'jumlah' => $post['jumlah'],
                            'discount' => $post['discount'],
                        ]);
                    }
                }

                $result = [
                    'status'  => 'success',
                    'message' => 'Bundling Created Succesfully!'
                ];

                return response()->json($result);
            }
        } catch (\Exception $e) {
            $result = [
                'status'  => 'error',
                'message' => 'Bundling Creating Failed'
            ];
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function brandProduct(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_brand'])) {
            return [
                'status' => 'fail',
                'messages' => ['Brand id is required']
            ];
        }

        $product = DB::table('brands')
                    ->join('brand_product', 'brands.id_brand', '=', 'brand_product.id_brand')
                    ->join('products', 'brand_product.id_product', '=', 'products.id_product')
                    ->where('brand_product.id_brand','=', $post['id_brand'])
                    ->select('products.id_product', 'products.product_name', 'products.product_code');
        
        return MyHelper::checkGet($product->get());
    }

    public function productPrices(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_product'])){
            return [
                'status' => 'fail',
                'messages' => 'Product Id is required'
            ];
        }

        $price = DB::table('product_prices')
            ->join('products', 'products.id_product', 'product_prices.id_product')
            ->where('products.id_product', 'product_prices.id_product');
    }

}
