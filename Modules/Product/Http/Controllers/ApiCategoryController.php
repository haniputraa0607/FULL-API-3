<?php

namespace Modules\Product\Http\Controllers;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\ProductPrice;
use App\Http\Models\NewsProduct;
use App\Http\Models\Setting;

use Modules\Brand\Entities\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductBundling\Entities\Bundling;
use Modules\ProductBundling\Entities\BundlingOutletGroup;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductBundling\Entities\BundlingToday;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Validator;
use Hash;
use DB;
use Mail;

use Modules\Product\Http\Requests\category\CreateProduct;
use Modules\Product\Http\Requests\category\UpdateCategory;
use Modules\Product\Http\Requests\category\DeleteCategory;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;

class ApiCategoryController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo                   = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->bundling                   = "Modules\ProductBundling\Http\Controllers\ApiBundlingController";
    }

    public $saveImage = "img/product/category/";

    /**
     * check inputan
     */
    function checkInputCategory($post = [], $type = "update")
    {
        $data = [];

        if (isset($post['product_category_name'])) {
            $data['product_category_name'] = $post['product_category_name'];
        }

        if (isset($post['product_category_description'])) {
            $data['product_category_description'] = $post['product_category_description'];
        }

        if (isset($post['product_category_photo'])) {
            $save = MyHelper::uploadPhotoStrict($post['product_category_photo'], $this->saveImage, 300, 300);

            if (isset($save['status']) && $save['status'] == "success") {
                $data['product_category_photo'] = $save['path'];
            } else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return $result;
            }
        }

        if (isset($post['product_category_order'])) {
            $data['product_category_order'] = $post['product_category_order'];
        } else {
            // khusus create
            if ($type == "create") {
                if (isset($post['id_parent_category'])) {
                    $data['product_category_order'] = $this->searchLastSorting($post['id_parent_category']);
                } else {
                    $data['product_category_order'] = $this->searchLastSorting(null);
                }
            }
        }

        if (isset($post['id_parent_category']) && $post['id_parent_category'] != null) {
            $data['id_parent_category'] = $post['id_parent_category'];
        } else {
            $data['id_parent_category'] = null;
        }

        return $data;
    }

    /**
     * create category
     */
    function create(Request $request)
    {

        $post = $request->all();
        if(isset($post['data']) && !empty($post['data'])){
            DB::beginTransaction();
            $data_request = $post['data'];

            $imageParent = null;
            if(!empty($data_request[0]['product_category_image'])){
                $uploadParent = MyHelper::uploadPhoto($data_request[0]['product_category_image'], $path = 'img/product_category/');
                if($uploadParent['status'] == "success"){
                    $imageParent = $uploadParent['path'];
                }
            }
            $store = ProductCategory::create([
                'product_category_name' => $data_request[0]['product_category_name'],
                'product_category_photo' => $imageParent
                ]);

            if($store){
                if(isset($data_request['child'])){
                    $id = $store['id_product_category'];
                    foreach ($data_request['child'] as $key=>$child){
                        $id_parent = NULL;

                        if($child['parent'] == 0){
                            $id_parent = $id;
                        }elseif(isset($data_request['child'][(int)$child['parent']]['id'])){
                            $id_parent = $data_request['child'][(int)$child['parent']]['id'];
                        }

                        $image = null;
                        if(!empty($child['product_category_image'])){
                            $upload = MyHelper::uploadPhoto($child['product_category_image'], $path = 'img/product_category/');
                            if($upload['status'] == "success"){
                                $image = $upload['path'];
                            }
                        }

                        $store = ProductCategory::create([
                            'product_category_name' => $child['product_category_name'],
                            'product_category_photo' => $image,
                            'id_parent_category' => $id_parent]);

                        if($store){
                            $data_request['child'][$key]['id'] = $store['id_product_category'];
                        }else{
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add product category']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add product category']]);
            }

            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * cari urutan ke berapa
     */
    function searchLastSorting($id_parent_category = null)
    {
        $sorting = ProductCategory::select('product_category_order')->orderBy('product_category_order', 'DESC');

        if (is_null($id_parent_category)) {
            $sorting->whereNull('id_parent_category');
        } else {
            $sorting->where('id_parent_category', $id_parent_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        } else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->product_category_order)) {
                return 1;
            } else {
                $sorting = $sorting->product_category_order + 1;
                return $sorting;
            }
        }
    }

    /**
     * update category
     */
    public function update(Request $request)
    {
        $post = $request->all();

        if(isset($post['id_product_category']) && !empty($post['id_product_category'])){
            DB::beginTransaction();
            if(isset($post['product_category_name'])){
                $data_update['product_category_name'] = $post['product_category_name'];
            }

            if(isset($post['id_parent'])){
                $data_update['id_parent'] = $post['id_parent'];
            }

            if(!empty($post['product_category_image'])){
                $uploadParent = MyHelper::uploadPhoto($post['product_category_image'], $path = 'img/product_category/');
                if($uploadParent['status'] == "success"){
                    $data_update['product_category_photo'] = $uploadParent['path'];
                }
            }

            $update = ProductCategory::where('id_product_category', $post['id_product_category'])->update($data_update);

            if($update){
                if(isset($post['child']) && !empty($post['child'])){
                    foreach ($post['child'] as $child){
                        $data_update_child = [];
                        $data_update_child['id_parent_category'] = $post['id_product_category'];
                        if(isset($child['product_category_name'])){
                            $data_update_child['product_category_name'] = $child['product_category_name'];
                        }

                        if(!empty($child['product_category_image'])){
                            $upload = MyHelper::uploadPhoto($child['product_category_image'], $path = 'img/product_category/');
                            if($upload['status'] == "success"){
                                $data_update_child['product_category_photo'] = $upload['path'];
                            }
                        }

                        $update = ProductCategory::updateOrCreate(['id_product_category' => $child['id_product_category']], $data_update_child);

                        if(!$update){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed update child product category']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update product category']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function edit(Request $request)
    {
        $post = $request->all();

        if(isset($post['id_product_category']) && !empty($post['id_product_category'])){
            $get_all_parent = ProductCategory::where(function ($q){
                $q->whereNull('id_parent_category')->orWhere('id_parent_category', 0);
            })->get()->toArray();

            $product_category = ProductCategory::where('id_product_category', $post['id_product_category'])->with(['category_parent', 'category_child'])->first();
            if($product_category){
                $product_category['last_child'] = 0;
                if(!empty($product_category['id_parent_category'])){
                    $cat_child = ProductCategory::where('id_product_category', $product_category['id_parent_category'])->first();
                    if(!empty($cat_child['id_parent_category'])){
                        $product_category['last_child'] = 1;
                    }
                }
            }
            return response()->json(['status' => 'success', 'result' => [
                'all_parent' => $get_all_parent,
                'category' => $product_category
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function delete(Request $request)
    {
        $id_product_category = $request->json('id_product_category');
        $delete       = ProductCategory::where('id_product_category', $id_product_category)->delete();

        if($delete){
            $delete = $this->deleteChild($id_product_category);
        }
        return MyHelper::checkDelete($delete);
    }

    public function deleteChild($id_parent){
        $get = ProductCategory::where('id_parent_category', $id_parent)->first();
        if($get){
            $delete  = ProductCategory::where('id_parent_category', $id_parent)->delete();
            $this->deleteChild($get['id_product_category']);
            return $delete;
        }else{
            return true;
        }
    }

    /**
     * delete check digunakan sebagai parent
     */
    function checkDeleteParent($id)
    {
        $check = ProductCategory::where('id_parent_category', $id)->count();

        if ($check == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * delete check digunakan sebagai product
     */
    function checkDeleteProduct($id)
    {
        $check = Product::where('id_product_category', $id)->count();

        if ($check == 0) {
            return true;
        } else {
            return false;
        }
        return true;
    }

    /**
     * list non tree
     * bisa by id parent category
     */
    function listCategory(Request $request)
    {
        $post = $request->all();
        $list = ProductCategory::with(['category_parent', 'category_child']);

        if ($keyword = ($request->search['value']??false)) {
            $list->where('product_category_name', 'like', '%'.$keyword.'%')
                ->orWhereHas('category_parent', function($q) use ($keyword) {
                    $q->where('product_category_name', 'like', '%'.$keyword.'%');
                })
                ->orWhereHas('category_child', function($q) use ($keyword) {
                    $q->where('product_category_name', 'like', '%'.$keyword.'%');
                });
        }

        if(isset($post['get_child']) && $post['get_child'] == 1){
            $list = $list->whereNotNull('id_parent_category');
        }

        $list = $list->orderBy('product_category_order', 'asc')->get()->toArray();

        return MyHelper::checkGet($list);
    }

    /**
     * list tree
     * bisa by id parent category
     */
    function listCategoryTreeX(Request $request)
    {
        $post = $request->json()->all();

        $category = $this->getData($post);

        if (!empty($category)) {
            $category = $this->createTree($category, $post);
        }

        if (isset($post['id_outlet'])) {
            $uncategorized = Product::join('product_prices', 'product_prices.id_product', '=', 'products.id_product')
                ->where('product_prices.id_outlet', '=', $post['id_outlet'])
                ->where(function ($query) {
                    $query->where('product_prices.product_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_prices.product_visibility')
                                ->where('products.product_visibility', 'Visible');
                        });
                })
                ->where('product_prices.product_status', '=', 'Active')
                ->whereNotNull('product_prices.product_price')
                ->whereNull('products.id_product_category')
                ->with(['photos'])
                ->orderBy('products.position')
                ->get()
                ->toArray();
        } else {
            $defaultoutlet = Setting::where('key', '=', 'default_outlet')->first();
            $uncategorized = Product::join('product_prices', 'product_prices.id_product', '=', 'products.id_product')
                ->where('product_prices.id_outlet', '=', $defaultoutlet['value'])
                ->where(function ($query) {
                    $query->where('product_prices.product_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_prices.product_visibility')
                                ->where('products.product_visibility', 'Visible');
                        });
                })
                ->where('product_prices.product_status', '=', 'Active')
                ->whereNotNull('product_prices.product_price')
                ->whereNull('products.id_product_category')
                ->with(['photos'])
                ->orderBy('products.position')
                ->get()
                ->toArray();
        }

        $result = array();
        $dataCategory = [];
        if (!empty($category)) {
            foreach ($category as $key => $value) {
                if (count($value['product']) < 1) {
                    // unset($category[$key]);
                } else {
                    foreach ($value['product'] as $index => $prod) {
                        if (count($prod['photos']) < 1) {
                            $value['product'][$index]['photos'][] = [
                                "id_product_photo" => 0,
                                "id_product" => $prod['id_product'],
                                "product_photo" => 'img/product/item/default.png',
                                "created_at" => $prod['created_at'],
                                "updated_at" => $prod['updated_at'],
                                "url_product_photo" => config('url.storage_url_api') . 'img/product/item/default.png'
                            ];
                        }
                    }
                    $dataCategory[] = $value;
                }
            }
        }

        $result['categorized'] = $dataCategory;

        if (!isset($post['id_product_category'])) {
            $result['uncategorized_name'] = "Product";
            $result['uncategorized'] = $uncategorized;
        }

        return response()->json(MyHelper::checkGet($result));
    }

    /**
     * list tree
     * bisa by id parent category and id brand
     */
    function listCategoryTree(Request $request)
    {
        $post = $request->json()->all();
        if (!($post['id_outlet'] ?? false)) {
            $post['id_outlet'] = Setting::where('key', 'default_outlet')->pluck('value')->first();
        }
        $products = Product::select([
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status',
            DB::raw('(CASE
                        WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $post['id_outlet'] . ' ) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $post['id_outlet'] . ' )
                        ELSE product_global_price.product_global_price
                    END) as product_price'),
            DB::raw('(CASE
                        WHEN (select product_detail.product_detail_stock_status from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' order by id_product_detail desc limit 1) 
                        is NULL THEN "Available"
                        ELSE (select product_detail.product_detail_stock_status from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END) as product_stock_status'),
        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            // brand produk ada di outlet
            ->where('brand_outlet.id_outlet', '=', $post['id_outlet'])
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . '  order by id_product_detail desc limit 1)
                    END)')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' order by id_product_detail desc limit 1)
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END)')
            ->where(function ($query) use ($post) {
                $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $post['id_outlet'] . '  order by id_product_special_price desc limit 1) is NOT NULL');
                $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product order by id_product_global_price desc limit 1) is NOT NULL');
            })
            ->with([
                'brand_category' => function ($query) {
                    $query->groupBy('id_product', 'id_brand');
                },
                'photos' => function ($query) {
                    $query->select('id_product', 'product_photo');
                },
                'product_promo_categories' => function ($query) {
                    $query->select('product_promo_categories.id_product_promo_category', 'product_promo_category_name as product_category_name', 'product_promo_category_order as product_category_order');
                },
            ])
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product', 'product_price', 'product_stock_status')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get();

        // grouping by id
        $result = [];
        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $post['id_outlet'])->first();
        if (!$outlet) {
            return [
                'status' => 'fail', 
                'messages' => ['Outlet not found']
            ];
        }

        $brands = [];
        foreach ($products as $product) {
            if ($product->product_variant_status && $product->product_stock_status == 'Available') {
                $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                $product['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
            }
            $product['product_price_raw'] = (int) $product['product_price'];
            $product->append('photo');
            $product = $product->toArray();
            $pivots = $product['brand_category'];
            unset($product['brand_category']);
            unset($product['photos']);
            unset($product['product_prices']);
            $ppc = $product['product_promo_categories'];
            unset($product['product_promo_categories']);
            foreach ($pivots as $pivot) {
                $id_category = 0;
                if ($pivot['id_product_category']) {
                    $product['id_brand'] = $pivot['id_brand'];
                    $result[$pivot['id_brand']][$pivot['id_product_category']][] = $product;
                    $id_category = $pivot['id_product_category'];
                }
                if (!$id_category) {
                    continue;
                }
                //promo category
                if ($ppc) {
                    foreach ($ppc as $promo_category) {
                        $promo_category['id_product_category'] = $id_category;
                        $promo_category['url_product_category_photo'] = '';
                        $id_product_promo_category = $promo_category['id_product_promo_category'];
                        unset($promo_category['id_product_promo_category']);
                        $product['position'] = $promo_category['pivot']['position'];
                        unset($promo_category['pivot']);
                        if (!($result[$pivot['id_brand']]['promo' . $id_product_promo_category] ?? false)) {
                            $promo_category['product_category_order'] -= 100000;
                            $result[$pivot['id_brand']]['promo' . $id_product_promo_category]['category'] = $promo_category;
                        }
                        $result[$pivot['id_brand']]['promo' . $id_product_promo_category]['list'][] = $product;
                    }
                }
                $brands[] = $pivot['id_brand'];
            }
        }

        $brands = array_unique($brands);
        //get product bundling
        $result = $this->getBundling($post, $brands, $outlet, $result);

        // get detail of every key
        foreach ($result as $id_brand => $categories) {
            foreach ($categories as $id_category => $products) {
                if (!is_numeric($id_category)) {
                    // berarti ini promo category
                    $products['list'] = array_filter($products['list']);
                    usort($products['list'], function ($a, $b) {
                        return $a['position'] <=> $b['position'];
                    });
                    $categories[$id_category] = $products;
                    continue;
                }
                $category = ProductCategory::select('id_product_category', 'product_category_name', 'product_category_order')->find($id_category);
                $categories[$id_category] = [
                    'category' => $category,
                    'list' => $products
                ];
            }
            usort($categories, function ($a, $b) {
                $pos_a = $a['category']['product_category_order'];
                $pos_b = $b['category']['product_category_order'];
                if (!$pos_a) {
                    $pos_a = 99999;
                }
                if (!$pos_b) {
                    $pos_b = 99999;
                }
                return $pos_a <=> $pos_b ?: $a['category']['id_product_category'] <=> $b['category']['id_product_category'];
            });

            if($id_brand >= 1000){
                $settingBundlingBrand = Setting::where('key', 'brand_bundling_name')->first();
                $brand = [
                    'id_brand' => $id_brand,
                    'name_brand' => $settingBundlingBrand['value']??'Bundling',
                    'code_brand' => "",
                    'order_brand' => -1000
                 ];
            }else{
                $brand = Brand::select('id_brand', 'name_brand', 'code_brand', 'order_brand')->find($id_brand);
                if (!$brand) {
                    unset($result[$id_brand]);
                    continue;
                }
            }
            $result[$id_brand] = [
                'brand' => $brand,
                'list' => $categories
            ];
        }
        usort($result, function ($a, $b) {
            return $a['brand']['order_brand'] <=> $b['brand']['order_brand'];
        });

        // check promo
       	$pct = new PromoCampaignTools;
        $promo_data = $pct->applyPromoProduct($post, $result, 'list_product2', $promo_error);

        if ($promo_data) {
            $result = $promo_data;
        }

        $result = MyHelper::checkGet($result);
        $result['promo_error'] = $promo_error;
        $result['total_promo'] = app($this->promo)->availablePromo();
        return response()->json($result);
    }

    public function search(Request $request)
    {
        $post = $request->except('_token');
        if (!($post['id_outlet'] ?? false)) {
            $post['id_outlet'] = Setting::where('key', 'default_outlet')->pluck('value')->first();
        }
        $products = Product::select([
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description',
            'brand_product.id_product_category', 'brand_product.id_brand', 'product_variant_status',
            DB::raw('(CASE
                        WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $post['id_outlet'] . ' ) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $post['id_outlet'] . ' )
                        ELSE product_global_price.product_global_price
                    END) as product_price'),
            DB::raw('(CASE
                        WHEN (select product_detail.product_detail_stock_status from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' ) 
                        is NULL THEN "Available"
                        ELSE (select product_detail.product_detail_stock_status from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
                    END) as product_stock_status'),
        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            // brand produk ada di outlet
            ->where('brand_outlet.id_outlet', '=', $post['id_outlet'])
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
                    END)')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
                    END)')
            ->where(function ($query) use ($post) {
                $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $post['id_outlet'] . ' ) is NOT NULL');
                $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product) is NOT NULL');
            })
             // cari produk
            ->where('products.product_name', 'like', '%' . $post['product_name'] . '%')
            ->with([
                'photos' => function ($query) {
                    $query->select('id_product', 'product_photo');
                }
            ])
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get();

        $pct = new PromoCampaignTools;
        $promo_data = $pct->applyPromoProduct($post, $products, 'search_product', $promo_error);

        if ($promo_data) {
            $products = $promo_data;
        }

        $result = [];
        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $post['id_outlet'])->first();
        foreach ($products as $product) {
            $product->append('photo');
            $product['id_outlet'] = $post['id_outlet'];
            if ($product->product_variant_status) {
                $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                $product['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
            }
            $result[$product->id_product_category]['list'][] = $product;
            if (!isset($result[$product->id_product_category]['category'])) {
                $result[$product->id_product_category]['category'] = ProductCategory::select('id_product_category', 'product_category_name')->find($product->id_product_category);
            }
        }

        $resultsFinal = $this->getBundlingSearch($post, $outlet, array_values($result));
        return MyHelper::checkGet($resultsFinal, 'Menu tidak ditemukan');
    }

    function getBundling($post, $brands, $outlet, $resProduct){
        $resBundling = [];
        $count = count($brands);
        $currentHour = date('H:i:s');

        $bundlings1 = Bundling::join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
            ->join('bundling_product as bp', 'bp.id_bundling', 'bundling.id_bundling')
            ->join('brand_product', 'brand_product.id_product', 'bp.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', 'brand_product.id_brand')
            ->where('brand_outlet.id_outlet', $post['id_outlet'])
            ->where('bundling.all_outlet', 1)
            ->where('bundling.outlet_available_type', 'Selected Outlet')
            ->whereIn('brand_product.id_brand', $brands)
            ->whereRaw('TIME_TO_SEC("'.$currentHour.'") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("'.$currentHour.'") <= TIME_TO_SEC(time_end)')
            ->pluck('bundling.id_bundling')->toArray();

        $bundlings2 = Bundling::join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
            ->join('bundling_outlet as bo', 'bo.id_bundling', 'bundling.id_bundling')
            ->join('bundling_product as bp', 'bp.id_bundling', 'bundling.id_bundling')
            ->join('brand_product', 'brand_product.id_product', 'bp.id_product')
            ->where('all_outlet', 0)
            ->where('bundling.outlet_available_type', 'Selected Outlet')
            ->where('bo.id_outlet', $post['id_outlet'])
            ->whereIn('brand_product.id_brand', $brands)
            ->whereRaw('TIME_TO_SEC("'.$currentHour.'") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("'.$currentHour.'") <= TIME_TO_SEC(time_end)')
            ->pluck('bundling.id_bundling')->toArray();

        $bundling3 = app($this->bundling)->bundlingOutletGroupFilter($post['id_outlet'], $brands);

        $bundlings = array_merge($bundlings1,$bundlings2, $bundling3);
        $bundlings = array_unique($bundlings);

        //calculate price
        foreach ($bundlings as $bundling){
            $getProduct = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
                ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
                ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
                ->join('bundling_categories', 'bundling_categories.id_bundling_category', 'bundling.id_bundling_category')
                ->where('bundling.id_bundling', $bundling)
                ->select('products.product_visibility', 'pgp.product_global_price',  'products.is_inactive', 'products.product_variant_status',
                    'bundling_product.*', 'bundling.*', 'bundling_categories.bundling_category_name', 'bundling_categories.bundling_category_order')
                ->get()->toArray();

            if(!empty($getProduct)){
                $priceForListNoDiscount = 0;
                $priceForList = 0;
                $id_brand = [];
                $stockStatus = 1;
                foreach ($getProduct as $p){
                    if($p['is_inactive'] == 1){
                        continue 2;
                    }
                    $getProductDetail = ProductDetail::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first();
                    $p['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                    if($getProductDetail['product_detail_stock_status'] == 'Sold Out'){
                        $stockStatus = 0;
                    }

                    if($p['visibility_outlet'] == 'Hidden' || (empty($p['visibility_outlet']) && $p['product_visibility'] == 'Hidden')){
                        continue 2;
                    }else{
                        $id_brand[] = BrandProduct::where('id_product', $p['id_product'])->first()['id_brand'];
                        if($p['product_variant_status'] && !empty($p['id_product_variant_group'])){
                            $cekVisibility = ProductVariantGroup::where('id_product_variant_group', $p['id_product_variant_group'])->first();

                            if($cekVisibility['product_variant_group_visibility'] == 'Hidden'){
                                continue 2;
                            }else{
                                if($outlet['outlet_different_price'] == 1){
                                    $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $p['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price']??0;
                                }else{
                                    $price = $cekVisibility['product_variant_group_price']??0;
                                }
                            }
                        }elseif(!empty($p['id_product'])){
                            if($outlet['outlet_different_price'] == 1){
                                $price = ProductSpecialPrice::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
                            }else{
                                $price = $p['product_global_price'];
                            }
                        }

                        $price = (float)$price;
                        if($price <= 0){
                            continue 2;
                        }
                        //calculate discount produk
                        if(strtolower($p['bundling_product_discount_type']) == 'nominal'){
                            $calculate = ($price - $p['bundling_product_discount']);
                        }else{
                            $discount = $price*($p['bundling_product_discount']/100);
                            $discount = ($discount > $p['bundling_product_maximum_discount'] &&  $p['bundling_product_maximum_discount'] > 0? $p['bundling_product_maximum_discount']:$discount);
                            $calculate = ($price - $discount);
                        }
                        $calculate = $calculate * $p['bundling_product_qty'];
                        $priceForList = $priceForList + $calculate;
                        $priceForListNoDiscount = $priceForListNoDiscount + ($price * $p['bundling_product_qty']);
                    }
                }

                $dontHave = 0;
                $id_brand = array_unique($id_brand);
                foreach ($id_brand as $val){
                    if(!in_array($val, $brands)){
                        $dontHave = 1;
                    }
                }

                if($dontHave == 0){
                    $resBundling[] = [
                        "id_bundling" => $bundling,
                        "id_product_category" => $getProduct[0]['id_bundling_category']??'',
                        "product_category_name" => $getProduct[0]['bundling_category_name']??'',
                        'product_category_order' => $getProduct[0]['bundling_category_order']??0,
                        "id_product" => null,
                        "product_name" => $getProduct[0]['bundling_name']??'',
                        "product_code" => $getProduct[0]['bundling_code']??'',
                        "product_description" => $getProduct[0]['bundling_description']??'',
                        "product_variant_status" => null,
                        "product_price" => (int)$priceForList,
                        "product_stock_status" => ($stockStatus == 0 ? 'Sold Out' : 'Available'),
                        "product_price_raw" => (int)$priceForList,
                        "photo" => (!empty($getProduct[0]['image']) ? config('url.storage_url_api').$getProduct[0]['image'] : ''),
                        "product_price_no_discount" => $priceForListNoDiscount??0,
                        "is_promo" => 0,
                        "is_promo_bundling" => $getProduct[0]['bundling_promo_status']??0,
                        "brands" => $id_brand,
                        "position" => $getProduct[0]['bundling_order']??null
                    ];
                }
            }
        }

        $id_brand_bundling = 1000;
        foreach ($resBundling as $res){
            if(isset($resProduct[$id_brand_bundling][$res['product_category_name']]['category'])){
                $resProduct[$id_brand_bundling][$res['product_category_name']]['list'][] = [
                    "id_bundling" => $res['id_bundling'],
                    "id_product" => null,
                    "product_name" => $res['product_name'],
                    "product_code" => $res['product_code'],
                    "product_description" => $res['product_description'],
                    "product_variant_status" => null,
                    "product_price" => $res['product_price'],
                    "product_stock_status" => $res['product_stock_status'],
                    "product_price_raw" => $res['product_price_raw'],
                    "product_price_no_discount" => $res['product_price_no_discount'],
                    "photo" => $res['photo'],
                    "is_promo" => 0,
                    "is_promo_bundling" => $res['is_promo_bundling'],
                    "position" => $res['position']??0,
                    "id_brand" =>  $id_brand_bundling
                ];
            }else{
                $order = 2500000 - $res['product_category_order'];
                $resProduct[$id_brand_bundling][$res['product_category_name']]['category'] = [
                    "product_category_name" => $res['product_category_name'],
                    "product_category_order" => -$order,
                    "id_product_category" => $res['id_product_category'],
                    "url_product_category_photo" => ""
                ];

                $resProduct[$id_brand_bundling][$res['product_category_name']]['list'][] = [
                    "id_bundling" => $res['id_bundling'],
                    "id_product" => null,
                    "product_name" => $res['product_name'],
                    "product_code" => $res['product_code'],
                    "product_description" => $res['product_description'],
                    "product_variant_status" => null,
                    "product_price" => $res['product_price'],
                    "product_stock_status" => $res['product_stock_status'],
                    "product_price_raw" => $res['product_price_raw'],
                    "product_price_no_discount" => $res['product_price_no_discount'],
                    "photo" => $res['photo'],
                    "is_promo" => 0,
                    "is_promo_bundling" => $res['is_promo_bundling'],
                    "position" => $res['position']??0,
                    "id_brand" =>  $id_brand_bundling
                ];
            }
        }

        return $resProduct;
    }

    function getBundlingSearch($post, $outlet, $resProduct){
        $resBundling = [];
        $brands = BrandOutlet::where('id_outlet', $post['id_outlet'])->pluck('id_brand')->toArray();
        $count = count($brands);
        $currentHour = date('H:i:s');

        $bundlings1 = Bundling::join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
            ->join('bundling_product as bp', 'bp.id_bundling', 'bundling.id_bundling')
            ->join('brand_product', 'brand_product.id_product', 'bp.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', 'brand_product.id_brand')
            ->where('brand_outlet.id_outlet', $post['id_outlet'])
            ->where('bundling.all_outlet', 1)
            ->where('bundling.bundling_name', 'like', '%' . $post['product_name'] . '%')
            ->whereIn('brand_product.id_brand', $brands)
            ->whereRaw('TIME_TO_SEC("'.$currentHour.'") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("'.$currentHour.'") <= TIME_TO_SEC(time_end)')
            ->pluck('bundling.id_bundling')->toArray();

        $bundlings2 = Bundling::join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
            ->join('bundling_outlet as bo', 'bo.id_bundling', 'bundling.id_bundling')
            ->join('bundling_product as bp', 'bp.id_bundling', 'bundling.id_bundling')
            ->join('brand_product', 'brand_product.id_product', 'bp.id_product')
            ->where('all_outlet', 0)
            ->where('bo.id_outlet', $post['id_outlet'])
            ->where('bundling.bundling_name', 'like', '%' . $post['product_name'] . '%')
            ->whereIn('brand_product.id_brand', $brands)
            ->whereRaw('TIME_TO_SEC("'.$currentHour.'") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("'.$currentHour.'") <= TIME_TO_SEC(time_end)')
            ->pluck('bundling.id_bundling')->toArray();

        $bundlings = array_merge($bundlings1,$bundlings2);
        $bundlings = array_unique($bundlings);

        //calculate price
        foreach ($bundlings as $key => $bundling){
            $getProduct = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
                ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
                ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
                ->join('bundling_categories', 'bundling_categories.id_bundling_category', 'bundling.id_bundling_category')
                ->where('bundling.id_bundling', $bundling)
                ->select('products.product_visibility', 'pgp.product_global_price',  'products.product_variant_status',
                    'bundling_product.*', 'bundling.*', 'bundling_categories.bundling_category_name', 'bundling_categories.bundling_category_order')
                ->get()->toArray();

            if(!empty($getProduct)){
                $priceForListNoDiscount = 0;
                $priceForList = 0;
                $id_brand = [];
                $stockStatus = 1;
                foreach ($getProduct as $p){
                    $getProductDetail = ProductDetail::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first();
                    $p['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                    if($getProductDetail['product_detail_stock_status'] == 'Sold Out'){
                        $stockStatus = 0;
                    }

                    if($p['visibility_outlet'] == 'Hidden' || (empty($p['visibility_outlet']) && $p['product_visibility'] == 'Hidden')){
                        continue 2;
                    }else{
                        $id_brand[] = BrandProduct::where('id_product', $p['id_product'])->first()['id_brand'];
                        if($p['product_variant_status'] && !empty($p['id_product_variant_group'])){
                            $cekVisibility = ProductVariantGroup::where('id_product_variant_group', $p['id_product_variant_group'])->first();

                            if($cekVisibility['product_variant_group_visibility'] == 'Hidden'){
                                continue 2;
                            }else{
                                if($outlet['outlet_different_price'] == 1){
                                    $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $p['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price']??0;
                                }else{
                                    $price = $cekVisibility['product_variant_group_price']??0;
                                }
                            }
                        }elseif(!empty($p['id_product'])){
                            if($outlet['outlet_different_price'] == 1){
                                $price = ProductSpecialPrice::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
                            }else{
                                $price = $p['product_global_price'];
                            }
                        }

                        $price = (float)$price;
                        //calculate discount produk
                        if(strtolower($p['bundling_product_discount_type']) == 'nominal'){
                            $calculate = ($price - $p['bundling_product_discount']);
                        }else{
                            $discount = $price*($p['bundling_product_discount']/100);
                            $discount = ($discount > $p['bundling_product_maximum_discount'] &&  $p['bundling_product_maximum_discount'] > 0? $p['bundling_product_maximum_discount']:$discount);
                            $calculate = ($price - $discount);
                        }
                        $calculate = $calculate * $p['bundling_product_qty'];
                        $priceForList = $priceForList + $calculate;
                        $priceForListNoDiscount = $priceForListNoDiscount + ($price * $p['bundling_product_qty']);
                    }
                }

                $id_brand = array_unique($id_brand);
                if(count($brands) >= count($id_brand)){
                    $resBundling[] = [
                        "id_bundling" => $bundling,
                        "id_product_category" => $getProduct[0]['id_bundling_category']??'',
                        "product_category_name" => $getProduct[0]['bundling_category_name']??'',
                        'product_category_order' => $getProduct[0]['bundling_category_order']??0,
                        "id_product" => null,
                        "product_name" => $getProduct[0]['bundling_name']??'',
                        "product_code" => $getProduct[0]['bundling_code']??'',
                        "product_description" => $getProduct[0]['bundling_description']??'',
                        "product_variant_status" => null,
                        "product_price" => (int)$priceForList,
                        "product_stock_status" => ($stockStatus == 0 ? 'Sold Out' : 'Available'),
                        "product_price_raw" => (int)$priceForList,
                        "photo" => (!empty($getProduct[0]['image']) ? config('url.storage_url_api').$getProduct[0]['image'] : ''),
                        "product_price_no_discount" => $priceForListNoDiscount??0,
                        "is_promo" => 0,
                        "is_promo_bundling" => $getProduct[0]['bundling_promo_status']??0,
                        "brands" => $id_brand,
                        "position" => $getProduct[0]['bundling_order']??null
                    ];
                }
            }
        }

        $resBundlingFinal = [];
        foreach ($resBundling as $k=>$res){
            $product = [
                "id_bundling" => $res['id_bundling'],
                "id_product" => null,
                "product_name" => $res['product_name'],
                "product_code" => $res['product_code'],
                "product_description" => $res['product_description'],
                "product_variant_status" => null,
                "product_price" => $res['product_price'],
                "product_stock_status" => $res['product_stock_status'],
                "product_price_raw" => $res['product_price_raw'],
                "product_price_no_discount" => $res['product_price_no_discount'],
                "photo" => $res['photo'],
                "is_promo" => 0,
                "is_promo_bundling" => $res['is_promo_bundling'],
                "position" => $res['position']??0,
                "id_brand" =>  $res['brands'][0]??null
            ];
            $resBundlingFinal[$res['id_product_category']]['list'][] = $product;
            if (!isset($resBundlingFinal[$res['id_product_category']]['category'])) {
                $resBundlingFinal[$res['id_product_category']]['category'] = [
                    "product_category_name" => $res['product_category_name'],
                    "id_product_category" => $res['id_product_category'],
                    "url_product_category_photo" => ""
                ];
            }
        }

        return array_merge(array_values($resBundlingFinal), $resProduct);
    }

    function getData($post = [])
    {
        // $category = ProductCategory::select('*', DB::raw('if(product_category_photo is not null, (select concat("'.config('url.storage_url_api').'", product_category_photo)), "'.config('url.storage_url_api').'assets/pages/img/noimg-500-375.png") as url_product_category_photo'));
        $category = ProductCategory::with(['parentCategory'])->select('*');

        if (isset($post['id_parent_category'])) {

            if (is_null($post['id_parent_category']) || $post['id_parent_category'] == 0) {
                $category->master();
            } else {
                $category->parents($post['id_parent_category']);
            }
        } else {
            $category->master();
        }

        if (isset($post['id_product_category'])) {
            $category->id($post['id_product_category']);
        }

        $category = $category->orderBy('product_category_order')->get()->toArray();

        return $category;
    }

    /**
     * list
     */

    public function createTree($root, $post = [])
    {
        // print_r($root); exit();
        $node = [];

        foreach ($root as $i => $r) {
            $child = $this->getData(['id_parent_category' => $r['id_product_category']]);
            if (count($child) > 0) {
                $r['child'] = $this->createTree($child, $post);
            } else {
                $r['child'] = [];
            }

            $product = $this->getDataProduk($r['id_product_category'], $post);
            $r['product_count'] = count($product);
            $r['product'] = $product;

            array_push($node, $r);
        }
        return $node;
    }

    public function getDataProduk($id, $post = [])
    {
        if (isset($post['id_outlet'])) {
            $product = Product::select('products.*', 'product_prices.product_price', 'product_prices.product_visibility', 'product_prices.product_status', 'product_prices.product_stock_status', 'product_prices.id_outlet')->join('product_prices', 'product_prices.id_product', '=', 'products.id_product')
                ->where('product_prices.id_outlet', '=', $post['id_outlet'])
                ->where(function ($query) {
                    $query->where('product_prices.product_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_prices.product_visibility')
                                ->where('products.product_visibility', 'Visible');
                        });
                })
                ->where('product_prices.product_status', '=', 'Active')
                ->whereNotNull('product_prices.product_price')
                ->where('products.id_product_category', $id)
                ->with(['photos'])
                ->orderBy('products.position')
                ->get();
        } else {
            $defaultoutlet = Setting::where('key', '=', 'default_outlet')->first();
            $product = Product::select('products.*', 'product_prices.product_price', 'product_prices.product_visibility', 'product_prices.product_status', 'product_prices.product_stock_status')->join('product_prices', 'product_prices.id_product', '=', 'products.id_product')
                ->where('product_prices.id_outlet', '=', $defaultoutlet['value'])
                ->where(function ($query) {
                    $query->where('product_prices.product_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_prices.product_visibility')
                                ->where('products.product_visibility', 'Visible');
                        });
                })
                ->where('product_prices.product_status', '=', 'Active')
                ->whereNotNull('product_prices.product_price')
                ->where('products.id_product_category', $id)
                ->with(['photos'])
                ->orderBy('products.position')
                ->get();
        }
        return $product;
    }

    /* product category position */
    public function positionCategoryAssign(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['category_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Category id is required']
            ];
        }
        // update position
        foreach ($post['category_ids'] as $key => $category_id) {
            $update = ProductCategory::find($category_id)->update(['product_category_order' => $key + 1]);
        }

        return ['status' => 'success'];
    }

    public function getAllCategory()
    {
        $data = ProductCategory::orderBy('product_category_name')->get();
        return response()->json(MyHelper::checkGet($data));
    }

    public function applyPromo($promo_post, $data_product, &$promo_error)
    {
        $post = $promo_post;
        $products = $data_product;
        // promo code
        foreach ($products as $key => $value) {
            $products[$key]['is_promo'] = 0;
        }

        $promo_error = null;
        if (
            (!empty($post['promo_code']) && empty($post['id_deals_user']) && empty($post['id_subscription_user'])) ||
            (empty($post['promo_code']) && !empty($post['id_deals_user']) && empty($post['id_subscription_user'])) ||
            (empty($post['promo_code']) && empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
        ) {

            if (!empty($post['promo_code'])) {
                $code = app($this->promo_campaign)->checkPromoCode($post['promo_code'], 1, 1);
                if (!$code) {
                    $promo_error = 'Promo not valid';
                    return false;
                }
                $source = 'promo_campaign';
                $id_brand = $code->id_brand;
            } elseif (!empty($post['id_deals_user'])) {
                $code = app($this->promo_campaign)->checkVoucher($post['id_deals_user'], 1, 1);
                if (!$code) {
                    $promo_error = 'Promo not valid';
                    return false;
                }
                $source = 'deals';
                $id_brand = $code->dealVoucher->deals->id_brand;
            } elseif (!empty($post['id_subscription_user'])) {
                $code = app($this->subscription_use)->checkSubscription($post['id_subscription_user'], 1, 1, 1);
                if (!$code) {
                    $promo_error = 'Promo not valid';
                    return false;
                }
                $source = 'subscription';
                $id_brand = $code->subscription_user->subscription->id_brand;
            }

            if (($code['promo_campaign']['date_end'] ?? $code['voucher_expired_at'] ?? $code['subscription_expired_at']) < date('Y-m-d H:i:s')) {
                $promo_error = 'Promo is ended';
                return false;
            }
            $code = $code->toArray();

            $pct = new PromoCampaignTools;

			$all_outlet = $code['promo_campaign']['is_all_outlet']??$code['subscription_user']['subscription']['is_all_outlet']??$code['deal_voucher']['deals']['is_all_outlet']??0;
			$id_brand 	= $code['promo_campaign']['id_brand']??$code['subscription_user']['subscription']['id_brand']??$code['deal_voucher']['deals']['id_brand']??null;
			$promo_outlet 	= $code['promo_campaign']['promo_campaign_outlets']??$code['deal_voucher']['deals']['outlets_active']??$code['subscription_user']['subscription']['outlets_active']??[];

			$check_outlet = $pct->checkOutletRule($post['id_outlet'], $all_outlet, $promo_outlet, $id_brand);

			if ($check_outlet) {
                $applied_product = app($this->promo_campaign)->getProduct($source, ($code['promo_campaign'] ?? $code['deal_voucher']['deals'] ?? $code['subscription_user']['subscription']))['applied_product'] ?? [];

                if ($applied_product == '*') { // all product
                    foreach ($products as $key => $value) {

                    	$check = in_array($id_brand, array_column($value->brand_category->toArray(), 'id_brand'));

                    	if ($check || !isset($id_brand)) {
                        	$products[$key]['is_promo'] = 1;
                    	}
                    }
                } else {
                    if (isset($applied_product[0])) { // tier || buy x get y
                        foreach ($applied_product as $key => $value) {
                            foreach ($products as $key2 => $value2) {
                                if ($value2['id_product'] == $value['id_product']) {
                                	$check = in_array($id_brand, array_column($value2->brand_category->toArray(), 'id_brand'));

                    				if ($check || !isset($id_brand)) {
	                                    $products[$key2]['is_promo'] = 1;
	                                    break;
	                                }
                                }
                            }
                        }
                    } elseif (isset($applied_product['id_product'])) { // selected product discount
                        foreach ($products as $key2 => $value2) {
                            if ($value2['id_product'] == $applied_product['id_product']) {
                            	$check = in_array($id_brand, array_column($value2->brand_category->toArray(), 'id_brand'));

                    			if ($check || !isset($id_brand)) {
	                                $products[$key2]['is_promo'] = 1;
	                                break;
	                            }
                            }
                        }
                    }
                }
			}
        } elseif (
            (!empty($post['promo_code']) && !empty($post['id_deals_user'])) ||
            (!empty($post['id_subscription_user']) && !empty($post['id_deals_user'])) ||
            (!empty($post['promo_code']) && !empty($post['id_subscription_user']))
        ) {
            $promo_error = 'Can only use Subscription, Promo Code, or Voucher';
        }
        return $products;
        // end promo code
    }

    public function listCategoryCustomerApps()
    {
        $result = [];
        $list = ProductCategory::where('id_parent_category', null)->orderBy('product_category_order')->select('id_product_category', 'product_category_name', 'product_category_photo')->get()->toArray();

        foreach ($list as $key => $value) {
            $child = ProductCategory::where('id_parent_category', $value['id_product_category'])->select('id_product_category', 'product_category_name', 'product_category_photo')->orderBy('product_category_order')->get()->toArray();
            $list[$key]['childs'] = $child;
            foreach ($child as $index=>$c){
                $childChild = ProductCategory::where('id_parent_category', $c['id_product_category'])->select('id_product_category', 'product_category_name', 'product_category_photo')->orderBy('product_category_order')->get()->toArray();
                $list[$key]['childs'][$index]['childs'] = $childChild;
            }
        }

        return response()->json(MyHelper::checkGet($list));
    }

}
