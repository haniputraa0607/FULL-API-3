<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\NewsProduct;
use App\Http\Models\TransactionConsultation;
use App\Http\Models\TransactionProduct;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierBrand;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use Modules\Favorite\Entities\Favorite;
use Modules\Merchant\Entities\Merchant;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\Product\Entities\ProductProductPromoCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Product\Entities\ProductWholesaler;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\UserRatingPhoto;
use Validator;
use Hash;
use DB;
use Mail;
use Image;
use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\Brand;
use Modules\Product\Http\Requests\product\Create;
use Modules\Product\Http\Requests\product\Update;
use Modules\Product\Http\Requests\product\Delete;
use Modules\Product\Http\Requests\product\UploadPhoto;
use Modules\Product\Http\Requests\product\UpdatePhoto;
use Modules\Product\Http\Requests\product\DeletePhoto;
use Modules\Product\Http\Requests\product\Import;
use Modules\Product\Http\Requests\product\UpdateAllowSync;
use Modules\PromoCampaign\Entities\UserPromo;
use App\Http\Models\Deal;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\Subscription\Entities\Subscription;

use function Clue\StreamFilter\fun;

class ApiProductController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->management_merchant = "Modules\Merchant\Http\Controllers\ApiMerchantManagementController";
    }

    public $saveImage = "img/product/item/";

    public function checkInputProduct($post = [], $type = null)
    {
        $data = [];

        if (empty($post['id_product_category']) || isset($post['id_product_category'])) {
            if (empty($post['id_product_category'])) {
                $data['id_product_category'] = null;
            } else {
                $data['id_product_category'] = $post['id_product_category'];
            }
        }

        if (isset($post['product_name'])) {
            $data['product_name'] = $post['product_name'];
        }
        if (isset($post['product_name_pos'])) {
            $data['product_name_pos'] = $post['product_name_pos'];
        }
        if (isset($post['product_description'])) {
            $data['product_description'] = $post['product_description'];
        }
        if (isset($post['product_video'])) {
            $data['product_video'] = $post['product_video'];
        }
        if (isset($post['product_price'])) {
            $data['product_price'] = $post['product_price'];
        }
        if (isset($post['product_weight'])) {
            $data['product_weight'] = $post['product_weight'];
        }
        if (isset($post['product_visibility'])) {
            $data['product_visibility'] = $post['product_visibility'];
        }
        if (isset($post['product_order'])) {
            $data['product_order'] = $post['product_order'];
        }

        if (isset($post['product_length'])) {
            $data['product_length'] = $post['product_length'];
        }

        if (isset($post['product_width'])) {
            $data['product_width'] = $post['product_width'];
        }

        if (isset($post['product_height'])) {
            $data['product_height'] = $post['product_height'];
        }

        if (isset($post['need_recipe_status'])) {
            $data['need_recipe_status'] = $post['need_recipe_status'];
        } else {
            $data['need_recipe_status'] = 0;
        }

        return $data;
    }

    /**
     * cari urutan ke berapa
     */
    public function searchLastSorting($id_product_category = null)
    {
        $sorting = Product::select('position')->orderBy('position', 'DESC');

        if (is_null($id_product_category)) {
            $sorting->whereNull('id_product_category');
        } else {
            $sorting->where('id_product_category', $id_product_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        } else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->position)) {
                return 1;
            } else {
                $sorting = $sorting->position + 1;
                return $sorting;
            }
        }
    }

    public function priceUpdate(Request $request)
    {
        $post = $request->json()->all();
        $date_time = date('Y-m-d H:i:s');
        foreach ($post['id_product_price'] as $key => $id_product_price) {
            if ($id_product_price == 0) {
                $update = ProductPrice::create(['id_product' => $post['id_product'],
                                                'id_outlet' => $post['id_outlet'][$key],
                                                'product_price' => $post['product_price'][$key],
                                                'product_price_base' => $post['product_price_base'][$key],
                                                'product_price_tax' => $post['product_price_tax'][$key],
                                                'product_stock_status' => $post['product_stock_status'][$key],
                                                'product_visibility' => $post['product_visibility'][$key]
                                                ]);
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $post['id_outlet'][$key],
                    'date_time' => $date_time,
                    'new_status' => $post['product_stock_status'][$key],
                    'id_outlet_app_otp' => null
                ]);
            } else {
                $pp = ProductPrice::where('id_product_price', '=', $id_product_price)->first();
                if (!$pp) {
                    continue;
                }
                $old_status = $pp->product_stock_status;
                if (strtolower($old_status) != strtolower($post['product_stock_status'][$key])) {
                    $create = ProductStockStatusUpdate::create([
                        'id_product' => $post['id_product'],
                        'id_user' => $request->user()->id,
                        'user_type' => 'users',
                        'id_outlet' => $post['id_outlet'][$key],
                        'date_time' => $date_time,
                        'new_status' => $post['product_stock_status'][$key],
                        'id_outlet_app_otp' => null
                    ]);
                }
                $update = ProductPrice::where('id_product_price', '=', $id_product_price)->update(['product_price' => $post['product_price'][$key], 'product_price_base' => $post['product_price_base'][$key], 'product_price_tax' => $post['product_price_tax'][$key],'product_stock_status' => $post['product_stock_status'][$key],'product_visibility' => $post['product_visibility'][$key]]);
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updateProductDetail(Request $request)
    {
        $post = $request->json()->all();
        $date_time = date('Y-m-d H:i:s');
        foreach ($post['id_product_detail'] as $key => $id_product_detail) {
            if ($id_product_detail == 0) {
                $update = ProductDetail::create(['id_product' => $post['id_product'],
                    'id_outlet' => $post['id_outlet'][$key],
                    'product_stock_status' => $post['product_detail_stock_status'][$key],
                    'product_visibility' => $post['product_detail_visibility'][$key]
                ]);
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $post['id_outlet'][$key],
                    'date_time' => $date_time,
                    'new_status' => $post['product_detail_stock_status'][$key],
                    'id_outlet_app_otp' => null
                ]);
            } else {
                $pp = ProductDetail::where('id_product_detail', '=', $id_product_detail)->first();
                if (!$pp) {
                    continue;
                }
                $old_status = $pp->product_stock_status;
                if (strtolower($old_status) != strtolower($post['product_detail_stock_status'][$key])) {
                    $create = ProductStockStatusUpdate::create([
                        'id_product' => $post['id_product'],
                        'id_user' => $request->user()->id,
                        'user_type' => 'users',
                        'id_outlet' => $post['id_outlet'][$key],
                        'date_time' => $date_time,
                        'new_status' => $post['product_detail_stock_status'][$key],
                        'id_outlet_app_otp' => null
                    ]);
                }
                $update = ProductDetail::where('id_product_detail', '=', $id_product_detail)->update(['product_detail_stock_status' => $post['product_detail_stock_status'][$key],'product_detail_visibility' => $post['product_detail_visibility'][$key]]);
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updatePriceDetail(Request $request)
    {
        $post = $request->json()->all();

        foreach ($post['id_product_special_price'] as $key => $id_product_special_price) {
            if ($id_product_special_price == 0) {
                if (!is_null($post['product_price'][$key])) {
                    $update = ProductSpecialPrice::create(['id_product' => $post['id_product'],
                        'id_outlet' => $post['id_outlet'][$key],
                        'product_special_price' => str_replace(".", "", $post['product_price'][$key])
                    ]);
                }
            } else {
                $pp = ProductSpecialPrice::where('id_product_special_price', '=', $id_product_special_price)->first();
                if (!$pp) {
                    continue;
                }
                if (!is_null($post['product_price'][$key])) {
                    $update = ProductSpecialPrice::where('id_product_special_price', '=', $id_product_special_price)
                        ->update(['product_special_price' => str_replace(".", "", $post['product_price'][$key])]);
                }
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function categoryAssign(Request $request)
    {
        $post = $request->json()->all();
        foreach ($post['id_product'] as $key => $idprod) {
            $count = BrandProduct::where('id_product', $idprod)->count();
            if ($post['id_product_category'][$key] == 0) {
                $update = Product::where('id_product', '=', $idprod)->update(['id_product_category' => null, 'product_name' => $post['product_name'][$key]]);
                if ($count) {
                    BrandProduct::where(['id_product' => $idprod])->update(['id_product_category' => null]);
                } else {
                    BrandProduct::create(['id_product' => $idprod,'id_product_category' => null]);
                }
            } else {
                $update = Product::where('id_product', '=', $idprod)->update(['id_product_category' => $post['id_product_category'][$key], 'product_name' => $post['product_name'][$key]]);
                if ($count) {
                    BrandProduct::where(['id_product' => $idprod])->update(['id_product_category' => $post['id_product_category'][$key]]);
                } else {
                    BrandProduct::create(['id_product' => $idprod,'id_product_category' => $post['id_product_category'][$key]]);
                }
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * Export data product
     * @param Request $request Laravel Request Object
     */
    public function import(Request $request)
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
        switch ($post['type']) {
            case 'global':
                // update or create if not exist
                $data = $post['data'] ?? [];
                $check_brand = Brand::where(['id_brand' => $post['id_brand'],'code_brand' => $data['code_brand'] ?? ''])->exists();
                if ($check_brand) {
                    foreach ($data['products'] as $key => $value) {
                        if (empty($value['product_code'])) {
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if (empty($value['product_name'])) {
                            unset($value['product_name']);
                        }
                        if (empty($value['product_description'])) {
                            unset($value['product_description']);
                        }
                        $product = Product::where('product_code', $value['product_code'])->first();
                        if ($product) {
                            if ($product->update($value)) {
                                $result['updated']++;
                            } else {
                                $result['no_update']++;
                            }
                        } else {
                            $product = Product::create($value);
                            if ($product) {
                                $result['create']++;
                            } else {
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Product with product code {$value['product_code']} failed to be created";
                                continue;
                            }
                        }
                        $update = BrandProduct::updateOrCreate([
                            'id_brand' => $post['id_brand'],
                            'id_product' => $product->id_product
                        ]);
                    }
                } else {
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'detail':
                // update only, never create
                $data = $post['data'] ?? [];
                $check_brand = Brand::where(['id_brand' => $post['id_brand'],'code_brand' => $data['code_brand'] ?? ''])->first();
                if ($check_brand) {
                    foreach ($data['products'] as $key => $value) {
                        if (empty($value['product_code'])) {
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if (empty($value['product_name'])) {
                            unset($value['product_name']);
                        }
                        if (empty($value['product_description'])) {
                            unset($value['product_description']);
                        }
                        if (empty($value['position'])) {
                            unset($value['position']);
                        }
                        if (empty($value['product_visibility'])) {
                            unset($value['product_visibility']);
                        }
                        $product = Product::join('brand_product', 'products.id_product', '=', 'brand_product.id_product')
                            ->where([
                                'id_brand' => $check_brand->id_brand,
                                'product_code' => $value['product_code']
                            ])->first();
                        if (!$product) {
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product with product code {$value['product_code']} in selected brand not found";
                            continue;
                        }
                        if (empty($value['product_category_name'])) {
                            unset($value['product_category_name']);
                        } else {
                            $pc = ProductCategory::where('product_category_name', $value['product_category_name'])->first();
                            if (!$pc) {
                                $result['create_category']++;
                                $pc = ProductCategory::create([
                                    'product_category_name' => $value['product_category_name']
                                ]);
                            }
                            $value['id_product_category'] = $pc->id_product_category;
                            unset($value['product_category_name']);
                        }
                        $update1 = $product->update($value);
                        if ($value['id_product_category'] ?? false) {
                            $update2 = BrandProduct::where('id_product', $product->id_product)->update(['id_product_category' => $value['id_product_category']]);
                        }
                        if ($update1 || $update2) {
                            $result['updated']++;
                        } else {
                            $result['no_update']++;
                        }
                    }
                } else {
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'price':
                // update only, never create
                $data = $post['data'] ?? [];
                $check_brand = Brand::where(['id_brand' => $post['id_brand'],'code_brand' => $data['code_brand'] ?? ''])->first();
                if ($check_brand) {
                    $global_outlets = Outlet::select('id_outlet', 'outlet_code')->where([
                        'outlet_different_price' => 0
                    ])->get();
                    foreach ($data['products'] as $key => $value) {
                        if (empty($value['product_code'])) {
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if (empty($value['product_name'])) {
                            unset($value['product_name']);
                        }
                        if (empty($value['product_description'])) {
                            unset($value['product_description']);
                        }
                        if (empty($value['global_price'])) {
                            unset($value['global_price']);
                        }
                        $product = Product::join('brand_product', 'products.id_product', '=', 'brand_product.id_product')
                            ->where([
                                'id_brand' => $check_brand->id_brand,
                                'product_code' => $value['product_code']
                            ])->first();
                        if (!$product) {
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product with product code {$value['product_code']} in selected brand not found";
                            continue;
                        }
                        $update1 = $product->update($value);
                        if ($update1) {
                            $result['updated']++;
                        } else {
                            $result['no_update']++;
                        }
                        if ($value['global_price'] ?? false) {
                            foreach ($global_outlets as $outlet) {
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
                                        $result['more_msg_extended'][] = "Failed set price for product {$value['product_code']} at outlet {$outlet->outlet_code} failed";
                                    }
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
                                        $result['more_msg_extended'][] = "Failed create new price for product {$value['product_code']} at outlet $outlet_code failed";
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
                                    $result['more_msg_extended'][] = "Failed set price for product {$value['product_code']} at outlet $outlet_code failed";
                                }
                            }
                        }
                    }
                } else {
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'modifier-price':
                // update only, never create
                $data = $post['data'] ?? [];
                $check_brand = Brand::where(['id_brand' => $post['id_brand'],'code_brand' => $data['code_brand'] ?? ''])->first();
                if ($check_brand) {
                    $global_outlets = Outlet::select('id_outlet', 'outlet_code')->where([
                        'outlet_different_price' => 0
                    ])->get();
                    foreach ($data['products'] as $key => $value) {
                        if (empty($value['code'])) {
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if (empty($value['name'])) {
                            unset($value['name']);
                        } else {
                            $value['text'] = $value['name'];
                            unset($value['name']);
                        }
                        if (empty($value['type'])) {
                            unset($value['type']);
                        }
                        if (empty($value['global_price'])) {
                            unset($value['global_price']);
                        }
                        $product = ProductModifier::select('product_modifiers.*')->leftJoin('product_modifier_brands', 'product_modifiers.id_product_modifier', '=', 'product_modifier_brands.id_product_modifier')
                            ->where('code', $value['code'])->where(function ($q) use ($post) {
                                $q->where('id_brand', $post['id_brand'])->orWhere('modifier_type', '<>', 'Global Brand');
                            })->first();
                        if (!$product) {
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product modifier with code {$value['code']} in selected brand not found";
                            continue;
                        }
                        $update1 = $product->update($value);
                        if ($update1) {
                            $result['updated']++;
                        } else {
                            $result['no_update']++;
                        }
                        if ($value['global_price'] ?? false) {
                            $update = ProductModifierGlobalPrice::updateOrCreate([
                                'id_product_modifier' => $product->id_product_modifier], [
                                'product_modifier_price' => $value['global_price']
                                ]);
                            if ($update) {
                                $result['updated_price']++;
                            } else {
                                if ($update !== 0) {
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set global price for product modifier {$value['code']}";
                                }
                            }
                        }
                        foreach ($value as $col_name => $col_value) {
                            if (!$col_value) {
                                continue;
                            }
                            if (strpos($col_name, 'price_') !== false) {
                                $outlet_code = str_replace('price_', '', $col_name);
                                $pp = ProductModifierPrice::join('outlets', 'outlets.id_outlet', '=', 'product_modifier_prices.id_outlet')
                                ->where([
                                    'outlet_code' => $outlet_code,
                                    'id_product_modifier' => $product->id_product_modifier
                                ])->first();
                                if ($pp) {
                                    $update = $pp->update(['product_modifier_price' => $col_value]);
                                } else {
                                    $id_outlet = Outlet::select('id_outlet')->where('outlet_code', $outlet_code)->pluck('id_outlet')->first();
                                    if (!$id_outlet) {
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed create new price for product modifier {$value['code']} at outlet $outlet_code failed";
                                        continue;
                                    }
                                    $update = ProductModifierPrice::create([
                                        'id_outlet' => $id_outlet,
                                        'id_product_modifier' => $product->id_product_modifier,
                                        'product_modifier_price' => $col_value
                                    ]);
                                }
                                if ($update) {
                                    $result['updated_price']++;
                                } else {
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set price for product modifier {$value['code']} at outlet $outlet_code failed";
                                }
                            }
                        }
                    }
                } else {
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product modifier\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'modifier':
                // update only, never create
                $data = $post['data'] ?? [];
                $check_brand = Brand::where(['id_brand' => $post['id_brand'],'code_brand' => $data['code_brand'] ?? ''])->first();
                if ($check_brand) {
                    foreach ($data['products'] as $key => $value) {
                        if (empty($value['code'])) {
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if (empty($value['name'])) {
                            unset($value['name']);
                        } else {
                            $value['text'] = $value['name'];
                            unset($value['name']);
                        }
                        if (empty($value['type'])) {
                            unset($value['type']);
                        }
                        $product = ProductModifier::select('product_modifiers.*')->leftJoin('product_modifier_brands', 'product_modifiers.id_product_modifier', '=', 'product_modifier_brands.id_product_modifier')
                            ->where('code', $value['code'])->where(function ($q) use ($post) {
                                $q->where('id_brand', $post['id_brand'])->orWhere('modifier_type', '<>', 'Global Brand');
                            })->first();
                        if (!$product) {
                            $value['modifier_type'] = 'Global Brand';
                            $product = ProductModifier::create($value);
                            if ($product) {
                                ProductModifierBrand::create(['id_product_modifier' => $product->id_product_modifier,'id_brand' => $post['id_brand']]);
                                $result['create']++;
                            } else {
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Product modifier with code {$value['code']} failed to be created";
                            }
                            continue;
                        }
                        $update1 = $product->update($value);
                        if ($product->modifier_type == 'Global Brand') {
                            ProductModifierBrand::updateOrCreate(['id_product_modifier' => $product->id_product_modifier,'id_brand' => $post['id_brand']]);
                        }
                        if ($update1) {
                            $result['updated']++;
                        } else {
                            $result['no_update']++;
                        }
                    }
                } else {
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product modifier\'s brand does not match with selected brand']
                    ];
                }
                break;

            default:
                # code...
                break;
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

    /**
     * Export data product
     * @param Request $request Laravel Request Object
     */
    public function export(Request $request)
    {
        $post = $request->json()->all();
        switch ($post['type']) {
            case 'global':
                $data['brand'] = Brand::where('id_brand', $post['id_brand'])->first();
                $data['products'] = Product::select('product_code', 'product_name', 'product_description')
                    ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
                    ->where('id_brand', $post['id_brand'])
                    ->where('product_type', 'product')
                    ->groupBy('products.id_product')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                break;

            case 'detail':
                $data['brand'] = Brand::where('id_brand', $post['id_brand'])->first();
                $data['products'] = Product::select('product_categories.product_category_name', 'products.position', 'product_code', 'product_name', 'product_description', 'products.product_visibility')
                    ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
                    ->where('id_brand', $post['id_brand'])
                    ->where('product_type', 'product')
                    ->leftJoin('product_categories', 'product_categories.id_product_category', '=', 'brand_product.id_product_category')
                    ->groupBy('products.id_product')
                    ->groupBy('product_category_name')
                    ->orderBy('product_category_name')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                break;

            case 'price':
                $different_outlet = Outlet::select('outlet_code', 'id_product', 'product_special_price.product_special_price as product_price')
                    ->leftJoin('product_special_price', 'outlets.id_outlet', '=', 'product_special_price.id_outlet')
                    ->where('outlet_different_price', 1)->get();
                $do = MyHelper::groupIt($different_outlet, 'outlet_code', null, function ($key, &$val) {
                    $val = MyHelper::groupIt($val, 'id_product');
                    return $key;
                });
                $data['brand'] = Brand::where('id_brand', $post['id_brand'])->first();
                $data['products'] = Product::select('products.id_product', 'product_code', 'product_name', 'product_description', 'product_global_price.product_global_price as global_price')
                    ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
                    ->leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                    ->where('id_brand', $post['id_brand'])
                    ->where('product_type', 'product')
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
                break;

            case 'modifier-price':
                $subquery = str_replace('?', '0', ProductModifierGlobalPrice::select(\DB::raw('id_product_modifier,product_modifier_price as global_price'))
                    ->groupBy('id_product_modifier')
                    ->toSql());
                $different_outlet = Outlet::select('outlet_code', 'id_product_modifier', 'product_modifier_price')
                    ->leftJoin('product_modifier_prices', 'outlets.id_outlet', '=', 'product_modifier_prices.id_outlet')
                    ->where('outlet_different_price', 1)->get();
                $do = MyHelper::groupIt($different_outlet, 'outlet_code', null, function ($key, &$val) {
                    $val = MyHelper::groupIt($val, 'id_product_modifier');
                    return $key;
                });
                $data['brand'] = Brand::where('id_brand', $post['id_brand'])->first();
                $data['products'] = ProductModifier::select('product_modifiers.id_product_modifier', 'type', 'code', 'text as name', 'global_prices.global_price')
                    ->leftJoin('product_modifier_brands', 'product_modifier_brands.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->leftJoin(DB::raw('(' . $subquery . ') as global_prices'), 'product_modifiers.id_product_modifier', '=', 'global_prices.id_product_modifier')
                    ->whereNotIn('type', ['Modifier Group'])
                    ->where(function ($q) use ($post) {
                        $q->where('id_brand', $post['id_brand'])
                            ->orWhere('modifier_type', '<>', 'Global Brand');
                    })
                    ->orderBy('type')
                    ->orderBy('text')
                    ->orderBy('product_modifiers.id_product_modifier')
                    ->distinct()
                    ->get();
                foreach ($data['products'] as $key => &$product) {
                    $inc = 0;
                    foreach ($do as $outlet_code => $x) {
                        $inc++;
                        $product['price_' . $outlet_code] = $x[$product['id_product_modifier']][0]['product_modifier_price'] ?? '';
                    }
                    unset($product['id_product_modifier']);
                }
                break;

            case 'modifier':
                $data['brand'] = Brand::where('id_brand', $post['id_brand'])->first();
                $data['products'] = ProductModifier::select('type', 'code', 'text as name')
                    ->leftJoin('product_modifier_brands', 'product_modifier_brands.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->whereNotIn('type', ['Modifier Group'])
                    ->where(function ($q) use ($post) {
                        $q->where('id_brand', $post['id_brand'])
                            ->orWhere('modifier_type', '<>', 'Global Brand');
                    })
                    ->orderBy('type')
                    ->orderBy('text')
                    ->orderBy('product_modifiers.id_product_modifier')
                    ->distinct()
                    ->get();
                break;

            default:
                # code...
                break;
        }
        return MyHelper::checkGet($data);
    }

    /* Pengecekan code unique */
    public function cekUnique($id, $code)
    {
        $cek = Product::where('product_code', $code)->first();

        if (empty($cek)) {
            return true;
        } else {
            if ($cek->id_product == $id) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * list product
     */
    public function listProduct(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_outlet'])) {
            $product = Product::join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
                                ->leftJoin('product_special_price', function ($query) use ($post) {
                                        $query->on('product_special_price.id_product', '=', 'products.id_product')
                                            ->where('product_special_price.id_outlet', '=', $post['id_outlet']);
                                })
                                    ->where('product_detail.id_outlet', '=', $post['id_outlet'])
                                    ->where('product_detail.product_detail_visibility', '=', $post['visibility'] ?? 'Visible')
                                    ->where('product_detail.product_detail_status', '=', 'Active')
                                    ->where('products.product_type', 'product')
                                    ->with(['category', 'discount']);

            if (isset($post['visibility'])) {
                $product = $product->whereNotNull('id_product_category')->select('products.*');
                unset($post['id_outlet']);
            }
        } else {
            if (isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price') {
                $product = Product::with(['category', 'discount', 'product_special_price', 'global_price'])->where('products.product_type', 'product');
            } elseif (isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail') {
                $product = Product::with(['category', 'discount', 'product_detail'])->where('products.product_type', 'product');
            } else {
                $product = Product::with(['category', 'discount', 'product_detail', 'product_wholesaler'])->where('products.product_type', 'product');
            }
        }

        if (!empty($post['check_status'])) {
            $product = $product->join('product_detail', 'product_detail.id_product', 'products.id_product')
                ->where('product_detail_stock_status', 'Available');
        }

        if (!empty($post['outlet_detail'])) {
            $product = Product::join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
                ->where('product_detail.id_outlet', '=', $post['id_outlet'])->with('category')->select('products.*');
        }

        if (isset($post['id_product'])) {
            $product->with('category')->where('products.id_product', $post['id_product'])->with(['brands']);
        }

        if (isset($post['product_code'])) {
            $product->with(['global_price','product_special_price','product_tags','outlet','brands','product_promo_categories' => function ($q) {
                $q->select('product_promo_categories.id_product_promo_category');
            }])->where('products.product_code', $post['product_code']);
        }

        if (isset($post['update_price']) && $post['update_price'] == 1) {
            $product->where('product_variant_status', 0);
        }

        if (isset($post['product_name'])) {
            $product->where('products.product_name', 'LIKE', '%' . $post['product_name'] . '%');
        }

        if (isset($post['orderBy'])) {
            $product = $product->orderBy($post['orderBy']);
        } else {
            $product = $product->orderBy('position');
        }

        if (isset($post['admin_list'])) {
            $product = $product->where('product_type', 'product')->with(['brands', 'outlet']);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'id_outlet') {
                            $product->whereHas('outlet', function ($query) use ($row) {
                                $query->where('id_outlet', $row['operator']);
                            });
                        } elseif ($row['operator'] == '=' || empty($row['parameter'])) {
                            $product->where($row['subject'], (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                        } else {
                            $product->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                        }
                    }
                }
            } else {
                $product->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'id_outlet') {
                                $row->orWhereHas('outlet', function ($query) use ($row) {
                                    $query->where('id_outlet', $row['operator']);
                                });
                            } elseif ($row['operator'] == '=' || empty($row['parameter'])) {
                                $subquery->orWhere($row['subject'], (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                            } else {
                                $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                });
            }
        }

        if (isset($post['pagination'])) {
            $product = $product->orderBy('products.created_at', 'desc')->paginate(10);
        } else {
            $product = $product->get();
        }

        if (!empty($product)) {
            foreach ($product as $key => $value) {
                $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("' . config('url.storage_url_api') . '", product_photo)), "' . config('url.storage_url_api') . 'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
            }
        }

        $product = $product->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    public function listProductImage(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['image']) && $post['image'] == 'null') {
            $product = Product::leftJoin('product_photos', 'product_photos.id_product', '=', 'products.id_product')
                            ->whereNull('product_photos.product_photo')->get();
        } else {
            $product = Product::get();
            if (!empty($product)) {
                foreach ($product as $key => $value) {
                    unset($product[$key]['product_price_base']);
                    unset($product[$key]['product_price_tax']);
                    $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("' . config('url.storage_url_api') . '", product_photo)), "' . config('url.storage_url_api') . 'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
                }
            }
        }

        $product = $product->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    public function imageOverride(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['status'])) {
            try {
                Setting::where('key', 'image_override')->update(['value' => $post['status']]);
                return response()->json(MyHelper::checkGet('true'));
            } catch (\Exception $e) {
                return response()->json(MyHelper::checkGet($e));
            }
        }

        $setting = Setting::where('key', 'image_override')->first();

        if (!$setting) {
            Setting::create([
                'key'       => 'image_override',
                'value'     => 0
            ]);

            $setting = 'false';
        } else {
            if ($setting->value == 0) {
                $setting = 'false';
            } else {
                $setting = 'true';
            }
        }

        return response()->json(MyHelper::checkGet($setting));
    }

    /**
     * create  product
     */
    public function create(Create $request)
    {
        $post = $request->json()->all();

        // check data
        $data = $this->checkInputProduct($post, $type = "create");
        // return $data;
        $save = Product::create($data);

        if ($save) {
            $listOutlet = Outlet::get()->toArray();
            foreach ($listOutlet as $outlet) {
                $dataPrice = [];
                $dataPrice['id_product'] = $save->id_product;
                $dataPrice['id_outlet'] = $outlet['id_outlet'];
                $dataPrice['product_price'] = null;
                // $data['product_visibility'] = 'Visible';

                ProductPrice::create($dataPrice);
            }

            if (is_array($brands = $data['product_brands'] ?? false)) {
                foreach ($brands as $id_brand) {
                    BrandProduct::create([
                        'id_product' => $save['id_product'],
                        'id_brand' => $id_brand,
                        'id_product_category' => $data['id_product_category']
                    ]);
                }
            }

            //create photo
            if (isset($post['photo'])) {
                $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $dataPhoto['product_photo'] = $upload['path'];
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload image']
                    ];

                    return response()->json($result);
                }

                $dataPhoto['id_product']          = $save->id_product;
                $dataPhoto['product_photo_order'] = $this->cekUrutanPhoto($save['id_product']);
                $save                             = ProductPhoto::create($dataPhoto);
            }
            if (isset($post['product_global_price'])) {
                ProductGlobalPrice::updateOrCreate(
                    ['id_product' => $save['id_product']],
                    ['product_global_price' => str_replace(".", "", $post['product_global_price'])]
                );
            }
        }

        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update product
     */
    public function update(Update $request)
    {
        $post = $request->json()->all();

        // check data
        DB::beginTransaction();
        $data = $this->checkInputProduct($post);
        $data['product_visibility'] = $post['product_visibility'];
        $save = Product::where('id_product', $post['id_product'])->update($data);

        if ($save) {
            //update wholesaler
            ProductWholesaler::where('id_product', $post['id_product'])->delete();
            if (empty($data['product_variant_status']) && !empty($post['product_wholesaler'])) {
                $post['wholesaler_price'] = $post['product_wholesaler'];
                $insertWholesaler = [];

                foreach ($post['wholesaler_price'] as $wholesaler) {
                    if ($wholesaler['minimum'] <= 1) {
                        return response()->json(['status' => 'fail', 'messages' => ['Minimum wholesaler must be more than one']]);
                    }

                    $priceProductWholesaler = str_replace('.', '', $wholesaler['unit_price'] ?? 0);
                    if (!empty($wholesaler['discount_percent']) && !empty($wholesaler['unit_price_before_discount'])) {
                        $wholesaler['unit_price_before_discount'] = str_replace('.', '', $wholesaler['unit_price_before_discount']);
                        $wholesaler['discount_percent'] = str_replace('.', '', $wholesaler['discount_percent']);
                        $discount = $wholesaler['unit_price_before_discount'] * ($wholesaler['discount_percent'] / 100);
                        $priceProductWholesaler = (int)($wholesaler['unit_price_before_discount'] - $discount);
                    }
                    $insertWholesaler[] = [
                        'id_product' => $post['id_product'],
                        'product_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                        'product_wholesaler_unit_price' => $priceProductWholesaler ?? 0,
                        'wholesaler_unit_price_before_discount' => $wholesaler['unit_price_before_discount'] ?? 0,
                        'wholesaler_unit_price_discount_percent' => $wholesaler['discount_percent'] ?? 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }


                $arrayColumn = array_column($insertWholesaler, 'product_wholesaler_minimum');
                $withoutDuplicates = array_unique($arrayColumn);
                $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
                if (!empty($duplicates)) {
                    return response()->json(['status' => 'fail', 'messages' => ["minimum can't be the same"]]);
                }

                ProductWholesaler::insert($insertWholesaler);
            }

            $code = Product::where('id_product', $post['id_product'])->first();
            if (isset($post['photo'])) {
                //delete all photo
                $delete = $this->deletePhoto($post['id_product_photo']);
                    //create photo
                    $upload = MyHelper::uploadPhotoAllSize($post['photo'], 'img/product/' . $code['product_code'] . '/');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    ProductPhoto::where('id_product', $post['id_product'])->where('id_product_photo', $post['id_product_photo'])->update(['product_photo' => $upload['path']]);
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload image']
                    ];

                    return response()->json($result);
                }
            }

            if (isset($post['base_price']) && !empty($post['base_price'])) {
                $price = str_replace(".", "", $post['base_price'] ?? 0);
                $discountPercent = str_replace(".", "", $post['base_price_discount_percent'] ?? 0);
                $priceBeforeDiscount = str_replace(".", "", $post['base_price_before_discount'] ?? 0);
                if (!empty($discountPercent)) {
                    $discount = $priceBeforeDiscount * ($discountPercent / 100);
                    $price = (int)($priceBeforeDiscount - $discount);
                }

                ProductGlobalPrice::updateOrCreate(
                    ['id_product' => $post['id_product']],
                    [
                        'product_global_price' => $price,
                        'global_price_before_discount' => $priceBeforeDiscount,
                        'global_price_discount_percent' => $discountPercent
                    ]
                );
            }

            $detailUpdate['product_detail_visibility'] = $post['product_visibility'];
            if (isset($post['stock'])) {
                $detailUpdate['product_detail_stock_item'] = $post['stock'];
                $detailUpdate['product_detail_stock_status'] = ($post['stock'] > 0 ? 'Available' : 'Sold Out');
            }
            ProductDetail::where('id_product', $post['id_product'])->update($detailUpdate);
        }
        if ($save) {
            DB::commit();
        } else {
            DB::rollBack();
        }


        return response()->json(MyHelper::checkUpdate($save));
    }

    /**
     * delete product
     */
    public function delete(Delete $request)
    {
        $product = Product::with('prices')->find($request->json('id_product'));
        $check = $this->checkDeleteProduct($request->json('id_product'));

        if ($check) {
            // delete photo
            $this->deletePhotoAll($request->json('id_product'));

            // delete product
            $post['id_product'] = $request->json('id_product');
            $delete = Product::where('id_product', $post['id_product'])->delete();

            if ($delete) {
                ProductDetail::where('id_product', $post['id_product'])->delete();
                ProductGlobalPrice::where('id_product', $post['id_product'])->delete();
                $idProductVariantGroup = ProductVariantGroup::where('id_product', $post['id_product'])->pluck('id_product_variant_group')->toArray();
                ProductVariantGroup::where('id_product', $post['id_product'])->delete();
                ProductVariantGroupDetail::whereIn('id_product_variant_group', $idProductVariantGroup)->delete();
                $idProductVariant = ProductVariantPivot::whereIn('id_product_variant_group', $idProductVariantGroup)->pluck('id_product_variant')->toArray();
                ProductVariant::whereIn('id_product_variant', $idProductVariant)->delete();

                $result = [
                    'status' => 'success',
                    'product' => [
                        'id_product' => $product['id_product'],
                        'plu_id' => $product['product_code'],
                        'product_name' => $product['product_name'],
                        'product_name_pos' => $product['product_name_pos'],
                        'product_prices' => $product['prices'],
                    ],
                ];
            } else {
                $result = ['status' => 'fail', 'messages' => ['failed to delete data']];
            }

            return response()->json($result);
        } else {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['product has been used.']
            ]);
        }
    }

    /**
     * delete photo product
     */
    public function deletePhoto($id)
    {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product_photo', $id)->get()->toArray();

        if (!empty($dataPhoto)) {
            foreach ($dataPhoto as $key => $value) {
                MyHelper::deletePhoto($value['product_photo']);
            }
        }

        return true;
    }

    public function deletePhotoAll($id)
    {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product', $id)->get()->toArray();

        if (!empty($dataPhoto)) {
            foreach ($dataPhoto as $key => $value) {
                MyHelper::deletePhoto($value['product_photo']);
            }
        }

        return true;
    }

    /**
     * checking delete
     */
    public function checkDeleteProduct($id)
    {

        // jika true semua maka boleh dihapus
        if (($this->checkAtNews($id)) && ($this->checkAtTrx($id)) && $this->checkAtDiskon($id)) {
            return true;
        } else {
        // klo ada yang sudah digunakan
            return false;
        }
    }

    // check produk di transaksi
    public function checkAtTrx($id)
    {
        $check = TransactionProduct::where('id_product', $id)->count();

        if ($check > 0) {
            return false;
        } else {
            return true;
        }
    }

    // check product di diskon
    public function checkAtDiskon($id)
    {
        $check = ProductDiscount::where('id_product', $id)->count();

        if ($check > 0) {
            return false;
        } else {
            return true;
        }
    }

    // check product di news
    public function checkAtNews($id)
    {
        $check = NewsProduct::where('id_product', $id)->count();

        if ($check > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * upload photo
     */
    public function uploadPhotoProduct(UploadPhoto $request)
    {
        $post = $request->json()->all();

        $data = [];

        if (isset($post['photo'])) {
            $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['product_photo'] = $upload['path'];
            } else {
                $result = [
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return response()->json($result);
            }
        }

        if (empty($data)) {
            return reponse()->json([
                'status' => 'fail',
                'messages' => ['fail save to database']
            ]);
        } else {
            $data['id_product']          = $post['id_product'];
            $data['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
            $save                        = ProductPhoto::create($data);

            return response()->json(MyHelper::checkCreate($save));
        }
    }

    public function uploadPhotoProductAjax(Request $request)
    {
        $post = $request->json()->all();
        $data = [];
        $checkCode = Product::where('product_code', $post['name'])->first();
        if ($checkCode) {
            $checkSetting = Setting::where('key', 'image_override')->first();
            if ($checkSetting['value'] == 1) {
                if (isset($post['detail'])) {
                    if ($checkCode->product_photo_detail && file_exists($checkCode->product_photo_detail)) {
                        unlink($checkCode->product_photo_detail);
                    }
                } else {
                    $productPhoto = ProductPhoto::where('id_product', $checkCode->id_product)->first();
                    if (file_exists($productPhoto->product_photo)) {
                        unlink($productPhoto->product_photo);
                    }
                }
            }

            if (isset($post['detail'])) {
                $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/product/item/detail/', 720, 360, $post['name'] . '-' . strtotime("now"));
            } else {
                $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300, $post['name'] . '-' . strtotime("now"));
            }

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['product_photo'] = $upload['path'];
            } else {
                $result = [
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];
                return response()->json($result);
            }
        }
        if (empty($data)) {
            return reponse()->json([
                'status' => 'fail',
                'messages' => ['fail save to database']
            ]);
        } else {
            if (isset($post['detail'])) {
                $save = Product::where('id_product', $checkCode->id_product)->update(['product_photo_detail' => $data['product_photo']]);
            } else {
                $data['id_product']          = $checkCode->id_product;
                $data['product_photo_order'] = $this->cekUrutanPhoto($checkCode->id_product);
                $save                        = ProductPhoto::updateOrCreate(['id_product' => $checkCode->id_product], $data);
            }
            return response()->json(MyHelper::checkCreate($save));
        }
    }

    /*
    cek urutan
    */
    public function cekUrutanPhoto($id)
    {
        $cek = ProductPhoto::where('id_product', $id)->orderBy('product_photo_order', 'DESC')->first();

        if (empty($cek)) {
            $cek = 1;
        } else {
            $cek = $cek->product_photo_order + 1;
        }

        return $cek;
    }

    /**
     * update photo
     */
    public function updatePhotoProduct(UpdatePhoto $request)
    {
        $update = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->update([
            'product_photo_order' => $request->json('product_photo_order')
        ]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * delete photo
     */
    public function deletePhotoProduct(DeletePhoto $request)
    {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->get()->toArray();

        $delete    = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->delete();

        if (!empty($dataPhoto)) {
            MyHelper::deletePhoto($dataPhoto[0]['product_photo']);
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* harga */
    public function productPrices(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if ($post['id_outlet'] == 0) {
            if (isset($post['product_price'])) {
                $dataGlobalPrice['product_global_price'] = $post['product_price'];
            }
            $save = ProductGlobalPrice::updateOrCreate([
                'id_product' => $data['id_product']
            ], $dataGlobalPrice);
        } else {
            if (isset($post['product_price'])) {
                $dataSpecialPrice['product_special_price'] = $post['product_price'];
            }
            $save = ProductSpecialPrice::updateOrCreate([
                'id_product' => $data['id_product'],
                'id_outlet'  => $data['id_outlet']
            ], $dataSpecialPrice);
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function allProductPrices(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        $getAllProduct = Product::where('product_type', 'product')->pluck('id_product');
        if ($post['id_outlet'] == 0) {
            if (isset($post['product_price'])) {
                $dataGlobalPrice['product_global_price'] = $post['product_price'];
            }
            foreach ($getAllProduct as $id_product) {
                $save = ProductGlobalPrice::updateOrCreate([
                    'id_product' => $id_product
                ], $dataGlobalPrice);
            }
        } else {
            if (isset($post['product_price'])) {
                $dataSpecialPrice['product_special_price'] = $post['product_price'];
            }
            foreach ($getAllProduct as $id_product) {
                $save = ProductSpecialPrice::updateOrCreate([
                    'id_product' => $id_product,
                    'id_outlet'  => $data['id_outlet']
                ], $dataSpecialPrice);
            }
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    public function productDetail(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['product_visibility']) || $post['product_visibility'] == null) {
            if ($post['product_visibility'] == null) {
                $data['product_detail_visibility'] = 'Hidden';
            } else {
                $data['product_detail_visibility'] = $post['product_visibility'];
            }
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if (isset($post['product_stock_status'])) {
            $data['product_detail_stock_status'] = $post['product_stock_status'];
        }
        $product = ProductDetail::where([
            'id_product' => $data['id_product'],
            'id_outlet'  => $data['id_outlet']
        ])->first();

        if (($data['product_detail_stock_status'] ?? false) && (($data['product_detail_stock_status'] ?? false) != $product['product_detail_stock_status'] ?? false)) {
            $create = ProductStockStatusUpdate::create([
                'id_product' => $data['id_product'],
                'id_user' => $request->user()->id,
                'user_type' => 'users',
                'id_outlet' => $data['id_outlet'],
                'date_time' => date('Y-m-d H:i:s'),
                'new_status' => $data['product_detail_stock_status'],
                'id_outlet_app_otp' => null
            ]);
        }

        $save = ProductDetail::updateOrCreate([
            'id_product' => $data['id_product'],
            'id_outlet'  => $data['id_outlet']
        ], $data);

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function allProductDetail(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['product_visibility']) || $post['product_visibility'] == null) {
            if ($post['product_visibility'] == null) {
                $data['product_detail_visibility'] = 'Hidden';
            } else {
                $data['product_detail_visibility'] = $post['product_visibility'];
            }
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if (isset($post['product_stock_status'])) {
            $data['product_detail_stock_status'] = $post['product_stock_status'];
        }

        $getAllProduct = Product::where('product_type', 'plastic')->pluck('id_product');

        foreach ($getAllProduct as $id_product) {
            $product = ProductDetail::where([
                'id_product' => $id_product,
                'id_outlet'  => $data['id_outlet']
            ])->first();

            if (($data['product_detail_stock_status'] ?? false) && (($data['product_detail_stock_status'] ?? false) != $product['product_detail_stock_status'] ?? false)) {
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $id_product,
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $data['id_outlet'],
                    'date_time' => date('Y-m-d H:i:s'),
                    'new_status' => $data['product_detail_stock_status'],
                    'id_outlet_app_otp' => null
                ]);
            }

            $save = ProductDetail::updateOrCreate([
                'id_product' => $id_product,
                'id_outlet'  => $data['id_outlet']
            ], $data);
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function updateAllowSync(UpdateAllowSync $request)
    {
        $post = $request->json()->all();

        if ($post['product_allow_sync'] == "true") {
            $allow = '1';
        } else {
            $allow = '0';
        }
        $update = Product::where('id_product', $post['id_product'])->update(['product_allow_sync' => $allow]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function visibility(Request $request)
    {
        $post = $request->json()->all();
        foreach ($post['id_visibility'] as $key => $value) {
            if ($value) {
                $id = explode('/', $value);
                $save = ProductDetail::updateOrCreate(['id_product' => $id[0], 'id_outlet' => $id[1]], ['product_detail_visibility' => $post['visibility']]);
                if (!$save) {
                    return response()->json(MyHelper::checkUpdate($save));
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    /* product position */
    public function positionProductAssign(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['product_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product id is required']
            ];
        }
        // update position
        foreach ($post['product_ids'] as $key => $product_id) {
            $update = Product::find($product_id)->update(['position' => $key + 1]);
        }

        return ['status' => 'success'];
    }

    public function photoDefault(Request $request)
    {
        $post = $request->json()->all();


        //product detail
        if (isset($post['photo_detail'])) {
            if (!file_exists('img/product/item/detail')) {
                mkdir('img/product/item/detail', 0777, true);
            }
            $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/product/item/detail/', 720, 360, 'default', '.png');
        }

        //product
        if (isset($post['photo'])) {
            if (!file_exists('img/product/item/')) {
                mkdir('img/product/item/', 0777, true);
            }
            $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/product/item/', 300, 300, 'default', '.png');
        }

        if (isset($upload['status']) && $upload['status'] == "success") {
            $result = [
                'status'   => 'success',
            ];
        } else {
            $result = [
                'status'   => 'fail',
                'messages' => ['fail upload image']
            ];
        }
        return response()->json($result);
    }

    public function updateVisibility(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_product'])) {
            return [
                'status' => 'fail',
                'messages' => ['Id product is required']
            ];
        }
        if (!isset($post['product_visibility'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product visibility is required']
            ];
        }
        // update visibility
        Product::where('id_product', $post['id_product'])->update(['product_visibility' => $post['product_visibility']]);
        $update = ProductDetail::where('id_product', $post['id_product'])->update(['product_detail_visibility' => $post['product_visibility']]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function listProductPriceByOutlet(Request $request, $id_outlet)
    {
        $product = Product::with(['all_prices' => function ($q) use ($id_outlet) {
            $q->where('id_outlet', $id_outlet);
        }])->get();
        return response()->json(MyHelper::checkGet($product));
    }

    public function listProductDetailByOutlet(Request $request, $id_outlet)
    {
        $product = Product::with(['product_detail' => function ($q) use ($id_outlet) {
            $q->where('id_outlet', $id_outlet);
        }])->get();
        return response()->json(MyHelper::checkGet($product));
    }

    public function getNextID($id)
    {
        $product = Product::where('id_product', '>', $id)->orderBy('id_product')->first();
        return response()->json(MyHelper::checkGet($product));
    }
    public function detail(Request $request)
    {
        $post = $request->json()->all();
        //get product
        $product = Product::join('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
                    ->select('id_merchant', 'products.id_product_category', 'product_categories.product_category_name', 'id_product', 'product_code', 'product_name', 'product_description', 'product_code', 'product_variant_status', 'need_recipe_status', 'product_count_transaction');


        if(!empty($post['id_product'])){
            $product = $product->where('id_product', $post['id_product'])->first();
        }else{
            $product = $product->where('product_code', $post['product_code'])->first();
        }

        if (!$product) {
            return MyHelper::checkGet([]);
        } else {
            // toArray error jika $product Null,
            $product = $product->toArray();
        }
        $post['id_product'] = $product['id_product'];
        $product['sold'] = app($this->management_merchant)->productCount($product['product_count_transaction']);
        unset($product['product_count_transaction']);

        $merchant = Merchant::where('id_merchant', $product['id_merchant'])->first();
        $post['id_outlet'] = $merchant['id_outlet'] ?? null;
        $outlet = Outlet::find($post['id_outlet']);
        if (!$outlet) {
            return MyHelper::checkGet([], 'Outlet not found');
        }
        unset($product['product_detail']);
        $post['id_product_category'] = $product['id_product_category'] ?? 0;
        if ($post['id_product_category'] === 0) {
            return MyHelper::checkGet([]);
        }

        $product['outlet_name'] = $outlet['outlet_name'];
        $product['outlet_is_closed'] = (empty($outlet['outlet_is_closed']) ? false : true);
        $product['product_price'] = 0;
        $productGlobalPrice = ProductGlobalPrice::where('id_product', $post['id_product'])->first();
        if ($productGlobalPrice) {
            $product['product_price'] = $productGlobalPrice['product_global_price'];
            $product['product_label_discount'] = $productGlobalPrice['global_price_discount_percent'];
            $product['product_label_price_before_discount'] = $productGlobalPrice['global_price_before_discount'];
        }

        if ($product['product_variant_status']) {
            $selectedVariant = ProductVariantGroup::join('product_variant_group_details', 'product_variant_group_details.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                                ->where('id_outlet', $post['id_outlet'])
                                ->where('id_product', $product['id_product'])
                                ->where('product_variant_group_details.product_variant_group_visibility', 'Visible')
                                ->where('product_variant_group_stock_status', 'Available')
                                ->orderBy('product_variant_group_price', 'asc')->first();
            $product['product_price'] = $selectedVariant['product_variant_group_price'] ?? $product['product_price'];
            $product['product_label_discount'] = $selectedVariant['product_variant_group_price'] ?? $product['product_price'];
            $product['product_label_price_before_discount'] = $selectedVariant['variant_group_price_before_discount'] ?? $product['product_price'];
            $post['id_product_variant_group'] = $selectedVariant['id_product_variant_group'] ?? null;
            $product['id_product_variant_group'] = $post['id_product_variant_group'];
        } else {
            $product['stock_item'] = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_detail_stock_item'] ?? 0;
        }

        $product['can_buy_status'] = true;
        if ($product['need_recipe_status'] == 1) {
            $idUser = $request->user()->id;
            $checkRecipe = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
                ->whereNotNull('completed_at')
                ->whereNotIn('consultation_status', ['canceled'])
                ->where('id_user', $idUser)->where('product_type', 'Drug')->where('id_product', $product['id_product'])
                ->get()->toArray();

            $qtyCanBuy = 0;
            foreach ($checkRecipe as $valueRecipe) {
                $maxQty = ($valueRecipe['recipe_redemption_limit'] ?? 0) * ($valueRecipe['qty_product'] ?? 0);
                $calculate = $maxQty - $valueRecipe['qty_product_redeem'];
                $qtyCanBuy = $qtyCanBuy + $calculate;
            }

            if (!empty($checkRecipe) && $qtyCanBuy > 0) {
                $product['need_recipe_status'] = 0;
            }
        }

        $product['variants'] = Product::getVariantTree($product['id_product'], $outlet, false, $product['product_price'], $product['product_variant_status'])['variants_tree'] ?? null;

        if ($product['product_variant_status'] && empty($product['variants'])) {
            return MyHelper::checkGet([], 'Variants not available');
        }

        if (!$product['product_variant_status']) {
            $wholesaler = ProductWholesaler::where('id_product', $product['id_product'])->select('id_product_wholesaler', 'product_wholesaler_minimum as minimum', 'product_wholesaler_unit_price as unit_price', 'wholesaler_unit_price_before_discount as unit_price_before_discount', 'wholesaler_unit_price_discount_percent as discount_percent')->get()->toArray();
            foreach ($wholesaler as $key => $w) {
                $wholesaler[$key]['unit_price'] = (int)$w['unit_price'];
            }
            $product['wholesaler_price'] = $wholesaler;
        }

        if ($post['id_product_variant_group'] ?? false) {
            $product['selected_available'] = (!!Product::getVariantParentId($post['id_product_variant_group'], $product['variants'], $post['selected']['extra_modifiers'] ?? [])) ? 1 : 0;
        }

        $product['stock_status'] = 'Available';
        if (
            (empty($product['stock_item']) && $product['product_variant_status'] == 0) ||
            ($product['product_variant_status'] == 1 && empty($post['id_product_variant_group']))
        ) {
            $product['stock_status'] = 'Sold Out';
        }

        unset($product['product_variant_status']);
        $product['product_price'] = (int)$product['product_price'];
        $product['id_outlet'] = $post['id_outlet'];

        $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
        $product['image'] = (empty($image['url_product_photo']) ? config('url.storage_url_api') . 'img/product/item/default.png' : $image['url_product_photo']);
        $imageDetail = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->whereNotIn('id_product_photo', [$image['id_product_photo'] ?? null])->get()->toArray();
        $imagesDetail = [];
        foreach ($imageDetail as $dt) {
            $imagesDetail[] = $dt['url_product_photo'];
        }
        $product['image_detail'] = $imagesDetail;
        $ratings = [];
        $getRatings = UserRating::join('users', 'users.id', 'user_ratings.id_user')
                    ->select('user_ratings.*', 'users.name', 'users.photo')
                    ->where('id_product', $product['id_product'])->orderBy('user_ratings.created_at', 'desc')->limit(5)->get()->toArray();
        foreach ($getRatings as $rating) {
            $getPhotos = UserRatingPhoto::where('id_user_rating', $rating['id_user_rating'])->get()->toArray();
            $photos = [];
            foreach ($getPhotos as $dt) {
                $photos[] = $dt['url_user_rating_photo'];
            }
            $currentOption = explode(',', $rating['option_value']);
            $ratings[] = [
                "date" => MyHelper::dateFormatInd($rating['created_at'], false, false, false),
                "user_name" => $rating['name'],
                "user_photo" => config('url.storage_url_api') . (!empty($rating['photo']) ? $rating['photo'] : 'img/user_photo_default.png'),
                "rating_value" => $rating['rating_value'],
                "suggestion" => $rating['suggestion'],
                "option_value" => $currentOption,
                "photos" => $photos
            ];
        }
        $product['ratings'] = $ratings;
        $product['total_rating'] = round(UserRating::where('id_product', $product['id_product'])->average('rating_value') ?? 0, 1);
        $product['can_buy_own_product'] = true;
        $favorite = Favorite::where('id_product', $product['id_product'])->where('id_user', $request->user()->id)->first();
        $product['favorite'] = (!empty($favorite) ? true : false);
        if ($request->user()->id == $merchant['id_user']) {
            $product['can_buy_own_product'] = false;
        }

        return MyHelper::checkGet($product);
    }

    public function detailReview(Request $request)
    {
        $post = $request->json()->all();
        //get product
        $product = Product::join('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
            ->select('id_product', 'product_code', 'product_name', 'product_categories.product_category_name')
            ->where('id_product', $post['id_product'])->first();

        if (!$product) {
            return MyHelper::checkGet([]);
        } else {
            $product = $product->toArray();
        }


        $getRatings = UserRating::join('users', 'users.id', 'user_ratings.id_user')
            ->select('user_ratings.*', 'users.name', 'users.photo')
            ->where('id_product', $product['id_product'])->orderBy('user_ratings.created_at', 'desc')->paginate($post['pagination_total_row'] ?? 10)->toArray();
        foreach ($getRatings['data'] ?? [] as $key => $rating) {
            $getPhotos = UserRatingPhoto::where('id_user_rating', $rating['id_user_rating'])->get()->toArray();
            $photos = [];
            foreach ($getPhotos as $dt) {
                $photos[] = $dt['url_user_rating_photo'];
            }
            $currentOption = explode(',', $rating['option_value']);
            $rating = [
                "date" => MyHelper::dateFormatInd($rating['created_at'], false, false, false),
                "user_name" => $rating['name'],
                "user_photo" => config('url.storage_url_api') . (!empty($rating['photo']) ? $rating['photo'] : 'img/user_photo_default.png'),
                "rating_value" => $rating['rating_value'],
                "suggestion" => $rating['suggestion'],
                "option_value" => $currentOption,
                "photos" => $photos
            ];

            $getRatings['data'][$key] = $rating;
        }

        $product['ratings'] = $getRatings;
        return MyHelper::checkGet($product);
    }

    protected function addPromoFlag($variant_tree, $id_variant_group_promo)
    {
        if (!$variant_tree || !$id_variant_group_promo) {
            return $variant_tree;
        }
        if ($variant_tree['childs'] ?? false) {
            foreach ($variant_tree['childs'] as $key => $child) {
                if ($variant_tree['childs'][$key]['variant'] ?? false) {
                    $variant_tree['childs'][$key]['variant'] = $this->addPromoFlag($variant_tree['childs'][$key]['variant'], $id_variant_group_promo);
                    // read flag promo from child
                    if ($variant_tree['childs'][$key]['variant']['promo'] ?? false) {
                        // flag promo last child
                        $variant_tree['childs'][$key]['promo'] = 1;
                        // set flag promo for parent
                        $variant_tree['promo'] = 1;
                        // unset promo from child
                        unset($variant_tree['childs'][$key]['variant']['promo']);
                    }
                } else {
                    if (in_array($variant_tree['childs'][$key]['id_product_variant_group'] ?? false, $id_variant_group_promo)) {
                        // flag promo last child
                        $variant_tree['childs'][$key]['promo'] = 1;
                        // flag promo parent of last child
                        $variant_tree['promo'] = 1;
                    }
                }
            }
        }
        return $variant_tree;
    }

    public function ajaxProductBrand(Request $request)
    {
        $post = $request->except('_token');
        $q = (new Product())->newQuery();
        if ($post['select'] ?? false) {
            $q->select($post['select']);
        }

        if ($condition = $post['condition'] ?? false) {
            $this->filterList($q, $condition['rules'] ?? '', $condition['operator'] ?? 'and');
        }
        return MyHelper::checkGet($q->get());
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $newRule = [];
        $where = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=','parameter' => $var['parameter'] ?? null];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $newRule[$var['subject']][] = $var1;
        }

        if ($rules = $newRule['id_brand'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('brands', function ($query) use ($rul) {
                    $query->where('brands.id_brand', $rul['operator'], $rul['parameter']);
                });
            }
        }
    }

    public function listProductAjaxSimple()
    {
        return MyHelper::checkGet(Product::select('id_product', 'product_name')->get());
    }

    public function getProductByBrand(Request $request)
    {
        $post = $request->json()->all();
        $data = Product::join('brand_product', 'products.id_product', '=', 'brand_product.id_product');

        if (isset($post['id_brand']) && !empty($post['id_brand'])) {
            $data->where('brand_product.id_brand', $post['id_brand']);
        }
        $data = $data->get()->toArray();

        return response()->json(MyHelper::checkGet($data));
    }

    public function bestSeller()
    {
        $result = $this->listProductMerchantBestSeller([]);
        return response()->json(['status' => 'success', 'result' => $result]);
    }

    public function listProductMerchantBestSeller($query = [])
    {
        $list = Product::select(
            'products.id_product',
            'products.product_name',
            'products.product_code',
            'products.product_count_transaction',
            'products.product_description',
            'product_variant_status',
            'product_global_price as product_price',
            'global_price_discount_percent as product_label_discount',
            'global_price_before_discount as product_label_price_before_discount',
            'product_detail_stock_status as stock_status',
            'need_recipe_status',
            'outlets.id_outlet',
            'product_categories.product_category_name'
        )
                ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
                ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
                ->join('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
                ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
                ->where('outlet_status', 'Active')
                ->where('outlet_is_closed', 0)
                ->where('product_global_price', '>', 0)
                ->where('product_visibility', 'Visible')
                ->where('product_detail_visibility', 'Visible')
                ->where('product_count_transaction', '>', 0)
                ->where('product_detail_stock_status', 'Available')
                ->orderBy('product_count_transaction', 'desc');

        if (!empty($query['id_outlet'])) {
            $list = $list->where('product_detail.id_outlet', $query['id_outlet']);
        }
        $list = $list->limit(10)->get()->toArray();

        foreach ($list as $key => $product) {
            $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $product['id_outlet'])->first();
            if ($product['product_variant_status']) {
                $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                if (empty($variantTree['base_price'])) {
                    unset($list[$key]);
                    continue;
                }
                $list[$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                $list[$key]['product_label_discount'] = ($variantTree['base_price_discount_percent'] ?? false) ?: $product['product_label_discount'];
                $list[$key]['product_label_price_before_discount'] = ($variantTree['base_price_before_discount'] ?? false) ?: $product['product_label_price_before_discount'];
            }

            unset($list[$key]['id_outlet']);
            unset($list[$key]['product_variant_status']);
            unset($list[$key]['stock_status']);
            $list[$key]['product_price'] = (int)$list[$key]['product_price'];
            $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
            $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            $list[$key]['sold'] = app($this->management_merchant)->productCount($product['product_count_transaction']);
            unset($list[$key]['product_count_transaction']);
        }
        $list = array_values($list);
        $list = array_slice($list, 0, 10);
        shuffle($list);
        return $list;
    }

    public function newest()
    {
        $result = $this->listProductMerchantNewest([]);
        return response()->json(['status' => 'success', 'result' => $result]);
    }

    public function listProductMerchantNewest($query = [])
    {
        $list = Product::select(
            'products.id_product',
            'products.product_name',
            'products.product_code',
            'products.product_description',
            'products.product_count_transaction',
            'product_variant_status',
            'product_global_price as product_price',
            'global_price_discount_percent as product_label_discount',
            'global_price_before_discount as product_label_price_before_discount',
            'product_detail_stock_status as stock_status',
            'need_recipe_status',
            'outlets.id_outlet',
            'product_categories.product_category_name'
        )
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->join('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->where('outlet_status', 'Active')
            ->where('outlet_is_closed', 0)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->where('product_detail_stock_status', 'Available')
            ->orderBy('products.created_at', 'desc');

        if (!empty($query['id_best'])) {
            $list = $list->whereNotIn('products.id_product', $query['id_best']);
        }
        if (!empty($query['id_outlet'])) {
            $list = $list->where('product_detail.id_outlet', $query['id_outlet']);
        }

        $list = $list->limit(10)->get()->toArray();

        foreach ($list as $key => $product) {
            $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $product['id_outlet'])->first();
            if ($product['product_variant_status']) {
                $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                if (empty($variantTree['base_price'])) {
                    unset($list[$key]);
                    continue;
                }
                $list[$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                $list[$key]['product_label_discount'] = ($variantTree['base_price_discount_percent'] ?? false) ?: $product['product_label_discount'];
                $list[$key]['product_label_price_before_discount'] = ($variantTree['base_price_before_discount'] ?? false) ?: $product['product_label_price_before_discount'];
            }

            unset($list[$key]['id_outlet']);
            unset($list[$key]['product_variant_status']);
            unset($list[$key]['stock_status']);
            $list[$key]['product_price'] = (int)$list[$key]['product_price'];
            $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
            $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            $list[$key]['sold'] = app($this->management_merchant)->productCount($product['product_count_transaction']);
            unset($list[$key]['product_count_transaction']);
        }
        $list = array_values($list);
        return $list;
    }

    public function listProductMerchant(Request $request)
    {
        $idUser = $request->user()->id;
        $post = $request->json()->all();

        if (!empty($post['id_outlet'])) {
            $idMerchant = Merchant::where('id_outlet', $post['id_outlet'])->first()['id_merchant'] ?? null;
            if (empty($idMerchant)) {
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }
        }

        $list = Product::leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->join('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
            ->where('outlet_status', 'Active')
            ->where('outlet_is_closed', 0)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->groupBy('products.id_product');

        if (!empty($idMerchant)) {
            $list = $list->where('id_merchant', $idMerchant);
        }

        if (!empty($post['search_key'])) {
            if (strpos($post['search_key'], " ") !== false) {
                $list = $list->whereRaw('MATCH (product_name) AGAINST ("' . $post['search_key'] . '" IN BOOLEAN MODE)');
            } else {
                $list->where('product_name', 'like', '%' . $post['search_key'] . '%');
            }
        }

        if (!empty($post['id_product_category'])) {
            $list = $list->where('product_categories.id_product_category', $post['id_product_category']);
        }

        if (isset($post['all_best_seller']) && $post['all_best_seller']) {
            $list = $list->where('product_count_transaction', '>', 0);
        }

        if (isset($post['all_recommendation']) && $post['all_recommendation']) {
            $list = $list->where('product_recommendation_status', 1);
        }

        $defaultSelect = 1;
        if (!empty($post['filter_sorting'])) {
            $sorting = $post['filter_sorting'];

            if ($sorting == 'relate' && !empty($post['search_key'])) {
                $defaultSelect = 0;
                $list = $list->select(
                    'products.id_product',
                    'products.product_name',
                    'products.product_code',
                    'products.product_description',
                    'product_variant_status',
                    'product_global_price as product_price',
                    'global_price_discount_percent as product_label_discount',
                    'global_price_before_discount as product_label_price_before_discount',
                    'product_detail_stock_status as stock_status',
                    'product_detail.id_outlet',
                    'need_recipe_status',
                    'product_categories.product_category_name',
                    DB::raw('MATCH (product_name) AGAINST ("' . $post['search_key'] . '" IN BOOLEAN MODE) AS relate')
                )
                    ->orderBy('relate', 'desc');
            } elseif ($sorting == 'best seller') {
                $list = $list->orderBy('product_count_transaction', 'desc');
            } elseif ($sorting == 'review') {
                $list = $list->orderBy('total_rating', 'desc');
            } elseif ($sorting == 'newest') {
                $list = $list->orderBy('products.created_at', 'desc');
            } elseif ($sorting == 'recommendation') {
                $list = $list->orderBy('products.product_recommendation_status', 'desc')->orderBy('products.product_name', 'asc');
            }
        } else {
            $list = $list->orderBy('product_count_transaction', 'desc');
        }

        if ($defaultSelect == 1) {
            $list = $list->select(
                'products.id_product',
                'products.product_name',
                'products.product_code',
                'products.product_description',
                'product_variant_status',
                'product_global_price as product_price',
                'global_price_discount_percent as product_label_discount',
                'global_price_before_discount as product_label_price_before_discount',
                'product_detail_stock_status as stock_status',
                'product_detail.id_outlet',
                'need_recipe_status',
                'product_categories.product_category_name',
                'products.product_count_transaction'
            );
        }

        if (!empty($post['filter_category'])) {
            $list = $list->whereIn('product_categories.id_product_category', $post['filter_category']);
        }

        if (!empty($post['filter_min_price'])) {
            $list = $list->where(function ($q) use ($post) {
                $q->where(function ($query1) use ($post) {
                    $query1->where('product_global_price', '>=', $post['filter_min_price'])
                        ->where('product_variant_status', 0);
                });
                $q->orWhereHas('base_price_variant', function ($query2) use ($post) {
                    $query2->where('product_variant_group_price', '>=', $post['filter_min_price']);
                });
            });
        }

        if (!empty($post['filter_max_price'])) {
            $list = $list->where(function ($q) use ($post) {
                $q->where(function ($query1) use ($post) {
                    $query1->where('product_global_price', '<=', $post['filter_max_price'])
                        ->where('product_variant_status', 0);
                });
                $q->orWhereHas('base_price_variant', function ($query2) use ($post) {
                    $query2->where('product_variant_group_price', '<=', $post['filter_max_price']);
                });
            });
        }

        if (!empty($post['pagination'])) {
            $list = $list->paginate($post['pagination_total_row'] ?? 10)->toArray();

            foreach ($list['data'] as $key => $product) {
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if (empty($variantTree['base_price'])) {
                        $list['data'][$key]['stock_status'] = 'Sold Out';
                    }
                    $list['data'][$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                    $list['data'][$key]['product_label_discount'] = ($variantTree['base_price_discount_percent'] ?? false) ?: $product['product_label_discount'];
                    $list['data'][$key]['product_label_price_before_discount'] = ($variantTree['base_price_before_discount'] ?? false) ?: $product['product_label_price_before_discount'];
                }

                unset($list['data'][$key]['id_outlet']);
                unset($list['data'][$key]['product_variant_status']);
                $list['data'][$key]['product_price'] = (int)$list['data'][$key]['product_price'];
                $favorite = Favorite::where('id_product', $product['id_product'])->where('id_user', $idUser)->first();
                $list['data'][$key]['favorite'] = (!empty($favorite) ? true : false);
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list['data'][$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
                $list['data'][$key]['sold'] = app($this->management_merchant)->productCount($product['product_count_transaction']);
                unset($list['data'][$key]['product_count_transaction']);
            }
            $list['data'] = array_values($list['data']);
        } else {
            $list = $list->get()->toArray();

            foreach ($list as $key => $product) {
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if (empty($variantTree['base_price'])) {
                        $list[$key]['stock_status'] = 'Sold Out';
                    }
                    $list[$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                    $list[$key]['product_label_discount'] = ($variantTree['base_price_discount_percent'] ?? false) ?: $product['product_label_discount'];
                    $list[$key]['product_label_price_before_discount'] = ($variantTree['base_price_before_discount'] ?? false) ?: $product['product_label_price_before_discount'];
                }

                unset($list[$key]['id_outlet']);
                unset($list[$key]['product_variant_status']);
                $list[$key]['product_price'] = (int)$list[$key]['product_price'];
                $favorite = Favorite::where('id_product', $product['id_product'])->where('id_user', $idUser)->first();
                $list[$key]['favorite'] = (!empty($favorite) ? true : false);
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
                $list[$key]['sold'] = app($this->management_merchant)->productCount($product['product_count_transaction']);
                unset($list['data'][$key]['product_count_transaction']);
            }
            $list = array_values($list);
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function productRecommendation(Request $request)
    {
        $post = $request->json()->all();

        $update = Product::whereNotNull('id_product')->update(['product_recommendation_status' => 0]);
        if ($update) {
            $update = Product::whereIn('id_product', $post['id_product'])->update(['product_recommendation_status' => 1]);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function listProducRecommendation(Request $request)
    {
        $post = $request->json()->all();

        $list = Product::select(
            'products.id_product',
            'products.product_name',
            'products.product_code',
            'products.product_description',
            'product_variant_status',
            'product_global_price as product_price',
            'global_price_discount_percent as product_label_discount',
            'global_price_before_discount as product_label_price_before_discount',
            'product_detail_stock_status as stock_status',
            'product_detail.id_outlet',
            'product_categories.product_category_name',
            'need_recipe_status',
            'product_count_transaction'
        )
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->leftJoin('product_categories', 'product_categories.id_product_category', '=', 'products.id_product_category')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->where('outlet_status', 'Active')
            ->where('outlet_is_closed', 0)
            ->where('product_global_price', '>', 0)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->where('product_recommendation_status', 1)
            ->groupBy('products.id_product');

        if (!empty($post['all'])) {
            $list = $list->get()->toArray();
        } else {
            $list = $list->get()->take(5)->toArray();
        }

        foreach ($list as $key => $product) {
            if ($product['product_variant_status']) {
                $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                if (empty($variantTree['base_price'])) {
                    $list[$key]['stock_status'] = 'Sold Out';
                }
                $list[$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                $list[$key]['product_label_discount'] = ($variantTree['base_price_discount_percent'] ?? false) ?: $product['product_label_discount'];
                $list[$key]['product_label_price_before_discount'] = ($variantTree['base_price_before_discount'] ?? false) ?: $product['product_label_price_before_discount'];
            }

            unset($list[$key]['id_outlet']);
            unset($list[$key]['product_variant_status']);
            $list[$key]['product_price'] = (int)$list[$key]['product_price'];
            $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
            $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            $list[$key]['sold'] = app($this->management_merchant)->productCount($product['product_count_transaction']);
            unset($list[$key]['product_count_transaction']);
        }
        $list = array_values($list);

        return response()->json(MyHelper::checkGet($list));
    }

    public function detailRecomendation($params)
    {
        $post = $params;

        //get product
        $product = Product::join('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
                    ->select('id_merchant', 'products.id_product_category', 'product_categories.product_category_name', 'id_product', 'product_code', 'product_name', 'product_description', 'product_code', 'product_variant_status', 'need_recipe_status')
                    ->where('id_product', $post['id_product'])->first();

        if (!$product) {
            return MyHelper::checkGet([]);
        } else {
            // toArray error jika $product Null,
            $product = $product->toArray();
        }

        $merchant = Merchant::where('id_merchant', $product['id_merchant'])->first();
        $post['id_outlet'] = $merchant['id_outlet'] ?? null;
        $outlet = Outlet::find($post['id_outlet']);
        if (!$outlet) {
            return MyHelper::checkGet([], 'Outlet not found');
        }
        unset($product['product_detail']);
        $post['id_product_category'] = $product['id_product_category'] ?? 0;
        if ($post['id_product_category'] === 0) {
            return MyHelper::checkGet([]);
        }

        $product['outlet_name'] = $outlet['outlet_name'];
        $product['outlet_is_closed'] = (empty($outlet['outlet_is_closed']) ? false : true);
        $product['product_price'] = 0;
        $productGlobalPrice = ProductGlobalPrice::where('id_product', $post['id_product'])->first();
        if ($productGlobalPrice) {
            $product['product_price'] = $productGlobalPrice['product_global_price'];
        }

        if ($product['product_variant_status']) {
            $selectedVariant = ProductVariantGroup::join('product_variant_group_details', 'product_variant_group_details.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                                ->where('id_outlet', $post['id_outlet'])
                                ->where('id_product', $product['id_product'])
                                ->where('product_variant_group_details.id_product_variant_group', $post['id_product_variant_group'])
                                ->where('product_variant_group_details.product_variant_group_visibility', 'Visible')
                                ->where('product_variant_group_stock_status', 'Available')
                                ->orderBy('product_variant_group_price', 'asc')->first();
            $product['product_price'] = $selectedVariant['product_variant_group_price'] ?? $product['product_price'];
            // $post['id_product_variant_group'] = $selectedVariant['id_product_variant_group']??null;
            $product['id_product_variant_group'] = $post['id_product_variant_group'];
        } else {
            $product['stock_item'] = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_detail_stock_item'] ?? 0;
        }

        $product['can_buy_status'] = true;
        // if($product['need_recipe_status'] == 1){
        //     $idUser = $params['id'];
        //     $checkRecipe = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
        //         ->where('id_user', $idUser)->where('product_type', 'Drug')->where('id_product', $product['id_product'])->first();
        //     $maxQty = ($checkRecipe['recipe_redemption_limit']??0) * ($checkRecipe['qty_product']??0);

        //     if(empty($checkRecipe) || (!empty($checkRecipe) && $checkRecipe['qty_product_redeem'] >= $maxQty)){
        //         $product['can_buy_status'] = false;
        //     }
        // }
        if (isset($product['id_product_variant_group'])) {
            $product['variants'] = Product::getVariant($product['id_product'], $outlet, false, $product['product_price'], $product['product_variant_status'], $product['id_product_variant_group'])['variants_tree'];
        } else {
            $product['variants'] = Product::getVariantTree($product['id_product'], $outlet, false, $product['product_price'], $product['product_variant_status'])['variants_tree'] ?? null;
        }

        if ($product['product_variant_status'] && empty($product['variants'])) {
            return MyHelper::checkGet([], 'Variants not available');
        }

        if (!$product['product_variant_status']) {
            $wholesaler = ProductWholesaler::where('id_product', $product['id_product'])->select('id_product_wholesaler', 'product_wholesaler_minimum as minimum', 'product_wholesaler_unit_price as unit_price')->get()->toArray();
            foreach ($wholesaler as $key => $w) {
                $wholesaler[$key]['unit_price'] = (int)$w['unit_price'];
            }
            $product['wholesaler_price'] = $wholesaler;
        }

        if ($post['id_product_variant_group'] ?? false) {
            $product['selected_available'] = (!!Product::getVariantParentId($post['id_product_variant_group'], $product['variants'], $post['selected']['extra_modifiers'] ?? [])) ? 1 : 0;
        }

        $product['stock_status'] = 'Available';
        if (
            (empty($product['stock_item']) && $product['product_variant_status'] == 0) ||
            ($product['product_variant_status'] == 1 && empty($post['id_product_variant_group']))
        ) {
            $product['stock_status'] = 'Sold Out';
        }

        unset($product['product_variant_status']);
        $product['product_price'] = (int)$product['product_price'];
        $product['id_outlet'] = $post['id_outlet'];

        $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
        $product['image'] = (empty($image['url_product_photo']) ? config('url.storage_url_api') . 'img/product/item/default.png' : $image['url_product_photo']);
        $imageDetail = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->whereNotIn('id_product_photo', [$image['id_product_photo'] ?? null])->get()->toArray();
        $imagesDetail = [];
        foreach ($imageDetail as $dt) {
            $imagesDetail[] = $dt['url_product_photo'];
        }
        $product['image_detail'] = $imagesDetail;
        $ratings = [];
        $getRatings = UserRating::where('id_product', $product['id_product'])->get()->toArray();
        foreach ($getRatings as $rating) {
            $getPhotos = UserRatingPhoto::where('id_user_rating', $rating['id_user_rating'])->get()->toArray();
            $photos = [];
            foreach ($getPhotos as $dt) {
                $photos[] = $dt['url_user_rating_photo'];
            }
            $currentOption = explode(',', $rating['option_value']);
            $ratings[] = [
                "rating_value" => $rating['rating_value'],
                "suggestion" => $rating['suggestion'],
                "option_value" => $currentOption,
                "photos" => $photos
            ];
        }
        $product['ratings'] = $ratings;
        $product['total_rating'] = round(UserRating::where('id_product', $product['id_product'])->average('rating_value') ?? 0, 1);
        $product['can_buy_own_product'] = true;

        if ($params['id_user'] == $merchant['id_user']) {
            $product['can_buy_own_product'] = false;
        }

        if (isset($post['id_user'])) {
            $favorite = Favorite::where('id_product', $product['id_product'])->where('id_user', $post['id_user'])->first();
        } else {
            $favorite = null;
        }
        $product['favorite'] = (!empty($favorite) ? true : false);

        return MyHelper::checkGet($product);
    }
}
