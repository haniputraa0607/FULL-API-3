<?php

namespace Modules\Product\Http\Controllers;

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
use Validator;
use Hash;
use DB;
use Mail;

use Modules\Product\Http\Requests\category\CreateProduct;
use Modules\Product\Http\Requests\category\UpdateCategory;
use Modules\Product\Http\Requests\category\DeleteCategory;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;

class ApiCategoryController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo                   = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
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
    function create(CreateProduct $request)
    {

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
    function update(UpdateCategory $request)
    {
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
    function delete(DeleteCategory $request)
    {

        $id = $request->json('id_product_category');

        if (($this->checkDeleteParent($id)) && ($this->checkDeleteProduct($id))) {
            // info
            $dataCategory = ProductCategory::where('id_product_category', $request->json('id_product_category'))->get()->toArray();

            if (empty($dataCategory)) {
                return response()->json(MyHelper::checkGet($dataCategory));
            }

            $delete = ProductCategory::where('id_product_category', $id)->delete();

            // delete file
            MyHelper::deletePhoto($dataCategory[0]['product_category_photo']);

            return response()->json(MyHelper::checkDelete($delete));
        } else {
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
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description',
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
                        WHEN (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . '  order by id_product_detail desc limit 1)
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

        $promo_data = $this->applyPromo($post, $products, $promo_error);

        if ($promo_data) {
            $products = $promo_data;
        }

        // grouping by id
        $result = [];
        foreach ($products as $product) {
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
                            $promo_category['product_category_order'] -= 1000000;
                            $result[$pivot['id_brand']]['promo' . $id_product_promo_category]['category'] = $promo_category;
                        }
                        $result[$pivot['id_brand']]['promo' . $id_product_promo_category]['list'][] = $product;
                    }
                }
            }
        }
        // get detail of every key
        foreach ($result as $id_brand => $categories) {
            foreach ($categories as $id_category => $products) {
                if (!is_numeric($id_category)) {
                    // berarti ini promo category
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
            $brand = Brand::select('id_brand', 'name_brand', 'code_brand', 'order_brand')->find($id_brand);
            $result[$id_brand] = [
                'brand' => $brand,
                'list' => $categories
            ];
        }
        usort($result, function ($a, $b) {
            return $a['brand']['order_brand'] <=> $b['brand']['order_brand'];
        });

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
            'brand_product.id_product_category', 'brand_product.id_brand',
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
                        WHEN (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $post['id_outlet'] . ' )
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

        $result = [];
        foreach ($products as $product) {
            $product->append('photo');
            $product['id_outlet'] = $post['id_outlet'];
            $result[$product->id_product_category]['list'][] = $product;
            if (!isset($result[$product->id_product_category]['category'])) {
                $result[$product->id_product_category]['category'] = ProductCategory::select('id_product_category', 'product_category_name')->find($product->id_product_category);
            }
        }
        return MyHelper::checkGet(array_values($result));
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
                $code = app($this->promo_campaign)->checkPromoCode($post['promo_code'], null, 1);
                $source = 'promo_campaign';
                $id_brand = $code->id_brand;
            } elseif (!empty($post['id_deals_user'])) {
                $code = app($this->promo_campaign)->checkVoucher($post['id_deals_user'], null, 1);
                $source = 'deals';
                $id_brand = $code->dealVoucher->deals->id_brand;
            } elseif (!empty($post['id_subscription_user'])) {
                $code = app($this->subscription_use)->checkSubscription($post['id_subscription_user'], null, 1, 1);
                $source = 'subscription';
                $id_brand = $code->subscription_user->subscription->id_brand;
            }

            if (!$code) {
                $promo_error = 'Promo not valid';
                return false;
            } else {

                if (($code['promo_campaign']['date_end'] ?? $code['voucher_expired_at'] ?? $code['subscription_expired_at']) < date('Y-m-d H:i:s')) {
                    $promo_error = 'Promo is ended';
                    return false;
                }
                $code = $code->toArray();

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
}
