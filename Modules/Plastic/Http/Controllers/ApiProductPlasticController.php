<?php

namespace Modules\Plastic\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Setting;
use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\Plastic\Entities\PlasticTypeOutlet;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Validator;
use Hash;
use DB;
use Mail;

class ApiProductPlasticController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index()
    {
        $data = Product::where('product_type', 'plastic')
            ->leftJoin('plastic_type', 'products.id_plastic_type', 'plastic_type.id_plastic_type')
            ->select('products.*', 'plastic_type.plastic_type_name')
            ->get()->toArray();

        foreach ($data as $key => $dt) {
            $globalPrice = ProductGlobalPrice::where('id_product', $dt['id_product'])->first();
            $data[$key]['global_price'] = number_format($globalPrice['product_global_price']) ?? "";
        }
        return response()->json(MyHelper::checkGet($data));
    }

    public function store(Request $request)
    {
        $post = $request->json()->all();
        if (
            isset($post['product_code']) && !empty($post['product_code'])
            && isset($post['product_name']) && !empty($post['product_name'])
            && isset($post['product_capacity']) && !empty($post['product_capacity'])
        ) {
            $price = $post['global_price'];
            unset($post['global_price']);

            $check = Product::where('product_code', $post['product_code'])->first();
            if (!empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Product code already exist']]);
            }

            $post['product_name_pos'] = " ";
            $create = Product::create($post);

            if ($create && !empty($price)) {
                ProductGlobalPrice::updateOrCreate(
                    ['id_product' => $create['id_product']],
                    ['product_global_price' => str_replace(".", "", $price)]
                );
            }
            return response()->json(MyHelper::checkCreate($create));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_product']) && !empty($post['id_product'])) {
            $detail = Product::where('id_product', $post['id_product'])->first();
            if (!empty($detail)) {
                $globalPrice = ProductGlobalPrice::where('id_product', $post['id_product'])->first();
                $detail['global_price'] = number_format($globalPrice['product_global_price']) ?? null;
            }

            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();
        if (
            isset($post['id_product']) && !empty($post['id_product'])
            && isset($post['product_name']) && !empty($post['product_name'])
            && isset($post['product_capacity']) && !empty($post['product_capacity'])
        ) {
            $price = $post['global_price'];
            unset($post['global_price']);

            $post['product_name_pos'] = " ";
            $create = Product::where('id_product', $post['id_product'])->update($post);

            if ($create && !empty($price)) {
                $price = str_replace(".", "", $price);
                $price = str_replace(",", "", $price);

                ProductGlobalPrice::updateOrCreate(
                    ['id_product' => $post['id_product']],
                    ['id_product' => $post['id_product'], 'product_global_price' => $price]
                );
            }
            return response()->json(MyHelper::checkCreate($create));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }
    }

    public function destroy(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_product']) && !empty($post['id_product'])) {
            $check = TransactionProduct::where('id_product', $post['id_product'])->first();
            if (!empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Product already use']]);
            }

            $delete = Product::where('id_product', $post['id_product'])->delete();
            ProductGlobalPrice::where('id_product', $post['id_product'])->delete();
            ProductSpecialPrice::where('id_product', $post['id_product'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function visibility(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_product']) && !empty($post['id_product'])) {
            $update = Product::where('id_product', $post['id_product'])->update(['product_visibility' => $post['product_visibility']]);
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function exportProduct(Request $request)
    {
        $post = $request->json()->all();

        $data = Product::where('product_type', 'product')
                ->where('product_variant_status', 0)
                ->select('product_code', 'product_name', 'plastic_used as total_use_plastic');
        $dataBrand = [];
        if (isset($post['id_brand']) && !empty($post['id_brand'])) {
            $dataBrand = Brand::where('brands.id_brand', $post['id_brand'])->first();
            $data = $data->join('brand_product', 'brand_product.id_product', 'products.id_product')
                ->join('brands', 'brand_product.id_brand', 'brands.id_brand')
                ->where('brands.id_brand', $post['id_brand']);
        }
        $data = $data->get()->toArray();

        if (!empty($data)) {
            return response()->json([
                'status' => 'success',
                'result' => [
                    'brand' => $dataBrand,
                    'products' => $data
                ]
            ]);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['empty']]);
        }
    }

    public function importProduct(Request $request)
    {
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'invalid' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'] ?? [];

        foreach ($data as $key => $value) {
            if (empty($value['product_code'])) {
                $result['invalid']++;
                continue;
            }

            $product = Product::where('product_code', $value['product_code'])->first();

            if ($product) {
                $update = Product::where('id_product', $product['id_product'])->update(['plastic_used' => $value['total_use_plastic'] ?? 0]);

                if ($update) {
                    $result['updated']++;
                    continue;
                } else {
                    $result['failed']++;
                    continue;
                }
            } else {
                $result['not_found']++;
                $result['more_msg_extended'][] = "Product with code {$value['product_code']} not found";
                continue;
            }
        }

        $response = [];

        if ($result['invalid']) {
            $response[] = $result['invalid'] . ' invalid data';
        }
        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' product';
        }
        if ($result['not_found']) {
            $response[] = $result['no_update'] . ' product not found';
        }
        if ($result['failed']) {
            $response[] = 'Failed update ' . $result['failed'] . ' product';
        }
        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function exportProductVariant(Request $request)
    {
        $post = $request->json()->all();
        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->select(
                'products.id_product',
                'products.product_name',
                'products.product_code',
                'product_variant_groups.product_variant_groups_plastic_used',
                'product_variant_groups.product_variant_group_code',
                'product_variant_groups.id_product_variant_group'
            )
            ->where('product_variant_status', 1)
            ->where('product_visibility', 'Visible')
            ->orderBy('products.product_code', 'asc')
            ->with(['product_variant_pivot']);

        $dataBrand = [];
        if (isset($post['id_brand']) && !empty($post['id_brand'])) {
            $dataBrand = Brand::where('brands.id_brand', $post['id_brand'])->first();
            $data = $data->join('brand_product', 'brand_product.id_product', 'products.id_product')
                ->join('brands', 'brand_product.id_brand', 'brands.id_brand')
                ->where('brands.id_brand', $post['id_brand']);
        }
        $data = $data->get()->toArray();

        $arrProductVariant = [];
        foreach ($data as $key => $pv) {
            $arr = array_column($pv['product_variant_pivot'], 'product_variant_name');
            $name = implode(',', $arr);
            $arrProductVariant[$key] = [
                'product' => $pv['product_code'] . ' - ' . $pv['product_name'],
                'product_variant_code' => $pv['product_variant_group_code'],
                'product_variant' => $name,
                'total_use_plastic' => $pv['product_variant_groups_plastic_used']
            ];
        }

        if ($arrProductVariant) {
            return response()->json([
                'status' => 'success',
                'result' => [
                    'brand' => $dataBrand,
                    'products_variant' => $arrProductVariant
                ]
            ]);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['empty']]);
        }
    }

    public function importProductVariant(Request $request)
    {
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'invalid' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'] ?? [];

        foreach ($data as $key => $value) {
            if (empty($value['product_variant_code'])) {
                $result['invalid']++;
                continue;
            }

            $productVariantGroup = ProductVariantGroup::where('product_variant_group_code', $value['product_variant_code'])->first();

            if ($productVariantGroup) {
                $update = ProductVariantGroup::where('id_product_variant_group', $productVariantGroup['id_product_variant_group'])->update(['product_variant_groups_plastic_used' => $value['total_use_plastic'] ?? 0]);

                if ($update) {
                    $result['updated']++;
                    continue;
                } else {
                    $result['failed']++;
                    continue;
                }
            } else {
                $result['not_found']++;
                $result['more_msg_extended'][] = "Product Variant Group with code {$value['product_code']} not found";
                continue;
            }
        }

        $response = [];

        if ($result['invalid']) {
            $response[] = $result['invalid'] . ' invalid data';
        }
        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' product';
        }
        if ($result['not_found']) {
            $response[] = $result['no_update'] . ' product not found';
        }
        if ($result['failed']) {
            $response[] = 'Failed update ' . $result['failed'] . ' product';
        }
        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function exportProductPlaticPrice()
    {
        $different_outlet = Outlet::select('outlet_code', 'id_product', 'product_special_price.product_special_price as product_price')
            ->leftJoin('product_special_price', 'outlets.id_outlet', '=', 'product_special_price.id_outlet')
            ->where('outlet_different_price', 1)->get();
        $do = MyHelper::groupIt($different_outlet, 'outlet_code', null, function ($key, &$val) {
            $val = MyHelper::groupIt($val, 'id_product');
            return $key;
        });

        $data['products'] = Product::select('products.id_product', 'product_code as product_plastic_code', 'product_name as product_plastic_name', 'product_global_price.product_global_price as global_price')
            ->leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
            ->where('product_type', 'plastic')
            ->orderBy('position')
            ->orderBy('products.id_product')
            ->distinct()
            ->get();

        foreach ($data['products'] as $key => &$product) {
            $inc = 0;
            foreach ($do as $outlet_code => $x) {
                $inc++;
                $product['price_' . $outlet_code] = $x[$product['id_product']][0]['product_price'] ?? '';
                if ($inc === count($do)) {
                    unset($product['id_product']);
                }
            }
        }

        return MyHelper::checkGet($data);
    }

    public function importProductPlaticPrice(Request $request)
    {
        $post = $request->json()->all();
        $result = [
            'processed' => 0,
            'invalid' => 0,
            'updated' => 0,
            'updated_price' => 0,
            'updated_price_fail' => 0,
            'create' => 0,
            'create_category' => 0,
            'no_update' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0] ?? [];

        foreach ($data as $key => $value) {
            if (empty($value['product_plastic_code'])) {
                $result['invalid']++;
                continue;
            }
            $result['processed']++;
            if (empty($value['product_plastic_name'])) {
                unset($value['product_plastic_name']);
            }
            if (empty($value['global_price'])) {
                unset($value['global_price']);
            }

            $product = Product::where('product_code', $value['product_plastic_code'])->first();

            if (!$product) {
                $result['not_found']++;
                $result['more_msg_extended'][] = "Product with product code {$value['product_plastic_code']}";
                continue;
            }

            if ($value['global_price'] ?? false) {
                $pp = ProductGlobalPrice::where([
                    'id_product' => $product->id_product
                ])->first();
                if ($pp) {
                    $update = $pp->update(['product_global_price' => $value['global_price']]);
                } else {
                    $update = ProductGlobalPrice::create([
                        'id_product' => $product->id_product,
                        'product_global_price' => $value['global_price']
                    ]);
                }
                if ($update) {
                    $result['updated_price']++;
                } else {
                    if ($update !== 0) {
                        $result['updated_price_fail']++;
                        $result['more_msg_extended'][] = "Failed set price for product {$value['product_plastic_code']}";
                    }
                }
            }
            foreach ($value as $col_name => $col_value) {
                if (!$col_value) {
                    continue;
                }
                if (strpos($col_name, 'price_') !== false) {
                    $outlet_code = str_replace('price_', '', $col_name);
                    $pp = ProductSpecialPrice::join('outlets', 'outlets.id_outlet', '=', 'product_special_price.id_outlet')
                        ->where([
                            'outlet_code' => $outlet_code,
                            'id_product' => $product->id_product
                        ])->first();
                    if ($pp) {
                        $update = $pp->update(['product_special_price' => $col_value]);
                    } else {
                        $id_outlet = Outlet::select('id_outlet')->where('outlet_code', $outlet_code)->pluck('id_outlet')->first();
                        if (!$id_outlet) {
                            $result['updated_price_fail']++;
                            $result['more_msg_extended'][] = "Failed create new price for product {$value['product_plastic_code']} at outlet $outlet_code failed";
                            continue;
                        }
                        $update = ProductSpecialPrice::create([
                            'id_outlet' => $id_outlet,
                            'id_product' => $product->id_product,
                            'product_special_price' => $col_value
                        ]);
                    }
                    if ($update) {
                        $result['updated_price']++;
                    } else {
                        $result['updated_price_fail']++;
                        $result['more_msg_extended'][] = "Failed set price for product {$value['product_plastic_code']} at outlet $outlet_code failed";
                    }
                }
            }
        }

        $response = [];
        if ($result['invalid'] + $result['processed'] <= 0) {
            return MyHelper::checkGet([], 'File empty');
        } else {
            $response[] = $result['invalid'] + $result['processed'] . ' total data found';
        }
        if ($result['processed']) {
            $response[] = $result['processed'] . ' data processed';
        }
        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' product';
        }
        if ($result['create']) {
            $response[] = 'Create ' . $result['create'] . ' new product';
        }
        if ($result['create_category']) {
            $response[] = 'Create ' . $result['create_category'] . ' new category';
        }
        if ($result['no_update']) {
            $response[] = $result['no_update'] . ' product not updated';
        }
        if ($result['invalid']) {
            $response[] = $result['invalid'] . ' row data invalid';
        }
        if ($result['failed']) {
            $response[] = 'Failed create ' . $result['failed'] . ' product';
        }
        if ($result['not_found']) {
            $response[] = $result['not_found'] . ' product not found';
        }
        if ($result['updated_price']) {
            $response[] = 'Update ' . $result['updated_price'] . ' product price';
        }
        if ($result['updated_price_fail']) {
            $response[] = 'Update ' . $result['updated_price_fail'] . ' product price fail';
        }
        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function exportPlaticStatusOutlet()
    {
        $outlets = Outlet::select(
            'outlets.outlet_code',
            'outlets.outlet_name',
            'outlets.plastic_used_status as plastic_status'
        )->get()->toArray();

        foreach ($outlets as $key => $o) {
            unset($outlets[$key]['call']);
            unset($outlets[$key]['url']);
        }

        return response()->json(MyHelper::checkGet($outlets));
    }

    public function importPlaticStatusOutlet(Request $request)
    {
        $post = $request->json()->all();
        $result = [
            'invalid' => 0,
            'updated' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0] ?? [];

        foreach ($data as $key => $value) {
            if (empty($value['outlet_code'])) {
                $result['invalid']++;
                continue;
            }

            $outlet = Outlet::where('outlet_code', $value['outlet_code'])->first();

            if (!$outlet) {
                $result['not_found']++;
                $result['more_msg_extended'][] = "Outlet not found with code {$value['outlet_code']}";
                continue;
            }

            $update = Outlet::where('id_outlet', $outlet['id_outlet'])->update(['plastic_used_status' => $value['plastic_status'] ?? 'Inactive']);
            if ($update) {
                $result['updated']++;
            } else {
                $result['failed']++;
                $result['more_msg_extended'][] = "Failed set plastic status for outlet {$value['outlet_code']}";
            }
        }

        $response = [];

        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' product';
        }
        if ($result['invalid']) {
            $response[] = $result['invalid'] . ' row data invalid';
        }
        if ($result['not_found']) {
            $response[] = $result['not_found'] . ' product not found';
        }
        if ($result['failed']) {
            $response[] = 'Failed create ' . $result['failed'] . ' product';
        }

        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function listProductByOutlet(Request $request)
    {
        $post = $request->json()->all();
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();

        if ($outlet['plastic_used_status'] == 'Inactive') {
            return response()->json(MyHelper::checkGet([]));
        }

        $plastic_type = PlasticTypeOutlet::join('plastic_type', 'plastic_type.id_plastic_type', 'plastic_type_outlet.id_plastic_type')
            ->groupBy('plastic_type_outlet.id_plastic_type')
            ->where('id_outlet', $outlet['id_outlet'])->orderBy('plastic_type_order', 'asc')->first();

        $plastics = [];
        if ($plastic_type['id_plastic_type'] ?? null) {
            $plastics = Product::where('product_type', 'plastic')
                ->leftJoin('product_detail', 'products.id_product', 'product_detail.id_product')
                ->join('plastic_type', 'plastic_type.id_plastic_type', 'products.id_plastic_type')
                ->where(function ($sub) use ($outlet) {
                    $sub->whereNull('product_detail.id_outlet')
                        ->orWhere('product_detail.id_outlet', $outlet['id_outlet']);
                })
                ->where('products.id_plastic_type', $plastic_type['id_plastic_type'])
                ->where('product_visibility', 'Visible')
                ->select(
                    'plastic_type_name',
                    'products.id_product',
                    'products.product_code',
                    'products.product_name',
                    DB::raw('(CASE WHEN product_detail.product_detail_stock_status is NULL THEN "Available"
                        ELSE product_detail.product_detail_stock_status END) as product_stock_status')
                )->paginate(10);
        }

        return response()->json(MyHelper::checkGet($plastics));
    }

    public function updateStock(Request $request)
    {
        $post = $request->json()->all();
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();

        if (isset($post['sameall']) && !empty($post['sameall'])) {
            $plastic_type = PlasticTypeOutlet::join('plastic_type', 'plastic_type.id_plastic_type', 'plastic_type_outlet.id_plastic_type')
                ->groupBy('plastic_type_outlet.id_plastic_type')
                ->where('id_outlet', $outlet['id_outlet'])->orderBy('plastic_type_order', 'asc')->first();

            $plastics = [];
            if ($plastic_type['id_plastic_type'] ?? null) {
                $plastics = Product::where('product_type', 'plastic')
                    ->leftJoin('product_detail', 'products.id_product', 'product_detail.id_product')
                    ->join('plastic_type', 'plastic_type.id_plastic_type', 'products.id_plastic_type')
                    ->where(function ($sub) use ($outlet) {
                        $sub->whereNull('product_detail.id_outlet')
                            ->orWhere('product_detail.id_outlet', $outlet['id_outlet']);
                    })
                    ->where('products.id_plastic_type', $plastic_type['id_plastic_type'])
                    ->where('product_visibility', 'Visible')
                    ->pluck('products.id_product')->toArray();

                foreach ($plastics as $id_product) {
                    $product = ProductDetail::where([
                        'id_product' => $id_product,
                        'id_outlet'  => $outlet['id_outlet']
                    ])->first();

                    if (($post['product_stock_status'] ?? false) && (($post['product_stock_status'] ?? false) != $product['product_detail_stock_status'] ?? false)) {
                        $create = ProductStockStatusUpdate::create([
                            'id_product' => $id_product,
                            'id_user' => $post['id_user'],
                            'user_type' => 'users',
                            'id_outlet' => $outlet['id_outlet'],
                            'date_time' => date('Y-m-d H:i:s'),
                            'new_status' => $post['product_stock_status'],
                            'id_outlet_app_otp' => null
                        ]);
                    }

                    $save = ProductDetail::updateOrCreate([
                        'id_product' => $id_product,
                        'id_outlet'  => $outlet['id_outlet']
                    ], [
                        'product_detail_visibility'  => 'Visible',
                        'product_detail_stock_status' => $post['product_stock_status']]);
                }
            }
        } else {
            $product = ProductDetail::where([
                'id_product' => $post['id_product'],
                'id_outlet'  => $outlet['id_outlet']
            ])->first();

            if (($post['product_stock_status'] ?? false) && (($post['product_stock_status'] ?? false) != $product['product_detail_stock_status'] ?? false)) {
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $post['id_user'],
                    'user_type' => 'users',
                    'id_outlet' => $outlet['id_outlet'],
                    'date_time' => date('Y-m-d H:i:s'),
                    'new_status' => $post['product_stock_status'],
                    'id_outlet_app_otp' => null
                ]);
            }

            $save = ProductDetail::updateOrCreate([
                'id_product' => $post['id_product'],
                'id_outlet'  => $outlet['id_outlet']
            ], [
                'product_detail_visibility'  => 'Visible',
                'product_detail_stock_status' => $post['product_stock_status']]);
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function listUsePlasticProduct(Request $request)
    {
        $post = $request->json()->all();

        $data = Product::where('product_type', 'product')
                ->where('product_variant_status', 0)
                ->select('id_product', 'product_code', 'product_name', 'plastic_used as total_use_plastic');

        if (isset($post['rule']) && !empty($post['rule'])) {
            $rule = $post['operator'] ?? 'and';

            if ($rule == 'and') {
                foreach ($post['rule'] as $condition) {
                    if (!empty($condition['subject']) && isset($condition['operator'])) {
                        if ($condition['operator'] == '=') {
                            $data->where($condition['subject'], $condition['parameter']);
                        } else {
                            $data->where($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                        }
                    }
                }
            } else {
                $data->where(function ($q) use ($post) {
                    foreach ($post['rule'] as $condition) {
                        if (!empty($condition['subject'])) {
                            if ($condition['operator'] == '=' && isset($condition['operator'])) {
                                $q->orWhere($condition['subject'], $condition['parameter']);
                            } else {
                                $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(15);
        return response()->json(MyHelper::checkGet($data));
    }

    public function updateUsePlasticProduct(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['sameall']) && !empty($post['sameall'])) {
            $update = Product::where('product_variant_status', 0)->update(['plastic_used' => $post['plastic_used']]);
        } else {
            $update = Product::where('id_product', $post['id_product'])->update(['plastic_used' => $post['plastic_used']]);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function listUsePlasticProductVariant(Request $request)
    {
        $post = $request->json()->all();

        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->select(
                'product_variant_groups.id_product_variant_group',
                'products.id_product',
                'products.product_name',
                'products.product_code',
                'product_variant_groups.product_variant_groups_plastic_used',
                'product_variant_groups.product_variant_group_code',
                'product_variant_groups.id_product_variant_group'
            )
            ->where('product_variant_status', 1)
            ->orderBy('products.product_code', 'asc')
            ->with(['product_variant_pivot']);

        if (isset($post['rule']) && !empty($post['rule'])) {
            $rule = $post['operator'] ?? 'and';

            if ($rule == 'and') {
                foreach ($post['rule'] as $condition) {
                    if (!empty($condition['subject']) && isset($condition['operator'])) {
                        if ($condition['operator'] == '=') {
                            $data->where($condition['subject'], $condition['parameter']);
                        } else {
                            $data->where($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                        }
                    }
                }
            } else {
                $data->where(function ($q) use ($post) {
                    foreach ($post['rule'] as $condition) {
                        if (!empty($condition['subject']) && isset($condition['operator'])) {
                            if ($condition['operator'] == '=') {
                                $q->orWhere($condition['subject'], $condition['parameter']);
                            } else {
                                $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(15)->toArray();

        foreach ($data['data'] as $key => $pv) {
            $arr = array_column($pv['product_variant_pivot'], 'product_variant_name');
            $name = implode(',', $arr);
            $data['data'][$key]['product_variant_name'] = $name;
        }
        return response()->json(MyHelper::checkGet($data));
    }

    public function updateUsePlasticProductVariant(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['sameall']) && !empty($post['sameall'])) {
            $update = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
                    ->where('product_variant_status', 1)->update(['product_variant_groups_plastic_used' => $post['product_variant_groups_plastic_used']]);
        } else {
            $update = ProductVariantGroup::where('id_product_variant_group', $post['id_product_variant_group'])->update(['product_variant_groups_plastic_used' => $post['product_variant_groups_plastic_used']]);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }
}
