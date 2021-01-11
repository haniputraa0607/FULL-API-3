<?php

namespace Modules\ProductBundling\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductBundling\Entities\Bundling;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductBundling\Http\Requests\CreateBundling;
use DB;
use Modules\ProductBundling\Http\Requests\UpdateBundling;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;

class ApiBundlingController extends Controller
{
    function __construct()
    {
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Bundling $bundling)
    {
        $bundling = Bundling::with(['bundling_product'])->paginate(20);
        
        return MyHelper::checkGet($bundling);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateBundling $request)
    {
        $post = $request->json()->all();
        if(isset($post['data_product']) && !empty($post['data_product'])){
            DB::beginTransaction();
                //create bundling
                $createBundling = [
                    'bundling_name' => $post['bundling_name'],
                    'start_date' => date('Y-m-d H:i:s', strtotime($post['bundling_start'])),
                    'end_date' => date('Y-m-d H:i:s', strtotime($post['bundling_end'])),
                    'bundling_description' => $post['bundling_description']
                ];
                $create = Bundling::create($createBundling);

                if(!$create){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed create bundling']]);
                }

                if(isset($post['photo'])){
                    $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/bundling/', 300, 300, $create['id_bundling']);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $photo['image'] = $upload['path'];
                    }
                }

                if(isset($post['photo_detail'])){
                    $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/bundling/detail/', 720, 360, $create['id_bundling']);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $photo['image_detail'] = $upload['path'];
                    }
                }

                if(isset($photo) && !empty($photo)){
                    $updatePhotoBundling = Bundling::where('id_bundling', $create['id_bundling'])->update($photo);
                    if(!$updatePhotoBundling){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed update photo bundling']]);
                    }
                }

                //create bundling product
                $bundlingProduct = [];
                foreach ($post['data_product'] as $product){
                    $bundlingProduct[] = [
                        'id_bundling' => $create['id_bundling'],
                        'id_brand' => $product['id_brand'],
                        'id_product' => $product['id_product'],
                        'id_product_variant_group' => $product['id_product_variant_group']??null,
                        'bundling_product_qty' => $product['qty'],
                        'bundling_product_discount_type' => $product['discount_type'],
                        'bundling_product_discount' => $product['discount'],
                        'charged_central' => $product['charged_central'],
                        'charged_outlet' => $product['charged_outlet'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertBundlingProduct = BundlingProduct::insert($bundlingProduct);
                if(!$insertBundlingProduct){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert list product']]);
                }

                //create bundling outlet/outlet available
                $bundlingOutlet = [];
                foreach ($post['id_outlet'] as $outlet){
                    $bundlingOutlet[] = [
                        'id_bundling' => $create['id_bundling'],
                        'id_outlet' => $outlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $bundlingOutlet = BundlingOutlet::insert($bundlingOutlet);
                if(!$bundlingOutlet){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet available']]);
                }
                DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data product can not be empty']]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function detail(Request $request)
    {
        $post = $request->json()->all();
        if(isset($post['id_bundling']) && !empty($post['id_bundling'])){
            $detail = Bundling::where('id_bundling', $post['id_bundling'])
                    ->with(['bundling_product'])->first();

            $brands = [];
            if(!empty($detail['bundling_product'])){
                foreach ($detail['bundling_product'] as $bp){
                    $brands[] = $bp['id_brand'];
                    $bp['products'] = Product::join('brand_product','products.id_product','=','brand_product.id_product')
                        ->where('brand_product.id_brand', $bp['id_brand'])
                        ->select('products.id_product', 'products.product_code', 'products.product_name')->get()->toArray();
                    $bp['product_variant'] = [];
                    $bp['product_variant'] = app($this->product_variant_group)->productVariantGroupListAjax($bp['id_product'], 'array');
                }
            }

            $outletAvailable = Outlet::join('brand_outlet as bo', 'bo.id_outlet', 'outlets.id_outlet')
                ->groupBy('outlets.id_outlet')
                ->whereIn('bo.id_brand', $brands)
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();
            $selectedOutletAvailable = BundlingOutlet::where('id_bundling', $post['id_bundling'])->pluck('id_outlet')->toArray();

            if(!empty($detail)){
                return response()->json(['status' => 'success',
                                         'result' => [
                                             'detail' => $detail,
                                             'outlets' => $outletAvailable,
                                             'selected_outlet' => $selectedOutletAvailable
                                         ]]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be null']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be null']]);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(UpdateBundling $request)
    {
        $post = $request->json()->all();
        if(isset($post['data_product']) && !empty($post['data_product'])){
            DB::beginTransaction();
            //update bundling
            $updateBundling = [
                'bundling_name' => $post['bundling_name'],
                'start_date' => date('Y-m-d H:i:s', strtotime($post['bundling_start'])),
                'end_date' => date('Y-m-d H:i:s', strtotime($post['bundling_end'])),
                'bundling_description' => $post['bundling_description']
            ];
            $update = Bundling::where('id_bundling', $post['id_bundling'])->update($updateBundling);

            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update bundling']]);
            }

            if(isset($post['photo'])){
                $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/bundling/', 300, 300, $post['id_bundling']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $photo['image'] = $upload['path'];
                }
            }

            if(isset($post['photo_detail'])){
                $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/bundling/detail/', 720, 360, $post['id_bundling']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $photo['image_detail'] = $upload['path'];
                }
            }

            if(isset($photo) && !empty($photo)){
                $updatePhotoBundling = Bundling::where('id_bundling', $post['id_bundling'])->update($photo);
                if(!$updatePhotoBundling){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed update photo bundling']]);
                }
            }

            //update bundling product
            foreach ($post['data_product'] as $product){
                $bundlingProduct = [
                    'id_bundling' => $post['id_bundling'],
                    'id_brand' => $product['id_brand'],
                    'id_product' => $product['id_product'],
                    'id_product_variant_group' => $product['id_product_variant_group']??null,
                    'bundling_product_qty' => $product['qty'],
                    'bundling_product_discount_type' => $product['discount_type'],
                    'bundling_product_discount' => $product['discount'],
                    'charged_central' => $product['charged_central'],
                    'charged_outlet' => $product['charged_outlet'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                if(isset($product['id_bundling_product']) && !empty($product['id_bundling_product'])){
                    $saveBundlingProduct = BundlingProduct::where('id_bundling_product', $product['id_bundling_product'])->update($bundlingProduct);
                }else{
                    $bundlingProduct['created_at'] = date('Y-m-d H:i:s');
                    $saveBundlingProduct = BundlingProduct::create($bundlingProduct);
                }

                if(!$saveBundlingProduct){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save list product']]);
                }
            }

            //delete bundling outlet
            $delete = BundlingOutlet::where('id_bundling', $post['id_bundling'])->delete();

            if(!$delete){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed delete outlet available']]);
            }

            //create bundling outlet/outlet available
            $bundlingOutlet = [];
            foreach ($post['id_outlet'] as $outlet){
                $bundlingOutlet[] = [
                    'id_bundling' => $post['id_bundling'],
                    'id_outlet' => $outlet,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            $bundlingOutlet = BundlingOutlet::insert($bundlingOutlet);
            if(!$bundlingOutlet){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet available']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data product can not be empty']]);
        }
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

    public function outletAvailable(Request $request){
        $post = $request->json()->all();
        if(isset($post['brands']) && !empty($post['brands'])){
            $idBrand = array_column($post['brands'], 'value');
            $outlets = Outlet::join('brand_outlet as bo', 'bo.id_outlet', 'outlets.id_outlet')
                ->groupBy('outlets.id_outlet')
                ->whereIn('bo.id_brand', $idBrand)
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();

            return response()->json(MyHelper::checkGet($outlets));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted parameter']]);
        }
    }

    public function detailForApps(Request $request){
        $post = $request->json()->all();
        if(!isset($post['id_bundling']) && empty($post['id_bundling'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be empty']]);
        }

        if(!isset($post['id_outlet']) && empty($post['id_outlet'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $post['id_outlet'])->first();
        if (!$outlet) {
            return ['status' => 'fail','messages' => ['Outlet not found']];
        }

        $getProductBundling = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
            ->join('brand_product', 'brand_product.id_product', 'products.id_product')
            ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
            ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
            ->where('bundling.id_bundling', $post['id_bundling'])
            ->select('brand_product.id_brand', 'pgp.product_global_price',  'products.product_variant_status', 'products.product_name',
                'products.product_code', 'products.product_description',
                'bundling_product.*', 'bundling.*')
            ->get()->toArray();

        if (empty($getProductBundling)) {
            return ['status' => 'fail','messages' => ['Bundling detail not found']];
        }

        //check available outlet
        $availableOutlet = BundlingOutlet::where('id_outlet', $post['id_outlet'])->where('id_bundling', $post['id_bundling'])->first();
        if (!$availableOutlet) {
            return ['status' => 'fail','messages' => ['Product not available in this outlet']];
        }

        $priceForList = 0;
        $products = [];
        foreach ($getProductBundling as $p){
            $price = $p['product_global_price'];

            if($outlet['outlet_different_price'] == 1){
                $price = ProductSpecialPrice::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
            }

            if ($p['product_variant_status']) {
                $variantTree = Product::getVariantTree($p['id_product'], $outlet);
                $price = $variantTree['base_price']??0;
            }

            $variantDescription = '';
            if(!empty($p['id_product_variant_group'])){
                $variant = ProductVariantGroup::with(['product_variant_pivot'])->where('id_product_variant_group', $p['id_product_variant_group'])->first();
                $variantDescription = implode(', ', array_column($variant['product_variant_pivot']->toArray()??[], 'product_variant_name'));
            }

            $price = (float)$price;
            //calculate discount produk
            if(strtolower($p['bundling_product_discount_type']) == 'nominal'){
                $calculate = ($price - $p['bundling_product_discount']);
            }else{
                $discount = $price*($p['bundling_product_discount']/100);
                $calculate = ($price - $discount);
            }
            $calculate = $calculate * $p['bundling_product_qty'];
            $priceForList = $priceForList + $calculate;

            $products[] = [
                'id_product' => $p['id_product'],
                'id_brand' => $p['id_brand'],
                'id_bundling' => $p['id_bundling'],
                'id_bundling_product' => $p['id_bundling_product'],
                'product_name' => $p['product_name'],
                'product_code' => $p['product_code'],
                'product_description' => $p['product_description'],
                'product_variant_description' => $variantDescription
            ];
        }

        $result = [
            'bundling' => [
                'id_bundling' => $getProductBundling[0]['id_bundling'],
                'bundling_name' => $getProductBundling[0]['bundling_name'],
                'bundling_code' => $getProductBundling[0]['bundling_code'],
                'bundling_description' => $getProductBundling[0]['bundling_description'],
                'bundling_image_detail' => (!empty($getProductBundling[0]['image_detail']) ? config('url.storage_url_api').$getProductBundling[0]['image_detail'] : ''),
                'bundling_base_price' => $priceForList
            ],
            'products' => $products
        ];

        return response()->json(MyHelper::checkGet($result));
    }
}
