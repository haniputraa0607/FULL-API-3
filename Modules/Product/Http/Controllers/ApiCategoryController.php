<?php

namespace Modules\Product\Http\Controllers;

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
use Validator;
use Hash;
use DB;
use Mail;

use Modules\Product\Http\Requests\category\CreateProduct;
use Modules\Product\Http\Requests\category\UpdateCategory;
use Modules\Product\Http\Requests\category\DeleteCategory;

class ApiCategoryController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/product/category/";

    /**
     * check inputan
     */
    function checkInputCategory($post=[], $type="update") {
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
            }
            else {
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
        }
        else {
            // khusus create
            if ($type == "create") {
                if (isset($post['id_parent_category'])) {
                    $data['product_category_order'] = $this->searchLastSorting($post['id_parent_category']);
                }
                else {
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
    function create(CreateProduct $request) {

        $post = $request->json()->all();
        $data = $this->checkInputCategory($post, "create");

        if (isset($data['error'])) {
            unset($data['error']);

            return response()->json($data);
        }

        // create
        $create = ProductCategory::create($data);

        return response()->json(MyHelper::checkCreate($create));
    }

    /**
     * cari urutan ke berapa
     */
    function searchLastSorting($id_parent_category=null) {
        $sorting = ProductCategory::select('product_category_order')->orderBy('product_category_order', 'DESC');

        if (is_null($id_parent_category)) {
            $sorting->whereNull('id_parent_category');
        }
        else {
            $sorting->where('id_parent_category', $id_parent_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        }
        else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->product_category_order)) {
                return 1;
            }
            else {
                $sorting = $sorting->product_category_order + 1;
                return $sorting;
            }
        }
    }

    /**
     * update category
     */
    function update(UpdateCategory $request) {
        // info
        $dataCategory = ProductCategory::where('id_product_category', $request->json('id_product_category'))->get()->toArray();

        if (empty($dataCategory)) {
            return response()->json(MyHelper::checkGet($dataCategory));
        }

        $post = $request->json()->all();

        $data = $this->checkInputCategory($post);

        if (isset($data['error'])) {
            unset($data['error']);

            return response()->json($data);
        }

        // update
        $update = ProductCategory::where('id_product_category', $post['id_product_category'])->update($data);

        // hapus file
        if (isset($data['product_category_photo'])) {
            MyHelper::deletePhoto($dataCategory[0]['product_category_photo']);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * delete (main)
     */
    function delete(DeleteCategory $request) {

        $id = $request->json('id_product_category');

        if ( ($this->checkDeleteParent($id)) && ($this->checkDeleteProduct($id)) ) {
            // info
            $dataCategory = ProductCategory::where('id_product_category', $request->json('id_product_category'))->get()->toArray();

            if (empty($dataCategory)) {
                return response()->json(MyHelper::checkGet($dataCategory));
            }

            $delete = ProductCategory::where('id_product_category', $id)->delete();

            // delete file
            MyHelper::deletePhoto($dataCategory[0]['product_category_photo']);

            return response()->json(MyHelper::checkDelete($delete));
        }
        else {
            $result = [
                'status' => 'fail',
                'messages' => ['category has been used.']
            ];

            return response()->json($result);
        }
    }

    /**
     * delete check digunakan sebagai parent
     */
    function checkDeleteParent($id) {
        $check = ProductCategory::where('id_parent_category', $id)->count();

        if ($check == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * delete check digunakan sebagai product
     */
    function checkDeleteProduct($id) {
        $check = Product::where('id_product_category', $id)->count();

        if ($check == 0) {
            return true;
        }
        else {
            return false;
        }
        return true;
    }

    /**
     * list non tree
     * bisa by id parent category
     */
    function listCategory(Request $request) {
        $post = $request->json()->all();

        if (!empty($post)) {
            $list = $this->getData($post);
        } else {
            $list = ProductCategory::where('id_parent_category', null)->orderBy('product_category_order')->get();

            foreach ($list as $key => $value) {
                $child = ProductCategory::where('id_parent_category', $value['id_product_category'])->orderBy('product_category_order')->get();
                $list[$key]['child'] = $child;
            }
        }

        return response()->json(MyHelper::checkGet($list));
    }

    /**
     * list tree
     * bisa by id parent category
     */
    function listCategoryTreeX(Request $request) {
        $post = $request->json()->all();

        $category = $this->getData($post);

        if (!empty($category)) {
            $category = $this->createTree($category, $post);
        }

		if(isset($post['id_outlet'])){
			$uncategorized = Product::join('product_prices','product_prices.id_product','=','products.id_product')
                                    ->where('product_prices.id_outlet','=',$post['id_outlet'])
                                    ->where(function($query){
                                        $query->where('product_prices.product_visibility','=','Visible')
                                                ->orWhere(function($q){
                                                    $q->whereNull('product_prices.product_visibility')
                                                    ->where('products.product_visibility', 'Visible');
                                                });
                                    })
                                    ->where('product_prices.product_status','=','Active')
                                    ->whereNotNull('product_prices.product_price')
									->whereNull('products.id_product_category')
									->with(['photos'])
                                    ->orderBy('products.position')
									->get()
									->toArray();

		} else {
			$defaultoutlet = Setting::where('key','=','default_outlet')->first();
			$uncategorized = Product::join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$defaultoutlet['value'])
                                    ->where(function($query){
                                        $query->where('product_prices.product_visibility','=','Visible')
                                                ->orWhere(function($q){
                                                    $q->whereNull('product_prices.product_visibility')
                                                    ->where('products.product_visibility', 'Visible');
                                                });
                                    })
                                    ->where('product_prices.product_status','=','Active')
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
                }else{
                    foreach($value['product'] as $index => $prod){
                        if(count($prod['photos']) < 1){
                            $value['product'][$index]['photos'][] = [
                                "id_product_photo" => 0,
                                "id_product" => $prod['id_product'],
                                "product_photo" => 'img/product/item/default.png',
                                "created_at" => $prod['created_at'],
                                "updated_at" => $prod['updated_at'],
                                "url_product_photo" => env('S3_URL_API').'img/product/item/default.png'
                            ];
                        }
                    }
                    $dataCategory[] = $value;
                }
            }
        }

        $result['categorized'] = $dataCategory;

        if(!isset($post['id_product_category'])){
            $result['uncategorized_name'] = "Product";
            $result['uncategorized'] = $uncategorized;
        }

        return response()->json(MyHelper::checkGet($result));
    }

    /**
     * list tree
     * bisa by id parent category and id brand
     */
    function listCategoryTree(Request $request) {
        $post = $request->json()->all();
        if(!($post['id_outlet']??false)){
            $post['id_outlet'] = Setting::where('key','default_outlet')->pluck('value')->first();
        }
        $products = Product::select([
                'products.id_product','products.product_name','products.product_description',
                'product_prices.product_price','product_prices.product_stock_status'
            ])
            ->join('brand_product','brand_product.id_product','=','products.id_product')
            // produk tersedia di outlet
            ->join('product_prices','product_prices.id_product','=','products.id_product')
            ->where('product_prices.id_outlet','=',$post['id_outlet'])
            // brand produk ada di outlet
            ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
            ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
            ->where(function($query){
                $query->where('product_prices.product_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_prices.product_visibility')
                            ->where('products.product_visibility', 'Visible');
                        });
            })
            ->where('product_prices.product_status','=','Active')
            ->whereNotNull('product_prices.product_price')
            ->with([
                'brand_category',
                'photos'=>function($query){
                    $query->select('id_product','product_photo');
                }
            ])
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->get();
        // grouping by id
        $result = [];
        foreach ($products as $product) {
            $product->append('photo');
            $product = $product->toArray();
            $pivots = $product['brand_category'];
            unset($product['brand_category']);
            unset($product['photos']);
            unset($product['product_prices']);
            foreach ($pivots as $pivot) {
                if($pivot['id_product_category']){
                    $result[$pivot['id_brand']][$pivot['id_product_category']][] = $product;
                }
            }
        }
        // get detail of every key
        foreach ($result as $id_brand => $categories) {
            foreach ($categories as $id_category => $products) {
                $category = ProductCategory::select('id_product_category','product_category_name')->find($id_category);
                $categories[$id_category] = [
                    'category' => $category,
                    'list' =>$products
                ];
            }
            $brand = Brand::select('id_brand','name_brand')->find($id_brand);
            $result[$id_brand] = [
                'brand' => $brand,
                'list' => array_values($categories)
            ];
        }
        $result = array_values($result);

        return response()->json(MyHelper::checkGet($result));
    }

    public function search(Request $request) {
        $post = $request->except('_token');
        if(!($post['id_outlet']??false)){
            $post['id_outlet'] = Setting::where('key','default_outlet')->pluck('value')->first();
        }
        $products = Product::select([
                'products.id_product','products.product_name','products.product_description',
                'product_prices.product_price','product_prices.product_stock_status',
                'brand_product.id_product_category','brand_product.id_brand'
            ])
            ->join('brand_product','brand_product.id_product','=','products.id_product')
            // produk tersedia di outlet
            ->join('product_prices','product_prices.id_product','=','products.id_product')
            ->where('product_prices.id_outlet','=',$post['id_outlet'])
            // brand produk ada di outlet
            ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
            ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
            // cari produk
            ->where('products.product_name','like','%'.$post['product_name'].'%')
            ->where(function($query){
                $query->where('product_prices.product_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_prices.product_visibility')
                            ->where('products.product_visibility', 'Visible');
                        });
            })
            ->where('product_prices.product_status','=','Active')
            ->whereNotNull('product_prices.product_price')
            ->whereNotNull('brand_product.id_product_category')
            ->with([
                'photos'=>function($query){
                    $query->select('id_product','product_photo');
                }
            ])
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->get();
        $result = [];
        foreach ($products as $product) {
            $product->append('photo');
            $result[$product->id_product_category]['list'][] = $product;
            if(!isset($result[$product->id_product_category]['category'])){
                $result[$product->id_product_category]['category']=ProductCategory::select('id_product_category','product_category_name')->find($product->id_product_category);
            }
        }
        return MyHelper::checkGet(array_values($result));
    }

    function getData($post=[]) {
        // $category = ProductCategory::select('*', DB::raw('if(product_category_photo is not null, (select concat("'.env('S3_URL_API').'", product_category_photo)), "'.env('S3_URL_API').'assets/pages/img/noimg-500-375.png") as url_product_category_photo'));
        $category = ProductCategory::with(['parentCategory'])->select('*');

        if (isset($post['id_parent_category'])) {

            if (is_null($post['id_parent_category']) || $post['id_parent_category'] == 0) {
                $category->master();
            }
            else {
                $category->parents($post['id_parent_category']);
            }
        }else{
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

    public function createTree($root, $post=[]){
        // print_r($root); exit();
        $node = [];

        foreach($root as $i => $r){
            $child = $this->getData(['id_parent_category' => $r['id_product_category']]);
            if(count($child) > 0){
                $r['child'] = $this->createTree($child, $post);
            }
            else {
                $r['child'] = [];
            }

            $product = $this->getDataProduk($r['id_product_category'], $post);
            $r['product_count'] = count($product);
            $r['product'] = $product;

            array_push($node,$r);
        }
        return $node;
    }

    public function getDataProduk($id, $post=[]) {
        if (isset($post['id_outlet'])) {
			$product = Product::select('products.*', 'product_prices.product_price', 'product_prices.product_visibility', 'product_prices.product_status', 'product_prices.product_stock_status', 'product_prices.id_outlet')->join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$post['id_outlet'])
                                    ->where(function($query){
                                        $query->where('product_prices.product_visibility','=','Visible')
                                                ->orWhere(function($q){
                                                    $q->whereNull('product_prices.product_visibility')
                                                    ->where('products.product_visibility', 'Visible');
                                                });
                                    })
									->where('product_prices.product_status','=','Active')
									->whereNotNull('product_prices.product_price')
									->where('products.id_product_category', $id)
									->with(['photos'])
                                    ->orderBy('products.position')
                                    ->get();

        } else {
			$defaultoutlet = Setting::where('key','=','default_outlet')->first();
			$product = Product::select('products.*', 'product_prices.product_price', 'product_prices.product_visibility', 'product_prices.product_status', 'product_prices.product_stock_status')->join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$defaultoutlet['value'])
                                    ->where(function($query){
                                        $query->where('product_prices.product_visibility','=','Visible')
                                                ->orWhere(function($q){
                                                    $q->whereNull('product_prices.product_visibility')
                                                    ->where('products.product_visibility', 'Visible');
                                                });
                                    })
                                    ->where('product_prices.product_status','=','Active')
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
            $update = ProductCategory::find($category_id)->update(['product_category_order'=>$key+1]);
        }

        return ['status' => 'success'];
    }

    public function getAllCategory(){
        $data = ProductCategory::orderBy('product_category_name')->get();
        return response()->json(MyHelper::checkGet($data));
    }

}
