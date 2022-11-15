<?php

namespace Modules\ProductVariant\Http\Controllers;

use App\Http\Models\Outlet;
use App\Jobs\RefreshVariantTree;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Product;
use Modules\Brand\Entities\Brand;
use Modules\ProductVariant\Entities\ProductVariant;
use DB;
use Illuminate\Support\Facades\Log;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\ProductVariant\Entities\ProductVariantGroupWholesaler;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductGlobalPrice;

class ApiProductVariantGroupController extends Controller
{
    public function productVariantGroupListCreate(Request $request)
    {
        $post = $request->all();
        if (isset($request->product_code) && !empty($request->product_code)) {
            if (isset($post['variants']) && !empty($post['variants'])) {
                $process = false;
                $id_product = Product::where('product_code', $request->product_code)->first();
                foreach ($post['variants'] as $dt) {
                    if (!empty($dt['id_product_variant_group'])) {
                        $update = ProductVariantGroup::where('id_product_variant_group', $dt['id_product_variant_group'])
                            ->update([
                                'product_variant_group_price' => str_replace(".", "", $dt['product_variant_group_price']),
                                'product_variant_group_visibility' => 'Visible']);

                        if ($update) {
                            //udpate visibility group detail
                            ProductVariantGroupDetail::where('id_product_variant_group', $dt['id_product_variant_group'])
                                ->update(['product_variant_group_visibility' => $dt['product_variant_group_visibility'] ?? 'Visible']);

                            ProductVariantGroupWholesaler::where('id_product_variant_group', $dt['id_product_variant_group'])->delete();
                            if (!empty($dt['wholesalers'])) {
                                $insertWholesalerVariant = [];
                                foreach ($dt['wholesalers'] as $wholesaler) {
                                    if ($wholesaler['minimum'] <= 1) {
                                        return response()->json(['status' => 'fail', 'messages' => ['Minimum wholesaler must be more than one']]);
                                    }
                                    $insertWholesalerVariant[] = [
                                        'id_product_variant_group' => $dt['id_product_variant_group'],
                                        'variant_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                                        'variant_wholesaler_unit_price' => str_replace('.', '', $wholesaler['unit_price'] ?? 0),
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ];
                                }

                                $arrayColumn = array_column($insertWholesalerVariant, 'variant_wholesaler_minimum');
                                $withoutDuplicates = array_unique($arrayColumn);
                                $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
                                if (!empty($duplicates)) {
                                    DB::rollback();
                                    return response()->json(['status' => 'fail', 'messages' => ["Minimum can't be the same"]]);
                                }

                                ProductVariantGroupWholesaler::insert($insertWholesalerVariant);
                            }
                        }
                    }
                }



                return response()->json(MyHelper::checkUpdate($update));
            } else {
                $get = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
                    ->where('products.product_code', $post['product_code'])
                    ->with(['product_variant_pivot', 'product_variant_group_wholesaler', 'variant_detail'])
                    ->get()->toArray();
                return response()->json(MyHelper::checkGet($get));
            }
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function productVariantGroupList(Request $request)
    {
        $post = $request->all();
        $get = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->join('brand_product', 'brand_product.id_product', 'products.id_product')
            ->join('brands', 'brand_product.id_brand', 'brands.id_brand')
            ->select('product_variant_groups.*', 'products.product_name', 'products.product_code', 'brands.name_brand')
            ->orderBy('brands.order_brand', 'asc')
            ->with(['product_variant_pivot']);

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $param = array_column($post['conditions'], 'parameter');
            $count = count($param);
            $paramValue = '';
            foreach ($param as $index => $p) {
                if ($index !== $count - 1) {
                    $paramValue .= 'pv.product_variant_name = "' . $p . '" OR ';
                } else {
                    $paramValue .= 'pv.product_variant_name = "' . $p . '"';
                }
            }

            $get = $get->whereIn('id_product_variant_group', function ($query) use ($paramValue, $count) {
                $query->select('id_product_variant_group')
                    ->from('product_variant_pivot as pvp')
                    ->join('product_variants as pv', 'pv.id_product_variant', 'pvp.id_product_variant')
                    ->groupBy('pvp.id_product_variant_group')
                    ->whereRaw($paramValue)
                    ->havingRaw('COUNT(*) = ' . $count);
            });
        }

        $get = $get->paginate($request->length ?: 10);
        return response()->json(MyHelper::checkGet($get));
    }

    public function productVariantGroupListAjax($idProduct, $returnType = 'json')
    {
        $get = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->where('product_variant_groups.id_product', $idProduct)
            ->where('products.product_variant_status', 1)
            ->with('product_variant_pivot_simple')
            ->select('product_variant_groups.id_product_variant_group')
            ->get();

        $result = [];
        foreach ($get as $i => $data) {
            $dataResult = null;
            $dataResult['id_product_variant_group'] = $data['id_product_variant_group'];
            $dataResult['product_variant_group_name'] = '';
            foreach ($data['product_variant_pivot_simple'] as $key => $variant) {
                if ($key > 0) {
                    $dataResult['product_variant_group_name'] = $dataResult['product_variant_group_name'] . ' ';
                }
                $dataResult['product_variant_group_name'] = $dataResult['product_variant_group_name'] . $variant['product_variant_name'];
            }
            $result[] = $dataResult;
        }

        if ($returnType == 'json') {
            return response()->json(MyHelper::checkGet($result));
        } else {
            return $result;
        }
    }

    public function removeProductVariantGroup(Request $request)
    {
        $post = $request->all();
        if (isset($post['id_product_variant']) && !empty($post['id_product_variant'])) {
            $failed = 0;
            $success = 0;
            $messages = [];
            if (strpos($post['id_product_variant'], "all") !== false) {
                $productVariant = ProductVariantGroup::orderBy('created_at', 'desc');

                if (isset($post['conditions']) && !empty($post['conditions'])) {
                    $param = array_column($post['conditions'], 'parameter');
                    $count = count($param);
                    $paramValue = '';
                    foreach ($param as $index => $p) {
                        if ($index !== $count - 1) {
                            $paramValue .= 'pv.product_variant_name = "' . $p . '" OR ';
                        } else {
                            $paramValue .= 'pv.product_variant_name = "' . $p . '"';
                        }
                    }

                    $productVariant = $productVariant->whereIn('id_product_variant_group', function ($query) use ($paramValue, $count) {
                        $query->select('id_product_variant_group')
                            ->from('product_variant_pivot as pvp')
                            ->join('product_variants as pv', 'pv.id_product_variant', 'pvp.id_product_variant')
                            ->groupBy('pvp.id_product_variant_group')
                            ->whereRaw($paramValue)
                            ->havingRaw('COUNT(*) = ' . $count);
                    });
                }

                $productVariant = $productVariant->get()->toArray();
                foreach ($productVariant as $pv) {
                    try {
                        $delete = ProductVariantGroup::where('id_product_variant_group', $pv['id_product_variant_group'])->delete();
                        if ($delete) {
                            ProductVariantPivot::where('id_product_variant_group', $pv['id_product_variant_group'])->delete();
                        }
                        $success++;
                    } catch (\Exception $e) {
                        $failed++;
                        $messages[] = $pv['product_variant_group_code'] . ' - Cannot delete because product variant already use';
                    }
                }

                return response()->json([
                    'success' => $success,
                    'fail' => $failed,
                    'messages' => $messages
                ]);
            } else {
                $explode = explode(',', $post['id_product_variant']);
                $productVariant = ProductVariantGroup::whereIn('id_product_variant_group', $explode)->get()->toArray();

                foreach ($productVariant as $pv) {
                    try {
                        $delete = ProductVariantGroup::where('id_product_variant_group', $pv['id_product_variant_group'])->delete();
                        if ($delete) {
                            ProductVariantPivot::where('id_product_variant_group', $pv['id_product_variant_group'])->delete();
                        }
                        $success++;
                    } catch (\Exception $e) {
                        $failed++;
                        $messages[] = $pv['product_variant_group_code'] . ' - Cannot delete because product variant already use';
                    }
                }

                return response()->json([
                    'success' => $success,
                    'fail' => $failed,
                    'messages' => $messages
                ]);
            }
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listPrice(Request $request)
    {
        $post = $request->all();

        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->with(['product_variant_pivot']);

        if (isset($post['id_outlet']) && !empty($post['id_outlet'])) {
            $data = $data->leftJoin('product_variant_group_special_prices as pvgsp', function ($join) use ($post) {
                $join->on('pvgsp.id_product_variant_group', 'product_variant_groups.id_product_variant_group');
                $join->where('pvgsp.id_outlet', '=', $post['id_outlet']);
            })
                ->where(function ($query) use ($post) {
                    $query->where('pvgsp.id_outlet', $post['id_outlet']);
                    $query->orWhereNull('pvgsp.id_outlet');
                })
                ->select('pvgsp.*', 'products.product_name', 'products.product_code', 'product_variant_groups.product_variant_group_code', 'product_variant_groups.id_product_variant_group');
        } else {
            $data = $data->select('product_variant_groups.*', 'products.product_name', 'products.product_code');
        }

        if (isset($post['rule']) && !empty($post['rule'])) {
            $rule = 'and';
            if (isset($post['operator'])) {
                $rule = $post['operator'];
            }

            if ($rule == 'and') {
                foreach ($post['rule'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'product_variant_group_code') {
                            if ($row['operator'] == '=') {
                                $data->where('product_variant_groups.product_variant_group_code', $row['parameter']);
                            } else {
                                $data->where('product_variant_groups.product_variant_group_code', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'product_variant_group_visibility') {
                            $data->where('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                        }

                        if ($row['subject'] == 'product_code') {
                            if ($row['operator'] == '=') {
                                $data->where('products.product_code', $row['parameter']);
                            } else {
                                $data->where('products.product_code', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'product_name') {
                            if ($row['operator'] == '=') {
                                $data->where('products.product_name', $row['parameter']);
                            } else {
                                $data->where('products.product_name', 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                }
            } else {
                $data->where(function ($subquery) use ($post) {
                    foreach ($post['rule'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'product_variant_group_code') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('product_variant_groups.product_variant_group_code', $row['parameter']);
                                } else {
                                    $subquery->orWhere('product_variant_groups.product_variant_group_code', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'product_variant_group_visibility') {
                                $subquery->orWhere('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                            }

                            if ($row['subject'] == 'product_code') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('products.product_code', $row['parameter']);
                                } else {
                                    $subquery->orWhere('products.product_code', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'product_name') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('products.product_name', $row['parameter']);
                                } else {
                                    $subquery->orWhere('products.product_name', 'like', '%' . $row['parameter'] . '%');
                                }
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }

    public function updatePrice(Request $request)
    {
        $id_outlet  = $request->json('id_outlet');
        $insertData = [];
        DB::beginTransaction();
        if (empty($id_outlet)) {
            foreach ($request->json('prices') as $id_product_variant_group => $price) {
                if (!is_numeric($price['product_variant_group_price'])) {
                    continue;
                }
                $insert = ProductVariantGroup::where('id_product_variant_group', $id_product_variant_group)->update(['product_variant_group_price' => $price['product_variant_group_price']]);
                if (!$insert) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Update price fail'],
                    ];
                }
            }
        } else {
            foreach ($request->json('prices') as $id_product_variant_group => $price) {
                if (!($price['product_variant_group_price'] ?? false)) {
                    continue;
                }
                $key = [
                    'id_product_variant_group' => $id_product_variant_group,
                    'id_outlet'           => $id_outlet,
                ];
                $insertData = [
                    'id_product_variant_group' => $id_product_variant_group,
                    'id_outlet'           => $id_outlet,
                    'product_variant_group_price' => $price['product_variant_group_price'],
                ];

                $insert = ProductVariantGroupSpecialPrice::updateOrCreate($key, $insertData);
                if (!$insert) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Update price fail'],
                    ];
                }
            }
        }
        DB::commit();
        //update all product
        RefreshVariantTree::dispatch([])->allOnConnection('database');
        return ['status' => 'success'];
    }

    public function listDetail(Request $request)
    {
        $post      = $request->json()->all();
        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->with(['product_variant_pivot']);

        if (isset($post['id_outlet']) && !empty($post['id_outlet'])) {
            $data = $data->leftJoin('product_variant_group_details as pvgd', function ($join) use ($post) {
                $join->on('pvgd.id_product_variant_group', 'product_variant_groups.id_product_variant_group');
                $join->where('pvgd.id_outlet', '=', $post['id_outlet']);
            })
                ->where(function ($query) use ($post) {
                    $query->where('pvgd.id_outlet', $post['id_outlet']);
                    $query->orWhereNull('pvgd.id_outlet');
                })
                ->select('pvgd.*', 'products.product_name', 'products.product_code', 'product_variant_groups.product_variant_group_code', 'product_variant_groups.id_product_variant_group');
        } else {
            $data = $data->select('product_variant_groups.*', 'products.product_name', 'products.product_code');
        }

        if (isset($post['rule']) && !empty($post['rule'])) {
            $rule = 'and';
            if (isset($post['operator'])) {
                $rule = $post['operator'];
            }

            if ($rule == 'and') {
                foreach ($post['rule'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'product_variant_group_code') {
                            $data->where('product_variant_groups.product_variant_group_code', $row['parameter']);
                        }

                        if ($row['subject'] == 'product_variant_group_visibility') {
                            $data->where('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                        }
                    }
                }
            } else {
                $data->where(function ($subquery) use ($post) {
                    foreach ($post['rule'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'product_variant_group_code') {
                                $subquery->orWhere('product_variant_groups.product_variant_group_code', $row['parameter']);
                            }

                            if ($row['subject'] == 'product_variant_group_visibility') {
                                $subquery->orWhere('product_variant_groups.product_variant_group_visibility', $row['parameter']);
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }

    public function updateDetail(Request $request)
    {
        $id_outlet  = $request->json('id_outlet');
        $insertData = [];
        DB::beginTransaction();
        foreach ($request->json('detail') as $id_product_modifier => $detail) {
            $key = [
                'id_product_variant_group' => $id_product_modifier,
                'id_outlet'           => $id_outlet,
            ];
            $insertData = $key + [
                    'product_variant_group_stock_status' => $detail['product_variant_group_stock_status']
                ];
            $insert = ProductVariantGroupDetail::updateOrCreate($key, $insertData);
            if (!$insert) {
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Update detail fail'],
                ];
            }
        }
        DB::commit();
        RefreshVariantTree::dispatch([])->allOnConnection('database');
        return ['status' => 'success'];
    }

    public function export(Request $request)
    {
        $post      = $request->json()->all();
        $data = Product::with(['product_variant_group'])->where('product_type', 'product')->where('product_visibility', 'Visible');
        $dataBrand = [];
        if (isset($post['id_brand']) && !empty($post['id_brand'])) {
            $dataBrand = Brand::where('brands.id_brand', $post['id_brand'])->first();
            $data = $data->join('brand_product', 'brand_product.id_product', 'products.id_product')
                ->join('brands', 'brand_product.id_brand', 'brands.id_brand')
                ->where('brands.id_brand', $post['id_brand']);
        }
        $data = $data->get()->toArray();
        $parent = ProductVariant::whereNull('id_parent')->with(['product_variant_child'])->get()->toArray();

        $arr = [];
        foreach ($data as $key => $dt) {
            $arr[$key] = [
                'product_name' => $dt['product_name'],
                'product_code' => $dt['product_code'],
                'use_product_variant_status' => ($dt['product_variant_status'] == 1 ? 'YES' : 'NO')
            ];

            foreach ($parent as $p) {
                $name = '';
                if (!empty($p['product_variant_child'])) {
                    $child = array_column($p['product_variant_child'], 'product_variant_name');
                    $name = '(' . implode(',', $child) . ')';
                }
                $variant = [];
                foreach ($dt['product_variant_group'] as $pg) {
                    if ($pg['id_parent'] == $p['id_product_variant'] && array_search($pg['product_variant_name'], $variant) === false) {
                        $variant[] = $pg['product_variant_name'];
                    }
                }
                $arr[$key][$p['id_product_variant'] . '-' . $p['product_variant_name'] . ' ' . $name] = implode(',', $variant);
            }
        }

        if ($arr) {
            return response()->json([
                'status' => 'success',
                'result' => [
                    'brand' => $dataBrand,
                    'products' => $arr
                ]
            ]);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['empty']]);
        }
    }

    public function import(Request $request)
    {
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'create' => 0,
            'no_update' => 0,
            'invalid' => 0,
            'failed' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'] ?? [];

        foreach ($data as $key => $value) {
            if (empty($value['product_code'])) {
                $result['invalid']++;
                continue;
            }

            if (empty($value['use_product_variant_status'])) {
                $result['invalid']++;
                continue;
            }
            $arrVariantGroup = [];
            $products = Product::where('product_code', $value['product_code'])->first();
            $update = Product::where('product_code', $value['product_code'])->update(['product_variant_status' => (strtolower($value['use_product_variant_status']) == 'yes' ? 1 : 0)]);
            if (strtolower($value['use_product_variant_status']) == 'yes') {
                unset($value['product_code']);
                unset($value['product_name']);
                unset($value['use_product_variant_status']);
                $newArr = [];
                foreach ($value as $keyCom => $new) {
                    if (!empty($new)) {
                        $keyCom = explode("-", $keyCom)[0];
                        $explode = explode(",", $new);
                        foreach ($explode as $k => $x) {
                            $explode[$k] = $keyCom . '-' . $x;
                        }
                        $newArr[] = $explode;
                    }
                }
                $arrCombinations = $this->combinations($newArr);

                if ($arrCombinations) {
                    foreach ($arrCombinations as $group) {
                        //search id product variant for insert into product variant pivot
                        $arrTmp = [];
                        if (is_array($group)) {
                            foreach ($group as $g) {
                                $id = explode('-', $g)[0] ?? '';
                                $name = explode('-', $g)[1] ?? '';
                                $searchId = ProductVariant::where('id_parent', $id)->where('product_variant_name', $name)->first();
                                if ($searchId !== false) {
                                    $arrTmp[] = $searchId['id_product_variant'];
                                }
                            }
                        } else {
                            $id = explode('-', $group)[0] ?? '';
                            $name = explode('-', $group)[1] ?? '';
                            $searchId = ProductVariant::where('id_parent', $id)->where('product_variant_name', $name)->first();
                            if ($searchId !== false) {
                                $arrTmp[] = $searchId['id_product_variant'];
                            }
                        }

                        if ($arrTmp) {
                            $checkExisting = ProductVariantPivot::join('product_variant_groups as pvg', 'pvg.id_product_variant_group', 'product_variant_pivot.id_product_variant_group')
                                            ->whereIn('product_variant_pivot.id_product_variant', $arrTmp)->where('pvg.id_product', $products['id_product'])
                                            ->groupBy('product_variant_pivot.id_product_variant_group')->havingRaw('COUNT(product_variant_pivot.`id_product_variant`) = ' . count($arrTmp))->first();

                            if ($checkExisting) {
                                $result['updated']++;
                                $dt_insert = [];
                                $delete = ProductVariantPivot::where('id_product_variant_group', $checkExisting['id_product_variant_group'])->delete();
                                if ($delete) {
                                    foreach ($arrTmp as $val) {
                                        $dt_insert[] = [
                                            'id_product_variant_group' => $checkExisting['id_product_variant_group'],
                                            'id_product_variant' => $val
                                        ];
                                    }
                                    ProductVariantPivot::insert($dt_insert);
                                } else {
                                    $result['no_update']++;
                                }
                            } else {
                                $result['create']++;
                                $create = ProductVariantGroup::create(
                                    [
                                        'id_product' => $products['id_product'],
                                        'product_variant_group_code' => 'GENERATEBYSYSTEM_' . $products['product_code'] . '_' . implode('', $arrTmp),
                                        'product_variant_group_price' => 0,
                                        'product_variant_group_visibility' => 'Visible'
                                    ]
                                );
                                if ($create) {
                                    $dt_insert = [];
                                    foreach ($arrTmp as $val) {
                                        $dt_insert[] = [
                                            'id_product_variant_group' => $create['id_product_variant_group'],
                                            'id_product_variant' => $val
                                        ];
                                    }
                                    ProductVariantPivot::insert($dt_insert);
                                }
                            }
                        }
                    }
                }
            }
        }
        //update all product
        RefreshVariantTree::dispatch([])->allOnConnection('database');

        $response = [];

        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' product variant group';
        }
        if ($result['create']) {
            $response[] = 'Create ' . $result['create'] . ' new product variant group';
        }
        if ($result['no_update']) {
            $response[] = $result['no_update'] . ' product variant group not updated';
        }
        if ($result['failed']) {
            $response[] = 'Failed create ' . $result['failed'] . ' product variant group';
        }
        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function combinations($arrays, $i = 0)
    {
        if (!isset($arrays[$i])) {
            return array();
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }

        // get combinations from subsequent arrays
        $tmp = $this->combinations($arrays, $i + 1);

        $result = array();

        // concat each array from tmp with each element from $arrays[$i]
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ?
                    array_merge(array($v), $t) :
                    array($v, $t);
            }
        }

        return $result;
    }

    public function exportPrice(Request $request)
    {
        $post = $request->json()->all();
        $different_outlet = Outlet::where('outlet_different_price', 1)->get()->toArray();
        $different_outlet_price = Outlet::select('outlet_code', 'id_product_variant_group', 'product_variant_group_price')
            ->leftJoin('product_variant_group_special_prices', 'outlets.id_outlet', '=', 'product_variant_group_special_prices.id_outlet')
            ->where('outlet_different_price', 1)->get()->toArray();
        $data = ProductVariantGroup::join('products', 'products.id_product', 'product_variant_groups.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', 'product_variant_groups.id_product')
            ->select('products.id_product', 'product_global_price.product_global_price as product_price', 'product_variant_groups.product_variant_group_price as global_price', 'products.product_name', 'products.product_code', 'product_variant_groups.product_variant_group_code', 'product_variant_groups.id_product_variant_group')
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
                'current_product_variant_code' => $pv['product_variant_group_code'],
                'new_product_variant_code' => '',
                'product_variant_group' => $name,
                'global_price' => $pv['global_price']
            ];

            if (empty((int)$pv['global_price']) && !empty($pv['product_price'])) {
                $arrProductVariant[$key]['global_price'] = $pv['product_price'];
            }

            foreach ($different_outlet as $o) {
                $getSpecialPrice = ProductSpecialPrice::where('id_product', $pv['id_product'])->where('id_outlet', $o['id_outlet'])->first();
                $arrProductVariant[$key]['price_' . $o['outlet_code']] = $getSpecialPrice['product_special_price'] ?? 0;
                foreach ($different_outlet_price as $key_o => $o_price) {
                    if ($o_price['id_product_variant_group'] == $pv['id_product_variant_group'] && $o_price['outlet_code'] == $o['outlet_code']) {
                        $arrProductVariant[$key]['price_' . $o['outlet_code']] = $o_price['product_variant_group_price'];
                        unset($key_o);
                    } else {
                        continue;
                    }
                }
            }
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

    public function importPrice(Request $request)
    {
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'create' => 0,
            'no_update' => 0,
            'updated_price' => 0,
            'updated_price_fail' => 0,
            'invalid' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'] ?? [];

        foreach ($data as $key => $value) {
            if (empty($value['current_product_variant_code'])) {
                $result['invalid']++;
                continue;
            }
            if (empty($value['product'])) {
                unset($value['product']);
            }
            if (empty($value['product_variant'])) {
                unset($value['product_variant']);
            }

            $productVariantGroup = ProductVariantGroup::where([
                    'product_variant_group_code' => $value['current_product_variant_code']
                ])->first();
            if (!$productVariantGroup) {
                $result['not_found']++;
                $result['more_msg_extended'][] = "Product variant with code {$value['current_product_variant_code']} not found";
                continue;
            }

            $datUpdate = ['product_variant_group_price' => $value['global_price']];
            if (!empty($value['new_product_variant_code'])) {
                $datUpdate['product_variant_group_code'] = $value['new_product_variant_code'];
            }
            $update1 = ProductVariantGroup::where([
                'id_product_variant_group' => $productVariantGroup['id_product_variant_group']
            ])->update($datUpdate);

            if ($update1) {
                $basePrice = ProductVariantGroup::orderBy('product_variant_group_price', 'asc')->where('id_product', $productVariantGroup['id_product'])->first();
                if (!empty($basePrice)) {
                    $save = ProductGlobalPrice::updateOrCreate(['id_product' => $productVariantGroup['id_product']], ['id_product' => $productVariantGroup['id_product'], 'product_global_price' => $basePrice['product_variant_group_price']]);
                    $save->touch();
                }
                $result['updated']++;
            } else {
                $result['no_update']++;
            }

            foreach ($value as $col_name => $col_value) {
                if (!$col_value) {
                    $col_value = 0;
                }
                if (strpos($col_name, 'price_') !== false) {
                    $outlet_code = str_replace('price_', '', $col_name);
                    $pp = ProductVariantGroupSpecialPrice::join('outlets', 'outlets.id_outlet', '=', 'product_variant_group_special_prices.id_outlet')
                        ->where([
                            'outlet_code' => $outlet_code,
                            'id_product_variant_group' => $productVariantGroup['id_product_variant_group']
                        ])->first();
                    if ($pp) {
                        $id_outlet = $pp['id_outlet'];
                        $update = $pp->update(['product_variant_group_price' => $col_value]);
                    } else {
                        $id_outlet = Outlet::select('id_outlet')->where('outlet_code', $outlet_code)->pluck('id_outlet')->first();
                        if (!$id_outlet) {
                            continue;
                        }
                        $update = ProductVariantGroupSpecialPrice::create([
                            'id_outlet' => $id_outlet,
                            'id_product_variant_group' => $productVariantGroup['id_product_variant_group'],
                            'product_variant_group_price' => $col_value
                        ]);
                    }
                    if ($update) {
                        $baseSpecialPrice = ProductVariantGroup::join('product_variant_group_special_prices as pvgsp', 'pvgsp.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                                    ->orderBy('pvgsp.product_variant_group_price', 'asc')
                                    ->where('id_outlet', $id_outlet)
                                    ->where('id_product', $productVariantGroup['id_product'])
                                    ->select('pvgsp.*')
                                    ->first();
                        if (!empty($baseSpecialPrice)) {
                            $save = ProductSpecialPrice::updateOrCreate(
                                ['id_product' => $productVariantGroup['id_product'], 'id_outlet' => $id_outlet],
                                ['product_special_price' => $baseSpecialPrice['product_variant_group_price'],
                                  'id_product' => $productVariantGroup['id_product'],
                                'id_outlet' => $id_outlet]
                            );
                            $save->touch();
                        }
                        $result['updated_price']++;
                    } else {
                        $result['updated_price_fail']++;
                        $result['more_msg_extended'][] = "Failed set price for product variant group {$value['product_variant_code']} at outlet $outlet_code failed";
                    }
                }
            }
        }

        //update all product
        RefreshVariantTree::dispatch([])->allOnConnection('database');

        $response = [];

        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' product variant group price';
        }
        if ($result['create']) {
            $response[] = 'Create ' . $result['create'] . ' new product variant group';
        }
        if ($result['no_update']) {
            $response[] = $result['no_update'] . ' product variant group not updated';
        }
        if ($result['failed']) {
            $response[] = 'Failed create ' . $result['failed'] . ' product variant group';
        }

        if ($result['updated_price']) {
            $response[] = 'Update ' . $result['updated_price'] . ' product price outlet';
        }

        if ($result['updated_price_fail']) {
            $response[] = 'Update ' . $result['updated_price_fail'] . ' product price outlet fail';
        }
        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function deleteVariantFromProduct(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['product_code']) && !empty($post['product_code'])) {
            $getId = Product::where('product_code', $post['product_code'])->first();
            $getVariants = ProductVariantGroup::where('id_product', $getId['id_product'])->pluck('id_product_variant_group')->toArray();
            $delete = ProductVariantPivot::whereIn('id_product_variant_group', $getVariants)->delete();
            if ($delete) {
                $delete = ProductVariantGroup::where('id_product', $getId['id_product'])->delete();
            }
            RefreshVariantTree::dispatch(['type' => 'specific_product', 'id_product' => $getId['id_product']])->allOnConnection('database');
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteProductVariantGroup(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_product_variant_group']) && !empty($post['id_product_variant_group'])) {
            $getIdProduct = ProductVariantGroup::where('id_product_variant_group', $post['id_product_variant_group'])->first();
            $delete = ProductVariantPivot::where('id_product_variant_group', $post['id_product_variant_group'])->delete();
            if ($delete) {
                $delete = ProductVariantGroup::where('id_product_variant_group', $post['id_product_variant_group'])->delete();
            }
            RefreshVariantTree::dispatch(['type' => 'specific_product', 'id_product' => $getIdProduct['id_product']])->allOnConnection('database');
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listProductWithVariant(Request $request)
    {
        $data = Product::select('products.*', DB::raw('(Select COUNT(pvg.id_product_variant_group) from product_variant_groups pvg where pvg.id_product = products.id_product) as count_product_variant_group'));

        if ($keyword = ($request->search['value'] ?? false)) {
            $data->where('product_code', 'like', '%' . $keyword . '%')
                ->orWhere('product_name', 'like', '%' . $keyword . '%');
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }
}
