<?php

namespace Modules\ProductBundling\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Setting;
use App\Http\Models\TransactionProduct;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductModifierGroup;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductBundling\Entities\Bundling;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingOutletGroup;
use Modules\ProductBundling\Entities\BundlingPeriodeDay;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductBundling\Entities\BundlingToday;
use Modules\ProductBundling\Http\Requests\CreateBundling;
use DB;
use Modules\ProductBundling\Http\Requests\UpdateBundling;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\Transaction\Entities\TransactionBundlingProduct;

class ApiBundlingController extends Controller
{
    public function __construct()
    {
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
        $this->outlet_group_filter = "Modules\Outlet\Http\Controllers\ApiOutletGroupFilterController";
        $this->bundling      = "Modules\ProductBundling\Http\Controllers\ApiBundlingController";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $bundling = Bundling::with(['bundling_product', 'category']);

        if (
            isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])
        ) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $bundling = $bundling->whereRaw('(DATE(start_date) >= "' . $start_date . '" AND DATE(start_date) <= "' . $end_date . '" AND DATE(end_date) >= "' . $start_date . '" AND DATE(end_date) <= "' . $end_date . '")');
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'bundling_code') {
                            if ($row['operator'] == '=') {
                                $bundling->where('bundling_code', $row['parameter']);
                            } else {
                                $bundling->where('bundling_code', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'bundling_name') {
                            if ($row['operator'] == '=') {
                                $bundling->where('bundling_name', $row['parameter']);
                            } else {
                                $bundling->where('bundling_name', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'bundling_price_before_discount' || $row['subject'] == 'bundling_price_after_discount') {
                            $bundling->where($row['subject'], $row['operator'], $row['parameter']);
                        }

                        if ($row['subject'] == 'product_name') {
                            $bundling->whereIn('bundling.id_bundling', function ($sub) use ($row) {
                                $sub->select('bundling_product.id_bundling')->from('bundling_product')
                                    ->join('products', 'products.id_product', 'bundling_product.id_product');
                                if ($row['operator'] == '=') {
                                    $sub->where('product_name', $row['parameter']);
                                } else {
                                    $sub->where('product_name', 'like', '%' . $row['parameter'] . '%');
                                }
                            });
                        }

                        if ($row['subject'] == 'outlet_name') {
                            $bundling->whereIn('bundling.id_bundling', function ($sub) use ($row) {
                                $sub->select('bundling_outlet.id_bundling')->from('bundling_outlet')
                                    ->join('outlets', 'outlets.id_outlet', 'bundling_outlet.id_outlet');
                                if ($row['operator'] == '=') {
                                    $sub->where('outlet_name', $row['parameter']);
                                } else {
                                    $sub->where('outlet_name', 'like', '%' . $row['parameter'] . '%');
                                }
                            });
                        }

                        if ($row['subject'] == 'brand_name') {
                            $bundling->whereIn('bundling.id_bundling', function ($sub) use ($row) {
                                $sub->select('bundling_product.id_bundling')->from('bundling_product')
                                    ->join('products', 'products.id_product', 'bundling_product.id_product')
                                    ->join('brand_product', 'products.id_product', 'brand_product.id_product')
                                    ->join('brands', 'brands.id_brand', 'brand_product.id_brand');
                                if ($row['operator'] == '=') {
                                    $sub->where('name_brand', $row['parameter']);
                                } else {
                                    $sub->where('name_brand', 'like', '%' . $row['parameter'] . '%');
                                }
                            });
                        }
                    }
                }
            } else {
                $bundling->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'bundling_code') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('bundling_code', $row['parameter']);
                                } else {
                                    $subquery->orWhere('bundling_code', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'bundling_name') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('bundling_name', $row['parameter']);
                                } else {
                                    $subquery->orWhere('bundling_name', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'bundling_price_before_discount' || $row['subject'] == 'bundling_price_after_discount') {
                                $subquery->orWhere($row['subject'], $row['operator'], $row['parameter']);
                            }

                            if ($row['subject'] == 'product_name') {
                                $subquery->orWhereIn('bundling.id_bundling', function ($sub) use ($row) {
                                    $sub->select('bundling_product.id_bundling')->from('bundling_product')
                                        ->join('products', 'products.id_product', 'bundling_product.id_product');
                                    if ($row['operator'] == '=') {
                                        $sub->where('product_name', $row['parameter']);
                                    } else {
                                        $sub->where('product_name', 'like', '%' . $row['parameter'] . '%');
                                    }
                                });
                            }

                            if ($row['subject'] == 'outlet_name') {
                                $subquery->orWhereIn('bundling.id_bundling', function ($sub) use ($row) {
                                    $sub->select('bundling_outlet.id_bundling')->from('bundling_outlet')
                                        ->join('outlets', 'outlets.id_outlet', 'bundling_outlet.id_outlet');
                                    if ($row['operator'] == '=') {
                                        $sub->where('outlet_name', $row['parameter']);
                                    } else {
                                        $sub->where('outlet_name', 'like', '%' . $row['parameter'] . '%');
                                    }
                                });
                            }

                            if ($row['subject'] == 'brand_name') {
                                $subquery->orWhereIn('bundling.id_bundling', function ($sub) use ($row) {
                                    $sub->select('bundling_product.id_bundling')->from('bundling_product')
                                        ->join('products', 'products.id_product', 'bundling_product.id_product')
                                        ->join('brand_product', 'products.id_product', 'brand_product.id_product')
                                        ->join('brands', 'brands.id_brand', 'brand_product.id_brand');
                                    if ($row['operator'] == '=') {
                                        $sub->where('name_brand', $row['parameter']);
                                    } else {
                                        $sub->where('name_brand', 'like', '%' . $row['parameter'] . '%');
                                    }
                                });
                            }
                        }
                    }
                });
            }
        }

        if (isset($post['all_data']) && $post['all_data'] == 1) {
            $bundling = $bundling->orderBy('bundling_order', 'asc')->get()->toArray();
        } else {
            if (isset($post['order_field']) && !empty($post['order_field'])) {
                $bundling->orderBy($post['order_field'], $post['order_method']);
            }
            $bundling = $bundling->paginate(20)->toArray();
        }

        foreach ($bundling['data'] ?? [] as $key => $b) {
            $idProd = array_column($b['bundling_product'], 'id_product');
            $getBrands = BrandProduct::join('brands', 'brands.id_brand', 'brand_product.id_brand')
                        ->whereIn('brand_product.id_product', $idProd)
                        ->groupBy('brands.id_brand')->select('brands.id_brand', 'name_brand')
                        ->get()->toArray();
            $bundling['data'][$key]['brands'] = $getBrands;
        }
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
        if (empty($post['bundling_description'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Description can not be empty']]);
        }
        if (isset($post['data_product']) && !empty($post['data_product'])) {
            DB::beginTransaction();
                $checkCode = Bundling::where('bundling_code', $post['bundling_code'])->first();
            if (!empty($checkCode)) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Bundling ID can not be same']]);
            }

                $isAllOutlet = 0;
            if (isset($post['id_outlet']) && in_array("all", $post['id_outlet'])) {
                $isAllOutlet = 1;
            }

            if ($post['bundling_specific_day_type'] == 'not_specific_day') {
                $post['bundling_specific_day_type'] = null;
            }
                //create bundling
                $createBundling = [
                    'bundling_code' => $post['bundling_code'],
                    'bundling_name' => $post['bundling_name'],
                    'start_date' => date('Y-m-d H:i:s', strtotime($post['bundling_start'])),
                    'end_date' => date('Y-m-d H:i:s', strtotime($post['bundling_end'])),
                    'bundling_description' => $post['bundling_description'],
                    'bundling_promo_status' => $post['bundling_promo_status'] ?? 0,
                    'bundling_specific_day_type' => $post['bundling_specific_day_type'] ?? null,
                    'id_bundling_category' => $post['id_bundling_category'] ?? null,
                    'all_outlet' => $isAllOutlet,
                    'outlet_available_type' => $post['outlet_available_type']
                ];
                $create = Bundling::create($createBundling);

                if (!$create) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed create bundling']]);
                }

                if (isset($post['photo'])) {
                    $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/bundling/', 300, 300, $create['id_bundling']);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $photo['image'] = $upload['path'];
                    }
                }

                if (isset($post['photo_detail'])) {
                    $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/bundling/detail/', 720, 360, $create['id_bundling']);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $photo['image_detail'] = $upload['path'];
                    }
                }

                if (isset($photo) && !empty($photo)) {
                    $updatePhotoBundling = Bundling::where('id_bundling', $create['id_bundling'])->update($photo);
                    if (!$updatePhotoBundling) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed update photo bundling']]);
                    }
                }

                //create bundling periode day
                if (isset($post['day_date']) && !empty($post['day_date'])) {
                    $insertDay = [];
                    foreach ($post['day_date'] as $sd) {
                        $insertDay[] = [
                            'id_bundling' => $create['id_bundling'],
                            'day' => $sd,
                            'time_start' => date("H:i:s", strtotime($post['time_start'])),
                            'time_end' => date("H:i:s", strtotime($post['time_end'])),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $insertBundlingProduct = BundlingPeriodeDay::insert($insertDay);
                    if (!$insertBundlingProduct) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed add specific day']]);
                    }

                    //check if periode in current date
                    $currentDate = date('Y-m-d');
                    if ($post['bundling_specific_day_type'] == 'Day') {
                        $currentDay = date('l', strtotime($currentDate));
                    } else {
                        $currentDay = date('d', strtotime($currentDate));
                    }

                    $check = array_search($currentDay, array_column($insertDay, 'day'));
                    if (
                        $check !== false &&
                        date('Y-m-d', strtotime($post['bundling_start'])) <= $currentDate &&
                        date('Y-m-d', strtotime($post['bundling_end'])) >= $currentDate
                    ) {
                        BundlingToday::updateOrCreate(['id_bundling' => $create['id_bundling']], [
                            'id_bundling' => $create['id_bundling'],
                            'time_start' => $insertDay[$check]['time_start'],
                            'time_end' => $insertDay[$check]['time_end']
                        ]);
                    }
                } else {
                    $currentDate = date('Y-m-d');
                    if (
                        date('Y-m-d', strtotime($post['bundling_start'])) <= $currentDate &&
                        date('Y-m-d', strtotime($post['bundling_end'])) >= $currentDate
                    ) {
                        BundlingToday::updateOrCreate(['id_bundling' => $create['id_bundling']], [
                            'id_bundling' => $create['id_bundling'],
                            'time_start' => '00:01:00',
                            'time_end' => '23:59:59',
                        ]);
                    }
                }

                //create bundling product
                $bundlingProduct = [];
                $beforePrice = 0;
                $afterPrice = 0;
                foreach ($post['data_product'] as $product) {
                    $bundlingProduct[] = [
                        'id_bundling' => $create['id_bundling'],
                        'id_brand' => $product['id_brand'],
                        'id_product' => $product['id_product'],
                        'id_product_variant_group' => $product['id_product_variant_group'] ?? null,
                        'bundling_product_qty' => $product['qty'],
                        'bundling_product_discount_type' => $product['discount_type'],
                        'bundling_product_discount' => $product['discount'],
                        'bundling_product_maximum_discount' => $product['maximum_discount'] ?? 0,
                        'charged_central' => $product['charged_central'],
                        'charged_outlet' => $product['charged_outlet'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $price = ProductGlobalPrice::where('id_product', $product['id_product'])->first()['product_global_price'] ?? 0;
                    if (!empty($product['id_product_variant_group'])) {
                        $price = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first()['product_variant_group_price'] ?? 0;
                    }

                    $price = (float)$price;
                    if (strtolower($product['discount_type']) == 'nominal') {
                        $calculate = ($price - $product['discount']);
                    } else {
                        $discount = $price * ($product['discount'] / 100);
                        $discount = ($discount > $product['maximum_discount'] && $product['maximum_discount'] > 0 ? $product['maximum_discount'] : $discount);
                        $calculate = ($price - $discount);
                    }
                    $calculate = $calculate * $product['qty'];
                    $afterPrice = $afterPrice + $calculate;
                    $beforePrice = $beforePrice + ($price * $product['qty']);
                }

                $insertBundlingProduct = BundlingProduct::insert($bundlingProduct);
                if (!$insertBundlingProduct) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert list product']]);
                }

                //update price
                Bundling::where('id_bundling', $create['id_bundling'])->update(['bundling_price_before_discount' => $beforePrice, 'bundling_price_after_discount' => $afterPrice]);

                if ($post['outlet_available_type'] == 'Selected Outlet') {
                    if ($isAllOutlet == 0) {
                        //create bundling outlet/outlet available
                        $bundlingOutlet = [];
                        foreach ($post['id_outlet'] as $outlet) {
                            $bundlingOutlet[] = [
                                'id_bundling' => $create['id_bundling'],
                                'id_outlet' => $outlet,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                        $bundlingOutlet = BundlingOutlet::insert($bundlingOutlet);
                        if (!$bundlingOutlet) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet available']]);
                        }
                    }
                } else {
                    $bundlingOutletGroup = [];
                    foreach ($post['id_outlet_group'] as $og) {
                        $bundlingOutletGroup[] = [
                            'id_bundling' => $create['id_bundling'],
                            'id_outlet_group' => $og,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $insertBundlingOutletGroup = BundlingOutletGroup::insert($bundlingOutletGroup);
                    if (!$insertBundlingOutletGroup) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet group filter']]);
                    }
                }

                DB::commit();
                return response()->json(['status' => 'success']);
        } else {
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
        if (isset($post['id_bundling']) && !empty($post['id_bundling'])) {
            $detail = Bundling::where('id_bundling', $post['id_bundling'])
                    ->with(['bundling_product', 'bundling_periode_day', 'bundling_outlet_group'])->first();

            $brands = [];
            if (!empty($detail['bundling_product'])) {
                foreach ($detail['bundling_product'] as $bp) {
                    $brands[] = $bp['id_brand'];
                    $bp['products'] = Product::join('brand_product', 'products.id_product', '=', 'brand_product.id_product')
                        ->where('brand_product.id_brand', $bp['id_brand'])
                        ->select('products.id_product', 'products.product_code', 'products.product_name')->get()->toArray();
                    $bp['product_variant'] = [];
                    $bp['product_variant'] = app($this->product_variant_group)->productVariantGroupListAjax($bp['id_product'], 'array');
                    $bp['price'] = 0;
                    if (!empty($bp['id_product_variant_group'])) {
                        $price = ProductVariantGroup::where('id_product_variant_group', $bp['id_product_variant_group'])->selectRaw('FORMAT(product_variant_group_price, 0) as price')->first();
                        $bp['price'] = $price['price'] ?? 0;
                    } elseif (!empty($bp['id_product'])) {
                        $price = ProductGlobalPrice::where('id_product', $bp['id_product'])->selectRaw('FORMAT(product_global_price, 0) as price')->first();
                        $bp['price'] = $price['price'] ?? 0;
                    }
                }
            }

            $brands = array_unique($brands);
            $count = count($brands);
            $paramValue = '';
            $tmp = [];
            foreach ($brands as $index => $p) {
                $tmp[] = 'bo.id_brand = "' . $p . '"';
            }

            $paramValue = implode(" OR ", $tmp);

            $outletAvailable = Outlet::join('brand_outlet as bo', 'bo.id_outlet', 'outlets.id_outlet')
                ->groupBy('bo.id_outlet')
                ->whereRaw($paramValue)
                ->havingRaw('COUNT(*) >= ' . $count)
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();

            $selectedOutletAvailable = BundlingOutlet::where('id_bundling', $post['id_bundling'])->pluck('id_outlet')->toArray();

            if (!empty($detail)) {
                return response()->json(['status' => 'success',
                                         'result' => [
                                             'detail' => $detail,
                                             'outlets' => $outletAvailable,
                                             'selected_outlet' => $selectedOutletAvailable,
                                             'brand_tmp' => $brands
                                         ]]);
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be null']]);
            }
        } else {
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
        if (empty($post['bundling_description'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Description can not be empty']]);
        }

        if (isset($post['data_product']) && !empty($post['data_product'])) {
            DB::beginTransaction();

            $isAllOutlet = 0;
            if (in_array("all", $post['id_outlet'] ?? []) && $post['outlet_available_type'] == 'Selected Outlet') {
                $isAllOutlet = 1;
            }

            if ($post['bundling_specific_day_type'] == 'not_specific_day') {
                $post['bundling_specific_day_type'] = null;
            }

            //update bundling
            $updateBundling = [
                'bundling_name' => $post['bundling_name'],
                'start_date' => date('Y-m-d H:i:s', strtotime($post['bundling_start'])),
                'end_date' => date('Y-m-d H:i:s', strtotime($post['bundling_end'])),
                'bundling_description' => $post['bundling_description'],
                'bundling_promo_status' => $post['bundling_promo_status'] ?? 0,
                'bundling_specific_day_type' => $post['bundling_specific_day_type'] ?? null,
                'id_bundling_category' => $post['id_bundling_category'] ?? null,
                'all_outlet' => $isAllOutlet,
                'outlet_available_type' => $post['outlet_available_type']
            ];
            $update = Bundling::where('id_bundling', $post['id_bundling'])->update($updateBundling);

            if (!$update) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update bundling']]);
            }

            if (isset($post['photo'])) {
                $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/bundling/', 300, 300, $post['id_bundling']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $photo['image'] = $upload['path'];
                }
            }

            if (isset($post['photo_detail'])) {
                $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/bundling/detail/', 720, 360, $post['id_bundling']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $photo['image_detail'] = $upload['path'];
                }
            }

            if (isset($photo) && !empty($photo)) {
                $updatePhotoBundling = Bundling::where('id_bundling', $post['id_bundling'])->update($photo);
                if (!$updatePhotoBundling) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed update photo bundling']]);
                }
            }

            //delete bundling day
            $delete = BundlingPeriodeDay::where('id_bundling', $post['id_bundling'])->delete();

            //create bundling periode day
            if (isset($post['day_date']) && !empty($post['day_date'])) {
                $insertDay = [];
                foreach ($post['day_date'] as $sd) {
                    $insertDay[] = [
                        'id_bundling' => $post['id_bundling'],
                        'day' => $sd,
                        'time_start' => date("H:i:s", strtotime($post['time_start'])),
                        'time_end' => date("H:i:s", strtotime($post['time_end'])),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $insertBundlingProduct = BundlingPeriodeDay::insert($insertDay);
                if (!$insertBundlingProduct) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add specific day']]);
                }

                BundlingToday::where('id_bundling', $post['id_bundling'])->delete();
                //check if periode in current date
                $currentDate = date('Y-m-d');
                if ($post['bundling_specific_day_type'] == 'Day') {
                    $currentDay = date('l', strtotime($currentDate));
                } else {
                    $currentDay = date('d', strtotime($currentDate));
                }

                $check = array_search($currentDay, array_column($insertDay, 'day'));

                if (
                    $check !== false &&
                    date('Y-m-d', strtotime($post['bundling_start'])) <= $currentDate &&
                    date('Y-m-d', strtotime($post['bundling_end'])) >= $currentDate
                ) {
                    BundlingToday::updateOrCreate(['id_bundling' => $post['id_bundling']], [
                        'id_bundling' => $post['id_bundling'],
                        'time_start' => $insertDay[$check]['time_start'],
                        'time_end' => $insertDay[$check]['time_end']
                    ]);
                }
            } else {
                $currentDate = date('Y-m-d');
                if (
                    date('Y-m-d', strtotime($post['bundling_start'])) <= $currentDate &&
                    date('Y-m-d', strtotime($post['bundling_end'])) >= $currentDate
                ) {
                    BundlingToday::updateOrCreate(['id_bundling' => $post['id_bundling']], [
                        'id_bundling' => $post['id_bundling'],
                        'time_start' => '00:01:00',
                        'time_end' => '23:59:59',
                    ]);
                }
            }

            $afterPrice = 0;
            $beforePrice = 0;
            //update bundling product
            foreach ($post['data_product'] as $product) {
                if (isset($product['id_bundling_product']) && !empty($product['id_bundling_product'])) {
                    $bundlingProduct = [
                        'bundling_product_qty' => $product['qty'],
                        'bundling_product_discount_type' => $product['discount_type'],
                        'bundling_product_discount' => $product['discount'],
                        'bundling_product_maximum_discount' => $product['maximum_discount'] ?? 0,
                        'charged_central' => $product['charged_central'],
                        'charged_outlet' => $product['charged_outlet'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $saveBundlingProduct = BundlingProduct::where('id_bundling_product', $product['id_bundling_product'])->update($bundlingProduct);
                } else {
                    $bundlingProduct = [
                        'id_bundling' => $post['id_bundling'],
                        'id_brand' => $product['id_brand'],
                        'id_product' => $product['id_product'],
                        'id_product_variant_group' => $product['id_product_variant_group'] ?? null,
                        'bundling_product_qty' => $product['qty'],
                        'bundling_product_discount_type' => $product['discount_type'],
                        'bundling_product_discount' => $product['discount'],
                        'bundling_product_maximum_discount' => $product['maximum_discount'] ?? 0,
                        'charged_central' => $product['charged_central'],
                        'charged_outlet' => $product['charged_outlet'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $bundlingProduct['created_at'] = date('Y-m-d H:i:s');
                    $saveBundlingProduct = BundlingProduct::create($bundlingProduct);
                }

                if (!$saveBundlingProduct) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save list product']]);
                }

                $price = ProductGlobalPrice::where('id_product', $product['id_product'])->first()['product_global_price'] ?? 0;
                if (!empty($product['id_product_variant_group'])) {
                    $price = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first()['product_variant_group_price'] ?? 0;
                }

                $price = (float)$price;
                if (strtolower($product['discount_type']) == 'nominal') {
                    $calculate = ($price - $product['discount']);
                } else {
                    $discount = $price * ($product['discount'] / 100);
                    $discount = ($discount > $product['maximum_discount'] && $product['maximum_discount'] > 0 ? $product['maximum_discount'] : $discount);
                    $calculate = ($price - $discount);
                }
                $calculate = $calculate * $product['qty'];
                $afterPrice = $afterPrice + $calculate;
                $beforePrice = $beforePrice + ($price * $product['qty']);
            }

            //update price
            Bundling::where('id_bundling', $post['id_bundling'])->update(['bundling_price_before_discount' => $beforePrice, 'bundling_price_after_discount' => $afterPrice]);

            //delete bundling outlet
            BundlingOutlet::where('id_bundling', $post['id_bundling'])->delete();
            BundlingOutletGroup::where('id_bundling', $post['id_bundling'])->delete();

            if ($post['outlet_available_type'] == 'Selected Outlet') {
                if ($isAllOutlet == 0) {
                    //create bundling outlet/outlet available
                    $bundlingOutlet = [];
                    foreach ($post['id_outlet'] as $outlet) {
                        $bundlingOutlet[] = [
                            'id_bundling' => $post['id_bundling'],
                            'id_outlet' => $outlet,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $bundlingOutlet = BundlingOutlet::insert($bundlingOutlet);
                    if (!$bundlingOutlet) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet available']]);
                    }
                }
            } else {
                $bundlingOutletGroup = [];
                foreach ($post['id_outlet_group'] as $og) {
                    $bundlingOutletGroup[] = [
                        'id_bundling' => $post['id_bundling'],
                        'id_outlet_group' => $og,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $insertBundlingOutletGroup = BundlingOutletGroup::insert($bundlingOutletGroup);
                if (!$insertBundlingOutletGroup) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet group filter']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Data product can not be empty']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_bundling']) && !empty($post['id_bundling'])) {
            $check = TransactionBundlingProduct::where('id_bundling', $post['id_bundling'])->first();
            if ($check) {
                return response()->json(['status' => 'fail', 'messages' => ['Bundling already use in transaction']]);
            }

            $delete = Bundling::where('id_bundling', $post['id_bundling'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be empty']]);
        }
    }

    public function destroyBundlingProduct(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_bundling_product']) && !empty($post['id_bundling_product'])) {
            $check = TransactionProduct::where('id_bundling_product', $post['id_bundling_product'])->first();
            if ($check) {
                return response()->json(['status' => 'fail', 'messages' => ['Bundling product already use in transaction']]);
            }

            $delete = BundlingProduct::where('id_bundling_product', $post['id_bundling_product'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling product can not be empty']]);
        }
    }

    public function outletAvailable(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['brands']) && !empty($post['brands'])) {
            $idBrandAjax = array_column($post['brands'], 'value');

            if (!empty($post['brand_tmp'] ?? [])) {
                $idBrand = array_unique(array_merge($idBrandAjax, $post['brand_tmp']));
            } else {
                $idBrand = $idBrandAjax;
            }
            $idBrand = array_unique($idBrand);
            $count = count($idBrand);
            $paramValue = '';
            foreach ($idBrand as $index => $p) {
                if ($index !== $count - 1) {
                    $paramValue .= 'bo.id_brand = "' . $p . '" OR ';
                } else {
                    $paramValue .= 'bo.id_brand = "' . $p . '"';
                }
            }

            $outlets = Outlet::join('brand_outlet as bo', 'bo.id_outlet', 'outlets.id_outlet')
                ->groupBy('bo.id_outlet')
                ->whereRaw($paramValue)
                ->havingRaw('COUNT(*) >= ' . $count)
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();

            return response()->json(MyHelper::checkGet($outlets));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted parameter']]);
        }
    }

    public function detailForApps(Request $request)
    {
        $post = $request->json()->all();
        if (!isset($post['id_bundling']) && empty($post['id_bundling'])) {
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be empty']]);
        }

        if (!isset($post['id_outlet']) && empty($post['id_outlet'])) {
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
            ->select(
                'brand_product.id_brand',
                'pgp.product_global_price',
                'products.product_variant_status',
                'products.product_name',
                'products.product_code',
                'products.product_description',
                'bundling_product.*',
                'bundling.*'
            )
            ->get()->toArray();

        if (empty($getProductBundling)) {
            return ['status' => 'fail','messages' => ['Bundling detail not found']];
        }

        if ($getProductBundling[0]['all_outlet'] == 0 && $getProductBundling[0]['outlet_available_type'] == 'Selected Outlet') {
            //check available outlet
            $availableOutlet = BundlingOutlet::where('id_outlet', $post['id_outlet'])->where('id_bundling', $post['id_bundling'])->first();
            if (!$availableOutlet) {
                return ['status' => 'fail','messages' => ['Product not available in this outlet']];
            }
        } elseif ($getProductBundling[0]['all_outlet'] == 0 && $getProductBundling[0]['outlet_available_type'] == 'Outlet Group Filter') {
            $arrBundlingIdProduct = array_column($getProductBundling, 'id_product');
            $brands = BrandProduct::whereIn('id_product', $arrBundlingIdProduct)->pluck('id_brand')->toArray();
            $availableBundling = app($this->bundling)->bundlingOutletGroupFilter($post['id_outlet'], $brands);
            if (empty($availableBundling)) {
                return ['status' => 'fail','messages' => ['Product not available in this outlet']];
            }
        }

        $priceForListNoDiscount = 0;
        $priceForList = 0;
        $products = [];
        foreach ($getProductBundling as $p) {
            if ($p['product_variant_status'] && !empty($p['id_product_variant_group'])) {
                if ($outlet['outlet_different_price'] == 1) {
                    $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $p['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price'] ?? 0;
                } else {
                    $price = ProductVariantGroup::where('id_product_variant_group', $p['id_product_variant_group'])->first()['product_variant_group_price'] ?? 0;
                }
            } elseif (!empty($p['id_product'])) {
                if ($outlet['outlet_different_price'] == 1) {
                    $price = ProductSpecialPrice::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price'] ?? 0;
                } else {
                    $price = $p['product_global_price'];
                }
            }

            $variants = [];
            $extraModifier = [];
            $selectedExtraMod = [];
            if ($p['product_variant_status'] && !empty($p['id_product_variant_group'])) {
                $variants = ProductVariantGroup::join('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                    ->join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                    ->where('product_variant_groups.id_product_variant_group', $p['id_product_variant_group'])
                    ->select('product_variants.id_product_variant', 'product_variant_pivot.id_product_variant_group', 'product_variant_name')
                    ->get()->toArray();
                $idVariant = array_column($variants, 'id_product_variant');
                $getExtraModifier = ProductModifierGroup::join('product_modifier_group_pivots', 'product_modifier_groups.id_product_modifier_group', 'product_modifier_group_pivots.id_product_modifier_group')
                                    ->join('product_modifiers', 'product_modifiers.id_product_modifier_group', 'product_modifier_groups.id_product_modifier_group')
                                    ->where('id_product', $p['id_product'])->orWhereIn('id_product_variant', $idVariant)
                                    ->orderBy('product_modifier_groups.product_modifier_group_order', 'asc')
                                    ->orderBy('product_modifier_order', 'asc')
                                    ->select('id_product_modifier', 'text_detail_trx', 'product_modifiers.id_product_modifier_group')->get()->toArray();

                foreach ($getExtraModifier as $em) {
                    $check = array_search($em['id_product_modifier_group'], array_column($selectedExtraMod, 'id_product_modifier_group'));
                    if ($check === false) {
                        $extraModifier[] = $em['id_product_modifier'];
                        $selectedExtraMod[] = [
                            'id_product_modifier_group' => $em['id_product_modifier_group'],
                            'id_product_variant' => $em['id_product_modifier'],
                            'product_variant_name' => $em['text_detail_trx']
                        ];
                    }
                }
            }

            $price = (float)$price;
            for ($i = 0; $i < $p['bundling_product_qty']; $i++) {
                $priceForListNoDiscount = $priceForListNoDiscount + $price;

                //calculate discount produk
                if (strtolower($p['bundling_product_discount_type']) == 'nominal') {
                    $calculate = ($price - $p['bundling_product_discount']);
                } else {
                    $discount = $price * ($p['bundling_product_discount'] / 100);
                    $discount = ($discount > $p['bundling_product_maximum_discount'] &&  $p['bundling_product_maximum_discount'] > 0 ? $p['bundling_product_maximum_discount'] : $discount);
                    $calculate = ($price - $discount);
                }
                $priceForList = $priceForList + $calculate;

                $products[] = [
                    'id_product' => $p['id_product'],
                    'id_brand' => $p['id_brand'],
                    'id_bundling' => $p['id_bundling'],
                    'id_bundling_product' => $p['id_bundling_product'],
                    'id_product_variant_group' => $p['id_product_variant_group'],
                    'product_name' => $p['product_name'],
                    'product_code' => $p['product_code'],
                    'product_description' => $p['product_description'],
                    'extra_modifiers' => $extraModifier,
                    'variants' => array_merge($variants, $selectedExtraMod)
                ];
            }
        }

        $result = [
            'id_bundling' => $getProductBundling[0]['id_bundling'],
            'bundling_name' => $getProductBundling[0]['bundling_name'],
            'bundling_code' => $getProductBundling[0]['bundling_code'],
            'bundling_description' => $getProductBundling[0]['bundling_description'],
            'bundling_image_detail' => (!empty($getProductBundling[0]['image_detail']) ? config('url.storage_url_api') . $getProductBundling[0]['image_detail'] : ''),
            'bundling_base_price' => $priceForList,
            'bundling_base_price_no_discount' => $priceForListNoDiscount,
            'products' => $products
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function globalPrice(Request $request)
    {
        $post = $request->json()->all();
        if (
            !isset($post['id_product']) && empty($post['id_product']) &&
            !isset($post['id_product_variant_group']) && empty($post['id_product_variant_group'])
        ) {
            return ['status' => 'fail','messages' => ['ID product and ID product variant group can not be empty']];
        }

        $price = [];
        if (!empty($post['id_product'])) {
            $price = ProductGlobalPrice::where('id_product', $post['id_product'])->selectRaw('FORMAT(product_global_price, 0) as price')->first();
        } elseif (!empty($post['id_product_variant_group'])) {
            $price = ProductVariantGroup::where('id_product_variant_group', $post['id_product_variant_group'])->selectRaw('FORMAT(product_variant_group_price, 0) as price')->first();
        }

        return response()->json(MyHelper::checkGet($price));
    }

    public function bundlingToday()
    {
        $log = MyHelper::logCron('Sync Data Bundling Today');
        try {
            $currentDate = date('Y-m-d');
            $day = date('l', strtotime($currentDate));
            $dayNumber = date('d', strtotime($currentDate));
            $getBundling = Bundling::leftJoin('bundling_periode_day as bpd', 'bpd.id_bundling', 'bundling.id_bundling')
                ->whereDate('start_date', '<=', $currentDate)
                ->whereDate('end_date', '>=', $currentDate)
                ->where(function ($q) use ($day, $dayNumber) {
                    $q->where('bpd.day', $day)
                        ->orWhere('bpd.day', $dayNumber)
                        ->orWhereNull('bpd.id_bundling_periode_day');
                })
                ->select('bpd.day', 'bpd.id_bundling_periode_day', 'bpd.time_start', 'bpd.time_end', 'bundling.id_bundling')->get()->toArray();
            $dataToInsert = [];

            foreach ($getBundling as $b) {
                $check = array_search($b['id_bundling'], array_column($dataToInsert, 'id_bundling'));
                if (empty($b['id_bundling_periode_day'])) {
                    $timeStart = '00:01:00';
                    $timeEnd = '23:59:59';
                } else {
                    $timeStart = $b['time_start'];
                    $timeEnd = $b['time_end'];
                }
                if ($check === false) {
                    $dataToInsert[] = [
                        'id_bundling' => $b['id_bundling'],
                        'time_start' => $timeStart,
                        'time_end' => $timeEnd,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
            }

            //delete old data
            BundlingToday::whereNotNull('id_bundling')->delete();
            //insert new data
            BundlingToday::insert($dataToInsert);

            $log->success();
            return 'succes';
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        };
    }

    public function positionBundling(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['product_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Bundling id is required']
            ];
        }
        // update position
        foreach ($post['product_ids'] as $key => $product_id) {
            $update = Bundling::find($product_id)->update(['bundling_order' => $key + 1]);
        }

        return ['status' => 'success'];
    }

    public function bundlingOutletGroupFilter($id_outlet, $brands, $id_bundling = null)
    {
        $currentHour = date('H:i:s');
        $getBundlingOutletGroupFilter = Bundling::join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
            ->join('bundling_product as bp', 'bp.id_bundling', 'bundling.id_bundling')
            ->join('brand_product', 'brand_product.id_product', 'bp.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', 'brand_product.id_brand')
            ->join('bundling_outlet_group as bog', 'bundling.id_bundling', 'bog.id_bundling')
            ->where('bundling.outlet_available_type', 'Outlet Group Filter')
            ->whereIn('brand_product.id_brand', $brands)
            ->whereRaw('TIME_TO_SEC("' . $currentHour . '") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("' . $currentHour . '") <= TIME_TO_SEC(time_end)')
            ->select('bog.*')
            ->groupBy('bog.id_bundling_outlet_group');

        if (!empty($id_bundling)) {
            $getBundlingOutletGroupFilter->where('bundling.id_bundling', $id_bundling);
        }
        $getBundlingOutletGroupFilter = $getBundlingOutletGroupFilter->get()->toArray();

        $tmpBundling = [];

        foreach ($getBundlingOutletGroupFilter as $bog) {
            $outlet = app($this->outlet_group_filter)->outletGroupFilter($bog['id_outlet_group']);
            if (!empty($outlet)) {
                $check = array_search($id_outlet, array_column($outlet, 'id_outlet'));
                if ($check !== false) {
                    $tmpBundling[] = $bog['id_bundling'];
                }
            }
        }

        return array_unique($tmpBundling);
    }

    public function setting(Request $request)
    {
        $post = $request->json()->all();

        if (empty($post)) {
            $set = Setting::where('key', 'brand_bundling_name')->first();
            return response()->json(MyHelper::checkGet($set));
        } else {
            $set = Setting::updateOrCreate(['key' => 'brand_bundling_name'], ['key' => 'brand_bundling_name', 'value' => $post['brand_bundling_name'] ?? 'Bundling']);
            return response()->json(MyHelper::checkUpdate($set));
        }
    }
}
