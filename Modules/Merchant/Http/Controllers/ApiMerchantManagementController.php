<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\Subdistricts;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Disburse\Entities\BankAccount;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Entities\MerchantGrading;
use Modules\Merchant\Entities\MerchantLogBalance;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductWholesaler;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupWholesaler;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use DB;
use Image;

class ApiMerchantManagementController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
    }

    public function list(Request $request)
    {
        $post = $request->json()->all();

        $data = Merchant::whereIn('merchant_status', ['Active', 'Inactive'])
            ->leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
            ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->orderBy('merchants.created_at', 'desc');

        if (
            isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])
        ) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('merchants.created_at', '>=', $start_date)
                ->whereDate('merchants.created_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['operator'] == '=' || empty($row['parameter'])) {
                            $data->where($row['subject'], (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                        } else {
                            $data->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                        }
                    }
                }
            } else {
                $data->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['operator'] == '=' || empty($row['parameter'])) {
                                $subquery->orWhere($row['subject'], (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                            } else {
                                $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();
        if (!empty($post['id_merchant'])) {
            $check = Merchant::where('id_merchant', $post['id_merchant'])->first();
            if (empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Detail not found']]);
            }

            $update = Merchant::where('id_merchant', $check['id_merchant'])->update($post);
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function canditateList(Request $request)
    {
        $post = $request->json()->all();

        $data = Merchant::whereNotIn('merchant_status', ['Active', 'Inactive'])
            ->leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
            ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->orderBy('merchants.created_at', 'desc');

        if (
            isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])
        ) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('merchants.created_at', '>=', $start_date)
                ->whereDate('merchants.created_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'merchant_completed_step') {
                            $data->where('merchant_completed_step', $row['operator']);
                        } else {
                            if ($row['operator'] == '=') {
                                $data->where($row['subject'], $row['parameter']);
                            } else {
                                $data->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                }
            } else {
                $data->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'merchant_completed_step') {
                                $subquery->orWhere('merchant_completed_step', $row['operator']);
                            } else {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere($row['subject'], $row['parameter']);
                                } else {
                                    $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                                }
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function canditateUpdate(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['id_merchant'])) {
            $check = Merchant::leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                    ->leftJoin('users', 'users.id', 'merchants.id_user')
                    ->where('id_merchant', $post['id_merchant'])->first();
            if (empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Detail not found']]);
            }
            $type = ($post['action_type'] == 'approve' ? 'Active' : $post['action_type']);
            $update = Merchant::where('id_merchant', $check['id_merchant'])->update(['merchant_status' => ucfirst($type)]);
            if ($update) {
                if ($post['action_type'] == 'approve') {
                    $update = Outlet::where('id_outlet', $check['id_outlet'])->update(['outlet_status' => 'Active']);
                } else {
                    $update = Outlet::where('id_outlet', $check['id_outlet'])->update(['outlet_status' => 'Inactive']);
                }

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    ucfirst($post['action_type']) . ' Merchant',
                    $check['phone'],
                    [
                        'merchant_name' => $check['outlet_name'],
                        'merchant_phone' => $check['outlet_phone'],
                        "merchant_pic_name" => $check['merchant_pic_name'],
                        "merchant_pic_email" => $check['merchant_pic_email'],
                        "merchant_pic_phone" => $check['merchant_pic_phone']
                    ]
                );
            }

            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function store(Request $request)
    {
        $post = $request->json()->all();
        $idUser = $post['id_user'];
        $check = Merchant::where('id_user', $idUser)->first();
        if (!empty($check)) {
            return response()->json(['status' => 'fail', 'messages' => ['Merchant for this user already create']]);
        }

        $phone = $request->json('merchant_phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);
        if (substr($phone, 0, 2) != 62 && substr($phone, 0, 1) != '0') {
            $phone = '0' . $phone;
        }
        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Format nomor telepon tidak valid']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $check = Outlet::where('outlet_phone', $post['merchant_phone'])->first();

        if (!empty($check)) {
            return response()->json(['status' => 'fail', 'messages' => ['Nomor telepon sudah terdaftar']]);
        }

        DB::beginTransaction();

        $create = Merchant::create(
            [
                "id_user" => $idUser,
                "merchant_pic_name" => $post['merchant_pic_name'],
                "merchant_pic_id_card_number" => $post['merchant_pic_id_card_number'],
                "merchant_pic_email" => $post['merchant_pic_email'],
                "merchant_pic_phone" => $phone,
                "merchant_completed_step" => 1
            ]
        );
        if (!$create) {
            return response()->json(['status' => 'fail', 'messages' => ['Failed save data merchant']]);
        }

        $lastOutlet = Outlet::orderBy('outlet_code', 'desc')->first()['outlet_code'] ?? '';
        $lastOutlet = substr($lastOutlet, -5);
        $lastOutlet = (int)$lastOutlet;
        $countCode = $lastOutlet + 1;
        $idSubdis = explode("|", $post['id_subdistrict']);
        $idSubdis = $idSubdis[0] ?? null;

        $dataCreateOutlet = [
            "outlet_code" => 'M' . sprintf("%06d", $countCode),
            "outlet_name" => $post['merchant_name'],
            "outlet_license_number" => $post['merchant_license_number'],
            "outlet_email" => (empty($post['merchant_email']) ? null : $post['merchant_email']),
            "outlet_phone" => $phone,
            "id_city" => $post['id_city'],
            "id_subdistrict" => $idSubdis,
            "outlet_address" => $post['merchant_address'],
            "outlet_postal_code" => (empty($post['merchant_postal_code']) ? null : $post['merchant_postal_code'])
        ];

        $createOutlet = Outlet::create($dataCreateOutlet);
        if (!$createOutlet) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan data outlet']]);
        }

        if (!empty($post['id_brand'])) {
            $checkBrand = Brand::where('id_brand', $post['id_brand'])->first();
            if (!empty($checkBrand)) {
                BrandOutlet::create(['id_outlet' => $createOutlet['id_outlet'], 'id_brand' => $post['id_brand']]);
            }
        }

        Merchant::where('id_merchant', $create['id_merchant'])->update(['id_outlet' => $createOutlet['id_outlet']]);

        DB::commit();
        return response()->json(MyHelper::checkCreate($create));
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['id_merchant'])) {
            $detail = Merchant::with(['merchant_gradings'])->leftJoin('users', 'users.id', 'merchants.id_user')
                    ->leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                    ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
                    ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
                    ->where('id_merchant', $post['id_merchant'])->select('merchants.*', 'users.name', 'users.phone', 'users.email', 'provinces.id_province', 'users.phone', 'outlets.*')
                    ->first();
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function delete(Request $request)
    {
        $post = $request->json()->all();
        if (!empty($post['id_merchant'])) {
            $check = Merchant::where('id_merchant', $post['id_merchant'])->first();
            if ($check['merchant_status'] == 'Active' || $check['merchant_status'] == 'Inactive') {
                return response()->json(['status' => 'fail', 'messages' => ['Can not delete active/inactive merchant']]);
            }
            $del = Merchant::where('id_merchant', $post['id_merchant'])->delete();
            return response()->json(MyHelper::checkDelete($del));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function merchantProductList(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productList($post);
    }

    public function merchantProductCreate(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        if (!empty($post['id_outlet'])) {
            $post['id_user'] = Merchant::where('id_outlet', $post['id_outlet'])->first()['id_user'] ?? null;
        }
        return $this->productCreate($post);
    }

    public function merchantProductUpdate(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productUpdate($post);
    }

    public function merchantProductDetail(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productDetail($post);
    }

    public function merchantProductDelete(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productDelete($post);
    }

    public function merchantProductPhotoDelete(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productPhotoDelete($post);
    }

    public function merchantVariantDelete(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->variantDelete($post);
    }

    public function merchantProductStockDetail(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productStockDetail($post);
    }

    public function merchantProductStockUpdate(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = $request->user()->id;
        return $this->productStockUpdate($post);
    }

    public function productList($post)
    {
        $idUser = $post['id_user'];
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if (empty($checkMerchant)) {
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $products = Product::where('id_merchant', $checkMerchant['id_merchant'])->select('products.id_product', 'product_name');

        if (!empty($post['search_key'])) {
            $products = $products->where('product_name', 'like', '%' . $post['search_key'] . '%');
        }

        $products = $products->paginate(10)->toArray();
        foreach ($products['data'] as $key => $value) {
            $stockItem = ProductDetail::where('id_product', $value['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])->first()['product_detail_stock_item'] ?? 0;
            $stockItemVariant = ProductVariantGroup::join('product_variant_group_details', 'product_variant_group_details.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                ->where('id_product', $value['id_product'])
                ->where('id_outlet', $checkMerchant['id_outlet'])->sum('product_variant_group_stock_item');
            $products['data'][$key]['stock'] = $stockItem + $stockItemVariant;
            $photo = ProductPhoto::where('id_product', $value['id_product'])->orderBy('product_photo_order', 'asc')->first()['product_photo'] ?? '';
            $price = (int)(ProductGlobalPrice::where('id_product', $value['id_product'])->first()['product_global_price'] ?? 0);
            $products['data'][$key]['price'] = 'Rp ' . number_format($price, 0, ",", ".");
            $products['data'][$key]['image'] = (!empty($photo) ? config('url.storage_url_api') . $photo : config('url.storage_url_api') . 'img/default.jpg');
        }
        return response()->json(MyHelper::checkGet($products));
    }

    public function variantCombination(Request $request)
    {
        $post = $request->json()->all();

        if (count($post) <= 2) {
            $arrays = [];
            foreach ($post as $value) {
                $newArr = [];
                foreach ($value['variant_child'] as $child) {
                    $newArr[] = $value['variant_name'] . '|' . $child['variant_name'] . (!empty($child['id_product_variant']) ? '|' . $child['id_product_variant'] : '');
                }

                $arrays[] = $newArr;
            }
            $combinations = app($this->product_variant_group)->combinations($arrays);

            $res = [];
            foreach ($combinations as $combination) {
                $name = [];
                if (!is_array($combination)) {
                    $combination = [$combination];
                }

                $idVariant = [];
                foreach ($combination as $data) {
                    $explode = explode("|", $data);
                    $name[] = $explode[1];
                    if (isset($explode[2])) {
                        $idVariant[] = $explode[2];
                    }
                }

                $idProductVariantGroup = 0;
                if (!empty($idVariant)) {
                    $idProductVariantGroup = ProductVariantPivot::whereIn('id_product_variant', $idVariant)->groupBy('id_product_variant_group')
                            ->havingRaw('COUNT(id_product_variant_group) = ' . count($idVariant))->first()['id_product_variant_group'] ?? 0;
                }

                $wholesaler = [];
                $visibility = 'Hidden';
                $combinationName = implode(' ', $name);
                $databaseName = '';
                $stock = 0;
                $price = 0;
                if (!empty($idProductVariantGroup)) {
                    $wholesaler = ProductVariantGroupWholesaler::where('id_product_variant_group', $idProductVariantGroup)->select('id_product_variant_group_wholesaler', 'variant_wholesaler_minimum as minimum', 'variant_wholesaler_unit_price as unit_price')->get()->toArray();
                    foreach ($wholesaler as $key => $w) {
                        $wholesaler[$key]['unit_price'] = (int)$w['unit_price'];
                    }

                    $detail = ProductVariantGroupDetail::where('id_product_variant_group', $idProductVariantGroup)->first();
                    $visibility = $detail['product_variant_group_visibility'] ?? 'Hidden';
                    $stock = $detail['product_variant_group_stock_item'] ?? 0;

                    $group = ProductVariantGroup::where('id_product_variant_group', $idProductVariantGroup)->first();
                    $databaseName = $group['product_variant_group_name'] ?? '';
                    $price = (int)($group['product_variant_group_price'] ?? 0);
                }

                if ($combinationName != $databaseName) {
                    $idProductVariantGroup = 0;
                    $visibility = 'Hidden';
                    $wholesaler = [];
                    $stock = 0;
                    $price = 0;
                }

                $res[] = [
                    'id_product_variant_group' => $idProductVariantGroup,
                    'name' => implode(' ', $name),
                    'visibility' => ($visibility == 'Hidden' ? 0 : 1),
                    'price' => $price,
                    'stock' => $stock,
                    'data' => $combination,
                    'wholesaler_price' => $wholesaler
                ];
            }

            $result = [
                'variants' => $post,
                'variants_price' => $res
            ];
            return response()->json(MyHelper::checkGet($result));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Maksimal variasi adalah 2']]);
        }
    }

    public function productCreate($post)
    {
        $idUser = $post['id_user'];

        if (empty($post['product_weight']) || empty($post['product_height']) || empty($post['product_width'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Product weight/height/width tidak boleh kosong']]);
        }

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if (empty($checkMerchant)) {
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if (empty($post['image'])) {
            return ['status' => 'fail', 'messages' => ['Tambahkan minimal 1 gambar produk']];
        }

        if (empty($post['id_product_category'])) {
            return ['status' => 'fail', 'messages' => ['Kategori tidak boleh kosong']];
        }

        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $checkMerchant['id_outlet'])->first();
        if (!$outlet) {
            return [
                'status' => 'fail',
                'messages' => ['Outlet not found']
            ];
        }

        if (!empty($post['variant_status']) && empty($post['variants'])) {
            return [
                'status' => 'fail',
                'messages' => ['Variant can not be empty if status variant 1']
            ];
        }

        $product = [
            'id_merchant' => $checkMerchant['id_merchant'],
            'product_code' => 'P' . rand() . '-' . time(),
            'product_name' => $post['product_name'],
            'product_description' => $post['product_description'],
            'id_product_category' => (!empty($post['id_product_category']) ? $post['id_product_category'] : null),
            'product_visibility' => 'Visible',
            'product_status' => 'Active',
            'product_weight' => (!empty($post['product_weight']) ? $post['product_weight'] : 0),
            'product_height' => (!empty($post['product_height']) ? $post['product_height'] : 0),
            'product_width' => (!empty($post['product_width']) ? $post['product_width'] : 0),
            'product_length' => (!empty($post['product_length']) ? $post['product_length'] : 0),
            'product_variant_status' => $post['variant_status'] ?? 0,
            'need_recipe_status' => $post['need_recipe_status'] ?? 0
        ];

        DB::beginTransaction();
        $create = Product::create($product);

        if (!$create) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan product']]);
        }
        $idProduct = $create['id_product'];

        $defaultBrand = BrandOutlet::where('id_outlet', $checkMerchant['id_outlet'])->first()['id_brand'] ?? null;
        if (!empty($defaultBrand)) {
            $checkBrand = Brand::where('id_brand', $defaultBrand)->first();
            if (!empty($checkBrand)) {
                BrandProduct::create(['id_product' => $idProduct, 'id_brand' => $defaultBrand]);
            }
        }

        $img = [];
        if (!empty($post['image'])) {
            $image = $post['image'];
            if (!empty($post['admin'])) {
                $encode = $post['image'];
            } else {
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            }

            $upload = MyHelper::uploadPhotoAllSize($encode, 'img/product/');

            if (isset($upload['status']) && $upload['status'] == "success") {
                $img[] = $upload['path'];
            }
        }

        foreach ($post['image_detail'] ?? [] as $image) {
            $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            $upload = MyHelper::uploadPhotoAllSize($encode, 'img/product/' . $product['product_code'] . '/');

            if (isset($upload['status']) && $upload['status'] == "success") {
                $img[] = $upload['path'];
            }
        }

        $insertImg = [];
        foreach ($img as $j => $img) {
            $insertImg[] = [
                'id_product' => $idProduct,
                'product_photo' => $img,
                'product_photo_order' => $j + 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        ProductPhoto::insert($insertImg);

        if (!empty($post['base_price'])) {
            ProductGlobalPrice::create(['id_product' => $idProduct, 'product_global_price' => $post['base_price']]);
        }

        $stockProduct = $post['stock'] ?? 0;
        ProductDetail::create([
            'id_product' => $idProduct,
            'id_outlet' => $checkMerchant['id_outlet'],
            'product_detail_visibility' => $post['product_visibility'] ?? 'Visible',
            'product_detail_stock_status' => (empty($post['variants']) && empty($stockProduct) ? 'Sold Out' : 'Available'),
            'product_detail_stock_item' => (empty($post['variants']) ? $stockProduct : 0),
        ]);

        $priceVariant = [];
        if (!empty($post['variants']) && !empty($post['variant_status'])) {
            $variants = (array)json_decode($post['variants']);

            $dtVariant = [];
            foreach ($variants['variants'] as $key => $variant) {
                $variant = (array)$variant;
                $createVariant = ProductVariant::create([
                    'product_variant_name' => $variant['variant_name'],
                    'product_variant_visibility' => 'Visible',
                    'product_variant_order' => $key + 1
                ]);
                $dtVariant[$variant['variant_name']]['id'] = $createVariant['id_product_variant'];

                foreach ($variant['variant_child'] as $index => $child) {
                    $child = (array)$child;
                    $child = $child['variant_name'];
                    $insertChild = ProductVariant::create([
                        'id_parent' => $createVariant['id_product_variant'],
                        'product_variant_name' => $child,
                        'product_variant_visibility' => 'Visible',
                        'product_variant_order' => $index + 1
                    ]);

                    $dtVariant[$variant['variant_name']][$child] = $insertChild['id_product_variant'];
                }
            }

            foreach ($variants['variants_price'] as $combination) {
                $combination = (array) $combination;
                $idVariants = [];
                foreach ($combination['data'] as $dt) {
                    $first = explode('|', $dt)[0] ?? '';
                    $second = explode('|', $dt)[1] ?? '';

                    if (isset($dtVariant[$first][$second])) {
                        $idVariants[] = $dtVariant[$first][$second];
                    }
                }

                if (!empty($idVariants)) {
                    $variantGroup = ProductVariantGroup::create([
                        'id_product' => $idProduct,
                        'product_variant_group_code' => 'PV' . time() . '-' . implode('', $idVariants),
                        'product_variant_group_name' => $combination['name'],
                        'product_variant_group_visibility' => (empty($combination['visibility']) ? 'Hidden' : 'Visible'),
                        'product_variant_group_price' => $combination['price']
                    ]);

                    $priceVariant[] = $combination['price'];

                    $insertPivot = [];
                    foreach ($idVariants as $id) {
                        $insertPivot[] = [
                            'id_product_variant' => $id,
                            'id_product_variant_group' => $variantGroup['id_product_variant_group']
                        ];
                    }

                    if (!empty($insertPivot)) {
                        ProductVariantPivot::insert($insertPivot);
                    }

                    ProductVariantGroupDetail::create([
                        'id_product_variant_group' => $variantGroup['id_product_variant_group'],
                        'id_outlet' => $checkMerchant['id_outlet'],
                        'product_variant_group_visibility' => (empty($combination['visibility']) ? 'Hidden' : 'Visible'),
                        'product_variant_group_stock_status' => (empty($combination['stock']) ? 'Sold Out' : 'Available'),
                        'product_variant_group_stock_item' => $combination['stock']]);


                    if (!empty($combination['wholesaler_price'])) {
                        $insertWholesalerVariant = [];
                        foreach ($combination['wholesaler_price'] as $wholesaler) {
                            $wholesaler = (array)$wholesaler;
                            if ($wholesaler['minimum'] <= 1) {
                                DB::rollback();
                                return response()->json(['status' => 'fail', 'messages' => ['Jumlah unit harus lebih dari satu']]);
                            }
                            $insertWholesalerVariant[] = [
                                'id_product_variant_group' => $variantGroup['id_product_variant_group'],
                                'variant_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                                'variant_wholesaler_unit_price' => $wholesaler['unit_price'] ?? 0,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }

                        $arrayColumn = array_column($insertWholesalerVariant, 'variant_wholesaler_minimum');
                        $withoutDuplicates = array_unique($arrayColumn);
                        $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
                        if (!empty($duplicates)) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Minimum tidak boleh sama']]);
                        }

                        ProductVariantGroupWholesaler::insert($insertWholesalerVariant);
                    }
                }
            }
        }

        if (empty($post['variants']) && !empty($post['wholesaler_price'])) {
            $post['wholesaler_price'] = (array)json_decode($post['wholesaler_price']);
            $insertWholesaler = [];

            foreach ($post['wholesaler_price'] as $wholesaler) {
                $wholesaler = (array)$wholesaler;
                if ($wholesaler['minimum'] <= 1) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Jumlah unit harus lebih dari satu']]);
                }

                $insertWholesaler[] = [
                    'id_product' => $idProduct,
                    'product_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                    'product_wholesaler_unit_price' => $wholesaler['unit_price'] ?? 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }


            $arrayColumn = array_column($insertWholesaler, 'product_wholesaler_minimum');
            $withoutDuplicates = array_unique($arrayColumn);
            $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
            if (!empty($duplicates)) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Minimum tidak boleh sama']]);
            }

            ProductWholesaler::insert($insertWholesaler);
        }

        DB::commit();
        $price = $post['base_price'] ?? 0;
        if (!empty($post['variants']) && !empty($post['variant_status'])) {
            $price = min($priceVariant);
        }
        ProductGlobalPrice::updateOrCreate(['id_product' => $idProduct], ['id_product' => $idProduct, 'product_global_price' => $price]);
        return response()->json(MyHelper::checkCreate($create));
    }

    public function productUpdate($post)
    {
        $idUser = $post['id_user'];

        if (!empty($post['id_product'])) {
            if (empty($post['product_weight']) || empty($post['product_height']) || empty($post['product_width'])) {
                return response()->json(['status' => 'fail', 'messages' => ['Product weight/height/width tidak boleh kosong']]);
            }

            $checkMerchant = Merchant::where('id_user', $idUser)->first();
            if (empty($checkMerchant)) {
                return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
            }

            $checkProduct = Product::where('id_product', $post['id_product'])->first();
            if (empty($checkProduct)) {
                return response()->json(['status' => 'fail', 'messages' => ['Data product tidak ditemukan']]);
            }

            if (empty($post['id_product_category']) && empty($checkProduct['id_product_category'])) {
                return ['status' => 'fail', 'messages' => ['Kategori tidak boleh kosong']];
            }

            $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $checkMerchant['id_outlet'])->first();
            if (!$outlet) {
                return [
                    'status' => 'fail',
                    'messages' => ['Outlet not found']
                ];
            }

            if (!empty($post['variant_status']) && empty($post['variants'])) {
                return [
                    'status' => 'fail',
                    'messages' => ['Variant can not be empty if status variant 1']
                ];
            }

            $product = [
                'product_name' => $post['product_name'],
                'product_description' => $post['product_description'],
                'id_product_category' => (!empty($post['id_product_category']) ? $post['id_product_category'] : null),
                'product_weight' => (!empty($post['product_weight']) ? $post['product_weight'] : 0),
                'product_height' => (!empty($post['product_height']) ? $post['product_height'] : 0),
                'product_width' => (!empty($post['product_width']) ? $post['product_width'] : 0),
                'product_length' => (!empty($post['product_length']) ? $post['product_length'] : 0),
                'product_variant_status' => $post['variant_status'] ?? 0,
                'need_recipe_status' => $post['need_recipe_status'] ?? 0
            ];

            DB::beginTransaction();
            $update = Product::where('id_product', $post['id_product'])->update($product);

            if (!$update) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan product']]);
            }
            $idProduct = $post['id_product'];

            $checkBrand = BrandProduct::where('id_product', $idProduct)->first();
            if (empty($checkBrand)) {
                $defaultBrand = BrandOutlet::where('id_outlet', $checkMerchant['id_outlet'])->first()['id_brand'] ?? null;
                if (!empty($defaultBrand)) {
                    $checkBrand = Brand::where('id_brand', $defaultBrand)->first();
                    if (!empty($checkBrand)) {
                        BrandProduct::create(['id_product' => $idProduct, 'id_brand' => $defaultBrand]);
                    }
                }
            }

            $stockProduct = $post['stock'] ?? 0;
            ProductDetail::where('id_product', $idProduct)->where('id_outlet', $outlet['id_outlet'])->update([
                'product_detail_stock_status' => (empty($post['variants']) && empty($stockProduct) ? 'Sold Out' : 'Available'),
                'product_detail_stock_item' => (empty($post['variants']) ? $stockProduct : 0)
            ]);

            $img = [];
            if (!empty($post['image'])) {
                $image = $post['image'];
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
                $upload = MyHelper::uploadPhotoAllSize($encode, 'img/product/' . $checkProduct['product_code'] . '/');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $checkPhoto = ProductPhoto::where('id_product', $post['id_product'])->orderBy('product_photo_order', 'asc')->first();
                    if (!empty($checkPhoto)) {
                        $delete = MyHelper::deletePhoto($checkPhoto['product_photo']);
                        if ($delete) {
                            ProductPhoto::where('id_product_photo', $checkPhoto['id_product_photo'])->update(['product_photo' => $upload['path']]);
                        }
                    } else {
                        $img[] = $upload['path'];
                    }
                }
            }

            foreach ($post['image_detail'] ?? [] as $image) {
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
                $upload = MyHelper::uploadPhotoAllSize($encode, 'img/product/' . $checkProduct['product_code'] . '/');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $img[] = $upload['path'];
                }
            }

            $insertImg = [];
            $j = ProductPhoto::where('id_product', $post['id_product'])->orderBy('product_photo_order', 'desc')->first()['product_photo_order'] ?? 0;
            if ($j == 0 && empty($post['image'])) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Tambahkan minimal 1 gambar produk']]);
            }

            foreach ($img as $img) {
                $insertImg[] = [
                    'id_product' => $idProduct,
                    'product_photo' => $img,
                    'product_photo_order' => $j + 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $j++;
            }

            ProductPhoto::insert($insertImg);

            if (!empty($post['base_price'])) {
                ProductGlobalPrice::where('id_product', $idProduct)->update(['product_global_price' => $post['base_price']]);
            }

            if (!empty($post['variants']) && !empty($post['variant_status'])) {
                $variants = (array)json_decode($post['variants']);

                foreach ($variants['variants_price'] as $combination) {
                    $combination = (array) $combination;
                    $idProductVariantGroup = $combination['id_product_variant_group'];

                    if (!empty($idProductVariantGroup)) {
                        ProductVariantGroupWholesaler::where('id_product_variant_group', $idProductVariantGroup)->delete();
                        if (!empty($combination['wholesaler_price'])) {
                            $insertWholesalerVariant = [];
                            foreach ($combination['wholesaler_price'] as $wholesaler) {
                                $wholesaler = (array)$wholesaler;
                                if ($wholesaler['minimum'] <= 1) {
                                    DB::rollback();
                                    return response()->json(['status' => 'fail', 'messages' => ['Jumlah unit harus lebih dari satu']]);
                                }
                                $insertWholesalerVariant[] = [
                                    'id_product_variant_group' => $idProductVariantGroup,
                                    'variant_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                                    'variant_wholesaler_unit_price' => $wholesaler['unit_price'] ?? 0,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                            }

                            $arrayColumn = array_column($insertWholesalerVariant, 'variant_wholesaler_minimum');
                            $withoutDuplicates = array_unique($arrayColumn);
                            $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
                            if (!empty($duplicates)) {
                                DB::rollback();
                                return response()->json(['status' => 'fail', 'messages' => ['Minimum tidak boleh sama']]);
                            }

                            ProductVariantGroupWholesaler::insert($insertWholesalerVariant);
                        }
                    }
                }
            }

            DB::commit();

            $price = $post['base_price'] ?? 0;
            $priceVariant = ProductVariantGroup::where('id_product', $post['id_product'])->orderBy('product_variant_group_price', 'asc')->first()['product_variant_group_price'] ?? 0;
            if (!empty($priceVariant)) {
                $price = $priceVariant;
            }

            ProductWholesaler::where('id_product', $post['id_product'])->delete();
            if (empty($post['variants']) && !empty($post['wholesaler_price'])) {
                $post['wholesaler_price'] = (array)json_decode($post['wholesaler_price']);
                $insertWholesaler = [];

                foreach ($post['wholesaler_price'] as $wholesaler) {
                    $wholesaler = (array)$wholesaler;
                    if ($wholesaler['minimum'] <= 1) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Jumlah unit harus lebih dari satu']]);
                    }

                    $insertWholesaler[] = [
                        'id_product' => $post['id_product'],
                        'product_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                        'product_wholesaler_unit_price' => $wholesaler['unit_price'] ?? 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }


                $arrayColumn = array_column($insertWholesaler, 'product_wholesaler_minimum');
                $withoutDuplicates = array_unique($arrayColumn);
                $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
                if (!empty($duplicates)) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Minimum tidak boleh sama']]);
                }

                ProductWholesaler::insert($insertWholesaler);
            }
            ProductGlobalPrice::where('id_product', $post['id_product'])->update(['product_global_price' => $price]);
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productDetail($post)
    {
        $idUser = $post['id_user'];

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if (empty($checkMerchant)) {
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $checkMerchant['id_outlet'])->first();
        if (!$outlet) {
            return [
                'status' => 'fail',
                'messages' => ['Outlet not found']
            ];
        }

        if (!empty($post['id_product'])) {
            $detail = Product::leftJoin('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
                ->where('id_product', $post['id_product'])->select('products.*', 'product_category_name')->first();

            if (empty($detail)) {
                return response()->json(['status' => 'fail', 'messages' => ['Data produk tidak ditemukan']]);
            }

            $price = (int)(ProductGlobalPrice::where('id_product', $post['id_product'])->first()['product_global_price'] ?? 0);
            $variantTree = Product::getVariantTree($detail['id_product'], $outlet);

            $image = ProductPhoto::where('id_product', $detail['id_product'])->orderBy('product_photo_order', 'asc')->first();
            if (!empty($image)) {
                $image = [
                    'id_product_photo' => $image['id_product_photo'],
                    'url_product_photo' => (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : '')
                ];
            }

            $imageDetail = ProductPhoto::where('id_product', $detail['id_product'])->orderBy('product_photo_order', 'asc')->whereNotIn('id_product_photo', [$image['id_product_photo'] ?? null])->get()->toArray();
            $imagesDetail = [];
            foreach ($imageDetail as $dt) {
                $imagesDetail[] = [
                    'id_product_photo' => $dt['id_product_photo'],
                    'url_product_photo' => $dt['url_product_photo']
                ];
            }

            $result = [
                'id_product' => $detail['id_product'],
                'product_code' => $detail['product_code'],
                'product_name' => $detail['product_name'],
                'id_product_category' => $detail['id_product_category'],
                'product_category_name' => $detail['product_category_name'],
                'product_description' => $detail['product_description'],
                'base_price' => ($variantTree['base_price'] ?? false) ?: $price,
                'image' => $image,
                'image_detail' => $imagesDetail,
                'product_weight' => $detail['product_weight'],
                'product_height' => $detail['product_height'],
                'product_width' => $detail['product_width'],
                'product_length' => $detail['product_length'],
                'product_variant_status' => $detail['product_variant_status'],
                'need_recipe_status' => $detail['need_recipe_status']
            ];

            $wholesalerStatus = false;
            $variantGroup = ProductVariantGroup::where('id_product', $detail['id_product'])->get()->toArray();
            $variants = [];
            foreach ($variantGroup as $group) {
                $variant = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')->where('id_product_variant_group', $group['id_product_variant_group'])->get()->toArray();
                $variantName = array_column($variant, 'product_variant_name');

                $variantChild = [];
                foreach ($variant as $value) {
                    $childParent = ProductVariant::where('id_product_variant', $value['id_parent'])->first();
                    $childParentName = $childParent['product_variant_name'] ?? '';
                    $variantChild[] = $childParentName . '|' . $value['product_variant_name'];
                    $variantOriginal[$childParentName][] = $value['product_variant_name'];

                    $variants['variants'][] = [
                        'id_product_variant' => $childParent['id_product_variant'],
                        'variant_name' => $childParentName,
                        'variant_child' => ProductVariant::where('id_parent', $childParent['id_product_variant'])->select('id_product_variant', 'product_variant_name as variant_name')->get()->toArray()
                    ];
                }

                if (!empty($variantName)) {
                    $stock = ProductVariantGroupDetail::where('id_product_variant_group', $group['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])->first();
                    $wholesaler = ProductVariantGroupWholesaler::where('id_product_variant_group', $group['id_product_variant_group'])->select('id_product_variant_group_wholesaler', 'variant_wholesaler_minimum as minimum', 'variant_wholesaler_unit_price as unit_price')->get()->toArray();
                    foreach ($wholesaler as $key => $w) {
                        $wholesaler[$key]['unit_price'] = (int)$w['unit_price'];
                        $wholesalerStatus = true;
                    }
                    $variants['variants_price'][] = [
                        "id_product_variant_group" => $group['id_product_variant_group'],
                        "name" => implode(' ', $variantName),
                        "price" => (int)$group['product_variant_group_price'],
                        "visibility" => ($stock['product_variant_group_visibility'] == 'Hidden' ? 0 : 1),
                        "stock" => (int)($stock['product_variant_group_stock_item'] ?? 0),
                        "data" => $variantChild,
                        "wholesaler_price" => $wholesaler
                    ];
                }
            }

            if (!empty($variants['variants'])) {
                $variants['variants'] = array_values(array_map("unserialize", array_unique(array_map("serialize", $variants['variants']))));
                $result['wholesaler_status'] = $wholesalerStatus;
            } else {
                $wholesaler = ProductWholesaler::where('id_product', $detail['id_product'])->select('id_product_wholesaler', 'product_wholesaler_minimum as minimum', 'product_wholesaler_unit_price as unit_price')->get()->toArray();
                foreach ($wholesaler as $key => $w) {
                    $wholesaler[$key]['unit_price'] = (int)$w['unit_price'];
                    $wholesalerStatus = true;
                }
                $result['wholesaler_price'] = $wholesaler;
                $result['wholesaler_status'] = $wholesalerStatus;
                $result['stock'] = ProductDetail::where('id_product', $detail['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])->first()['product_detail_stock_item'] ?? 0;
            }
            $result['variants'] = $variants;
            return response()->json(MyHelper::checkGet($result));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productDelete($post)
    {
        if (!empty($post['id_product'])) {
            $check = TransactionProduct::where('id_product', $post['id_product'])->first();
            if (!empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Tidak bisa menghapus product. Produk sudah masuk ke transaksi.']]);
            }

            $delete = Product::where('id_product', $post['id_product'])->delete();
            if ($delete) {
                ProductDetail::where('id_product', $post['id_product'])->delete();
                ProductGlobalPrice::where('id_product', $post['id_product'])->delete();
                $idProductVariantGroup = ProductVariantGroup::where('id_product', $post['id_product'])->pluck('id_product_variant_group')->toArray();
                ProductVariantGroup::where('id_product', $post['id_product'])->delete();
                ProductVariantGroupDetail::whereIn('id_product_variant_group', $idProductVariantGroup)->delete();
            }
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productPhotoDelete($post)
    {
        if (!empty($post['id_product_photo'])) {
            $data = ProductPhoto::where('id_product_photo', $post['id_product_photo'])->first();
            if (empty($data)) {
                return response()->json(['status' => 'fail', 'messages' => ['Data foto tidak temukan']]);
            }
            $delete = MyHelper::deletePhoto($data['product_photo']);
            if ($delete) {
                $delete = ProductPhoto::where('id_product_photo', $post['id_product_photo'])->delete();
            }

            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function variantDelete($post)
    {
        if (!empty($post['id_product_variant'])) {
            $delete = false;
            $idProductVariantGroup = ProductVariantPivot::join('product_variant_groups', 'product_variant_groups.id_product_variant_group', 'product_variant_pivot.id_product_variant_group')
                ->where('id_product_variant', $post['id_product_variant'])->pluck('product_variant_groups.id_product_variant_group')->toArray();
            $check = TransactionProduct::whereIn('id_product_variant_group', $idProductVariantGroup)->first();

            if ($check <= 0) {
                $delete = ProductVariant::where('id_product_variant', $post['id_product_variant'])->delete();
                if ($delete) {
                    ProductVariantPivot::whereIn('id_product_variant_group', $idProductVariantGroup)->delete();
                    ProductVariantGroup::whereIn('id_product_variant_group', $idProductVariantGroup)->delete();
                }
            }
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productStockDetail($post)
    {
        $idUser = $post['id_user'];

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if (empty($checkMerchant)) {
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if (empty($post['id_product'])) {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }

        $product = Product::where('id_product', $post['id_product'])->first();
        if (empty($product)) {
            return response()->json(['status' => 'fail', 'messages' => ['Product not found']]);
        }

        $result['id_product'] = $product['id_product'];
        $result['product_variant_status'] = $product['product_variant_status'];

        if ($product['product_variant_status']) {
            $variantGroup = ProductVariantGroup::where('id_product', $product['id_product'])->get()->toArray();
            $variants = [];
            foreach ($variantGroup as $group) {
                $variant = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')->where('id_product_variant_group', $group['id_product_variant_group'])->get()->toArray();
                $variantName = array_column($variant, 'product_variant_name');

                if (!empty($variantName)) {
                    $stock = ProductVariantGroupDetail::where('id_product_variant_group', $group['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])->first();
                    $variants[] = [
                        "id_product_variant_group" => $group['id_product_variant_group'],
                        "name" => implode(' ', $variantName),
                        "visibility" => ($stock['product_variant_group_visibility'] == 'Hidden' ? 0 : 1),
                        "stock" => (int)($stock['product_variant_group_stock_item'] ?? 0)
                    ];
                }
            }

            $result['variants'] = $variants;
        } else {
            $productDetail = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])->first();
            $result['name'] = $product['product_name'];
            $result['visibility'] = (($productDetail['product_detail_visibility'] ?? 'Visible') == 'Hidden' ? 0 : 1);
            $result['stock'] = $productDetail['product_detail_stock_item'] ?? 0;
        }

        return response()->json(MyHelper::checkGet($result));
    }

    public function productStockUpdate($post)
    {
        $idUser = $post['id_user'];

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if (empty($checkMerchant)) {
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if (empty($post['id_product'])) {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }

        if (empty($post['variants']) && empty($post['stock'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }

        $product = Product::where('id_product', $post['id_product'])->first();
        if (empty($product)) {
            return response()->json(['status' => 'fail', 'messages' => ['Product not found']]);
        }

        if (!empty($post['variants'])) {
            $sumStock = 0;
            $visibility = 'Hidden';

            DB::beginTransaction();
            foreach ($post['variants'] as $variant) {
                $stock = $variant['stock'] ?? 0;
                $update = ProductVariantGroupDetail::where('id_product_variant_group', $variant['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])
                    ->update([
                        "product_variant_group_visibility" => ($variant['visibility'] == 1 ? 'Visible' : 'Hidden'),
                        'product_variant_group_stock_status' => (empty($stock) ? 'Sold Out' : 'Available'),
                        "product_variant_group_stock_item" => (int)$stock
                        ]);

                if (!$update) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan stock']]);
                }

                if ($variant['visibility'] == 1 && !empty($stock)) {
                    $sumStock = $sumStock + $stock;
                }

                if ($variant['visibility'] == 1) {
                    $visibility = 'Visible';
                }

                $idProduct = ProductVariantGroup::where('id_product_variant_group', $variant['id_product_variant_group'])->first()['id_product'] ?? null;
            }

            DB::commit();
            if (!empty($idProduct)) {
                ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])
                    ->update([
                        'product_detail_visibility' => $visibility,
                        'product_detail_stock_status' => ($sumStock <= 0 ? 'Sold Out' : 'Available'),
                        'product_detail_stock_item' => 0
                    ]);
            }
            return response()->json(['status' => 'success']);
        } else {
            $stock = $post['stock'] ?? 0;
            $checkVariant = ProductVariantGroup::where('id_product', $product['id_product'])->get()->toArray();

            if (count($checkVariant) > 1) {
                return response()->json(['status' => 'fail', 'messages' => ['Request param does not match']]);
            } elseif (count($checkVariant) == 1) {
                $update = ProductVariantGroupDetail::where('id_product_variant_group', $checkVariant[0]['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])
                    ->update([
                        "product_variant_group_visibility" => (($post['visibility'] ?? 0) == 1 ? 'Visible' : 'Hidden'),
                        'product_variant_group_stock_status' => (empty($stock) ? 'Sold Out' : 'Available'),
                        "product_variant_group_stock_item" => (int)$stock
                    ]);

                ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])
                    ->update([
                        'product_detail_visibility' => (($post['visibility'] ?? 0) == 1 ? 'Visible' : 'Hidden'),
                        'product_detail_stock_status' => ($stock <= 0 ? 'Sold Out' : 'Available'),
                        'product_detail_stock_item' => 0
                    ]);
            } else {
                $update = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])
                    ->update([
                        'product_detail_visibility' => (($post['visibility'] ?? 0) == 1 ? 'Visible' : 'Hidden'),
                        'product_detail_stock_status' => (empty($stock) ? 'Sold Out' : 'Available'),
                        'product_detail_stock_item' => (int)$stock
                    ]);
            }


            return response()->json(MyHelper::checkUpdate($update));
        }
    }

    public function variantGroupUpdate(Request $request)
    {
        $post = $request->all();
        $idUser = $request->user()->id;
        if (!empty($post['admin'])) {
            $idUser = Merchant::where('id_merchant', $post['id_merchant'])->first()['id_user'] ?? null;
        }

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if (empty($checkMerchant)) {
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $product = Product::where('id_product', $post['id_product'])->first();
        if (empty($product)) {
            return response()->json(['status' => 'fail', 'messages' => ['Product not found']]);
        }

        $idProduct = $product['id_product'];
        if (!empty($post['variants'])) {
            DB::beginTransaction();

            $dtVariant = [];
            foreach ($post['variants'] as $key => $variant) {
                $variant = (array)$variant;
                if (empty($variant['id_product_variant'])) {
                    $createVariant = ProductVariant::create([
                        'product_variant_name' => $variant['variant_name'],
                        'product_variant_visibility' => 'Visible',
                        'product_variant_order' => $key + 1
                    ]);
                    $dtVariant[$variant['variant_name']]['id'] = $createVariant['id_product_variant'];
                    $idProductVariant = $createVariant['id_product_variant'];
                } else {
                    ProductVariant::where('id_product_variant', $variant['id_product_variant'])->update([
                        'product_variant_name' => $variant['variant_name'],
                        'product_variant_order' => $key + 1
                    ]);
                    $dtVariant[$variant['variant_name']]['id'] = $variant['id_product_variant'];
                    $idProductVariant = $variant['id_product_variant'];
                }

                foreach ($variant['variant_child'] as $index => $child) {
                    $child = (array)$child;
                    if (empty($child['id_product_variant'])) {
                        $insertChild = ProductVariant::create([
                            'id_parent' => $idProductVariant,
                            'product_variant_name' => $child['variant_name'],
                            'product_variant_visibility' => 'Visible',
                            'product_variant_order' => $index + 1
                        ]);

                        $dtVariant[$variant['variant_name']][$child['variant_name']] = $insertChild['id_product_variant'];
                    } else {
                        ProductVariant::where('id_product_variant', $child['id_product_variant'])->update([
                            'id_parent' => $idProductVariant,
                            'product_variant_name' => $child['variant_name'],
                            'product_variant_order' => $index + 1
                        ]);

                        $dtVariant[$variant['variant_name']][$child['variant_name']] = $child['id_product_variant'];
                    }
                }
            }

            foreach ($post['variants_price'] as $combination) {
                $combination = (array) $combination;
                if (empty($combination['id_product_variant_group'])) {
                    $idVariants = [];
                    foreach ($combination['data'] as $dt) {
                        $first = explode('|', $dt)[0] ?? '';
                        $second = explode('|', $dt)[1] ?? '';

                        if (isset($dtVariant[$first][$second])) {
                            $idVariants[] = $dtVariant[$first][$second];
                        }
                    }

                    if (!empty($idVariants)) {
                        $variantGroup = ProductVariantGroup::create([
                            'id_product' => $idProduct,
                            'product_variant_group_code' => 'PV' . time() . '-' . implode('', $idVariants),
                            'product_variant_group_name' => $combination['name'],
                            'product_variant_group_visibility' => (empty($combination['visibility']) ? 'Hidden' : 'Visible'),
                            'product_variant_group_price' => str_replace('.', '', $combination['price'])
                        ]);
                        $idProductVariantGroup = $variantGroup['id_product_variant_group'];

                        $insertPivot = [];
                        foreach ($idVariants as $id) {
                            $insertPivot[] = [
                                'id_product_variant' => $id,
                                'id_product_variant_group' => $variantGroup['id_product_variant_group']
                            ];
                        }

                        if (!empty($insertPivot)) {
                            ProductVariantPivot::insert($insertPivot);
                        }

                        ProductVariantGroupDetail::create([
                            'id_product_variant_group' => $variantGroup['id_product_variant_group'],
                            'id_outlet' => $checkMerchant['id_outlet'],
                            'product_variant_group_visibility' => (empty($combination['visibility']) ? 'Hidden' : 'Visible'),
                            'product_variant_group_stock_status' => (empty($combination['stock']) ? 'Sold Out' : 'Available'),
                            'product_variant_group_stock_item' => $combination['stock']]);
                    }
                } else {
                    ProductVariantGroup::where('id_product_variant_group', $combination['id_product_variant_group'])->update([
                        'product_variant_group_name' => $combination['name'],
                        'product_variant_group_price' => str_replace('.', '', $combination['price']),
                        'product_variant_group_visibility' => (empty($combination['visibility']) ? 'Hidden' : 'Visible')
                    ]);

                    ProductVariantGroupDetail::where('id_product_variant_group', $combination['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])
                        ->update([
                            'product_variant_group_visibility' => (empty($combination['visibility']) ? 'Hidden' : 'Visible'),
                            'product_variant_group_stock_status' => (empty($combination['stock']) ? 'Sold Out' : 'Available'),
                            'product_variant_group_stock_item' => $combination['stock']]);

                    $idProductVariantGroup = $combination['id_product_variant_group'];

                    $idVariants = [];
                    foreach ($combination['data'] as $dt) {
                        $first = explode('|', $dt)[0] ?? '';
                        $second = explode('|', $dt)[1] ?? '';

                        if (isset($dtVariant[$first][$second])) {
                            $idVariants[] = $dtVariant[$first][$second];
                        }
                    }

                    if (!empty($idVariants)) {
                        $insertPivot = [];
                        foreach ($idVariants as $id) {
                            $checkExist = ProductVariantPivot::where([
                                'id_product_variant' => $id,
                                'id_product_variant_group' => $idProductVariantGroup
                            ])->first();
                            if (empty($checkExist)) {
                                $insertPivot[] = [
                                    'id_product_variant' => $id,
                                    'id_product_variant_group' => $idProductVariantGroup
                                ];
                            }
                        }

                        if (!empty($insertPivot)) {
                            ProductVariantPivot::insert($insertPivot);
                        }
                    }
                }

                ProductVariantGroupWholesaler::where('id_product_variant_group', $idProductVariantGroup)->delete();
                if (!empty($combination['wholesaler_price'])) {
                    $insertWholesalerVariant = [];
                    foreach ($combination['wholesaler_price'] as $wholesaler) {
                        $wholesaler = (array)$wholesaler;
                        if ($wholesaler['minimum'] <= 1) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Jumlah unit harus lebih dari satu']]);
                        }
                        $insertWholesalerVariant[] = [
                            'id_product_variant_group' => $idProductVariantGroup,
                            'variant_wholesaler_minimum' => $wholesaler['minimum'] ?? 0,
                            'variant_wholesaler_unit_price' => str_replace('.', '', $wholesaler['unit_price']) ?? 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    $arrayColumn = array_column($insertWholesalerVariant, 'variant_wholesaler_minimum');
                    $withoutDuplicates = array_unique($arrayColumn);
                    $duplicates = array_diff_assoc($arrayColumn, $withoutDuplicates);
                    if (!empty($duplicates)) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Minimum tidak boleh sama']]);
                    }

                    ProductVariantGroupWholesaler::insert($insertWholesalerVariant);
                }

                $priceVariant[] = $combination['price'];
                $idVariantGroupAll[] = $idProductVariantGroup;
            }
            $allIDFromProduct = ProductVariantGroup::where('id_product', $post['id_product'])->pluck('id_product_variant_group')->toArray();
            $diff = array_diff($allIDFromProduct, $idVariantGroupAll);
            if (!empty($diff)) {
                ProductVariantPivot::whereIn('id_product_variant_group', $diff)->delete();
                ProductVariantGroup::whereIn('id_product_variant_group', $diff)->delete();
            }

            $price = min($priceVariant);
            ProductGlobalPrice::where('id_product', $post['id_product'])->update(['product_global_price' => $price]);
            DB::commit();
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Variants can not be empty']]);
        }
    }

    public function withdrawalList(Request $request)
    {
        $post = $request->json()->all();

        $list = MerchantLogBalance::join('merchants', 'merchants.id_merchant', 'merchant_log_balances.id_merchant')
            ->join('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
            ->leftJoin('bank_accounts', 'bank_accounts.id_bank_account', 'merchant_log_balances.merchant_balance_id_reference')
            ->where('merchant_balance_source', 'Withdrawal')
            ->select('merchant_log_balances.*', 'outlets.*', 'merchant_log_balances.created_at as request_at');

        if (
            isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])
        ) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $list->whereDate('merchant_log_balances.created_at', '>=', $start_date)
                ->whereDate('merchant_log_balances.created_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['operator'] == '=' || empty($row['parameter'])) {
                            $list->where($row['subject'], (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                        } else {
                            $list->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                        }
                    }
                }
            } else {
                $list->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['operator'] == '=' || empty($row['parameter'])) {
                                $subquery->orWhere($row['subject'], (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                            } else {
                                $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                });
            }
        }

        $list = $list->orderBy('merchant_log_balances.created_at', 'desc')->paginate(30)->toArray();

        foreach ($list['data'] ?? [] as $key => $dt) {
            $bankAccount =  BankAccount::join('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
                ->where('bank_accounts.id_bank_account', $dt['merchant_balance_id_reference'])
                ->first();

            $fee = MerchantLogBalance::where('merchant_balance_source', 'Withdrawal Fee')->where('merchant_balance_id_reference', $dt['id_merchant_log_balance'])->first()['merchant_balance'] ?? 0;

            $list['data'][$key] = [
                'id_merchant_log_balance' => $dt['id_merchant_log_balance'],
                'date' => $dt['request_at'],
                'nominal' => $dt['merchant_balance'],
                'status' => $dt['merchant_balance_status'],
                'data_bank_account' => $bankAccount,
                'outlet' => $dt['outlet_code'] . '-' . $dt['outlet_name'],
                'fee' => $fee,
                'data_outlet' => [
                    'outlet_code' => $dt['outlet_code'],
                    'outlet_name' => $dt['outlet_name'],
                    'outlet_phone' => $dt['outlet_phone'],
                    'outlet_email' => $dt['outlet_email']
                ]
            ];
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function withdrawalChangeStatus(Request $request)
    {
        $post = $request->json()->all();
        $update = MerchantLogBalance::where('id_merchant_log_balance', $post['id_merchant_log_balance'])->update(['merchant_balance_status' => 'Completed']);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function userListNotRegister(Request $request)
    {
        $list = User::leftJoin('merchants', 'merchants.id_user', 'users.id')
                ->whereNull('merchants.id_merchant')
                ->whereNotNull('users.name')
                ->select('id', 'name', 'phone')->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function adminProductDetail(Request $request)
    {
        $post = $request->all();
        $post['id_user'] = Merchant::where('id_merchant', $post['id_merchant'])->first()['id_user'] ?? null;
        return $this->productDetail($post);
    }

    public function updateGrading(Request $request)
    {
        $post = $request->all();

        if (isset($post['reseller_status'])) {
            $data_update['reseller_status'] = 1;
            $data_update['auto_grading'] = $post['auto_grading'];
        } else {
            $data_update['reseller_status'] = 0;
            $data_update['auto_grading'] = 0;
        }

        DB::beginTransaction();
        $update = Merchant::where('id_merchant', $post['id_merchant'])->update($data_update);
        if (!$update) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Failed to update merchant']]);
        }

        $detail_grading = $this->updateDetailGrading($post['merchant_grading'] ?? null, $data_update['reseller_status'], $post['id_merchant']);
        if (!$detail_grading) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Failed to update merchant']]);
        }

        DB::commit();
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updateDetailGrading($data = null, $status, $id_merchant)
    {

        $grading = MerchantGrading::where('id_merchant', $id_merchant)->get()->toArray();


        if ($grading) {
            $delete = MerchantGrading::where('id_merchant', $id_merchant)->delete();
            if (!$delete) {
                return false;
            }
        }

        if ($status != 0) {
            foreach ($data ?? null as $key => $val) {
                $data_val = [
                    'id_merchant' => $id_merchant,
                    'grading_name' => $val['grading_name'],
                    'min_qty' => $val['min_qty'],
                    'min_nominal' => $val['min_nominal'],
                ];
                $add_grading = MerchantGrading::where('id_merchant', $id_merchant)->create($data_val);
                if (!$add_grading) {
                    return false;
                }
            }
        }
        return true;
    }
}
