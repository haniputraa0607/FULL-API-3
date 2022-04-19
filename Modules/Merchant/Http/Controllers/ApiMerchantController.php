<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\Recruitment\Entities\UserHairStylist;
use DB;

class ApiMerchantController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
    }

    public function registerIntroduction(Request $request)
    {
        $post = $request->json()->all();

        if(empty($post)){
            $setting = Setting::where('key', 'merchant_register_intoduction')->first()['value_text']??null;
            $setting = (array)json_decode($setting);

            $detail = [];
            if(!empty($setting)){
                $detail['image'] = (empty($setting['image']) ? '' : config('url.storage_url_api').$setting['image']);
                $detail['title'] = $setting['title'];
                $detail['description'] = $setting['description'];
                $detail['button_text'] = $setting['button_text'];
            }

            return response()->json(MyHelper::checkGet($detail));
        }else{
            $setting = Setting::where('key', 'merchant_register_intoduction')->first()['value_text']??null;
            $setting = (array)json_decode($setting);
            $image = $setting['image']??'';
            if(!empty($post['image'])){
                $upload = MyHelper::uploadPhotoStrict($post['image'], 'img/', 720, 360, 'merchant_introduction_image');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $image = $upload['path'].'?'.time();
                }else{
                    $image = '';
                }
            }

            $detailSave['image'] = $image;
            $detailSave['title'] = $post['title'];
            $detailSave['description'] = $post['description'];
            $detailSave['button_text'] = $post['button_text'];

            $save = Setting::updateOrCreate(['key' => 'merchant_register_intoduction'], ['value_text' => json_encode($detailSave)]);
            return response()->json(MyHelper::checkUpdate($save));
        }
    }

    public function registerSuccess(Request $request)
    {
        $post = $request->json()->all();

        if(empty($post)){
            $setting = Setting::where('key', 'merchant_register_success')->first()['value_text']??null;
            $setting = (array)json_decode($setting);

            $detail = [];
            if(!empty($setting)){
                $detail['image'] = (empty($setting['image']) ? '' : config('url.storage_url_api').$setting['image']);
                $detail['title'] = $setting['title'];
                $detail['description'] = $setting['description'];
                $detail['button_text'] = $setting['button_text'];
            }

            return response()->json(MyHelper::checkGet($detail));
        }else{
            $setting = Setting::where('key', 'merchant_register_success')->first()['value_text']??null;
            $setting = (array)json_decode($setting);
            $image = $setting['image']??'';
            if(!empty($post['image'])){
                $upload = MyHelper::uploadPhotoStrict($post['image'], 'img/', 500, 500, 'merchant_success_image');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $image = $upload['path'].'?'.time();
                }else{
                    $image = '';
                }
            }

            $detailSave['image'] = $image;
            $detailSave['title'] = $post['title'];
            $detailSave['description'] = $post['description'];
            $detailSave['button_text'] = $post['button_text'];

            $save = Setting::updateOrCreate(['key' => 'merchant_register_success'], ['value_text' => json_encode($detailSave)]);
            return response()->json(MyHelper::checkUpdate($save));
        }
    }

    public function registerSubmitStep1(MerchantCreateStep1 $request){
        $post = $request->json()->all();

        $phone = $request->json('merchant_phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

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

        if(!empty($check)){
            return response()->json(['status' => 'fail', 'messages' => ['Nomor telepon sudah terdaftar']]);
        }

        DB::beginTransaction();

        $create = Merchant::create(["id_user" =>$request->user()->id]);
        if(!$create){
            return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan data merchant']]);
        }

        $lastOutlet = Outlet::orderBy('outlet_code', 'desc')->first()['outlet_code']??'';
        $lastOutlet = substr($lastOutlet, -5);
        $lastOutlet = (int)$lastOutlet;
        $countCode = $lastOutlet+1;
        $dataCreateOutlet = [
            "outlet_code" => 'M'.sprintf("%06d", $countCode),
            "outlet_name" => $post['merchant_name'],
            "outlet_license_number" => $post['merchant_license_number'],
            "outlet_email" => (empty($post['merchant_email']) ? null : $post['merchant_email']),
            "outlet_phone" => $phone,
            "id_city" => $post['id_city'],
            "outlet_address" => $post['merchant_address'],
            "outlet_postal_code" => (empty($post['merchant_postal_code']) ? null : $post['merchant_postal_code'])
        ];

        $createOutlet = Outlet::create($dataCreateOutlet);
        if(!$createOutlet){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan data outlet']]);
        }

        Merchant::where('id_merchant', $create['id_merchant'])->update(['id_outlet' => $createOutlet['id_outlet']]);

        DB::commit();
        return response()->json(MyHelper::checkCreate($create));
    }

    public function registerSubmitStep2(MerchantCreateStep2 $request){
        $post = $request->json()->all();

        $checkData = Merchant::where('id_merchant', $post['id_merchant'])->first();
        if(empty($checkData)){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Toko/Perusahaan tidak ditemukan']
            ]);
        }

        $phone = $request->json('merchant_pic_phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Format nomor telepon tidak valid']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $domain = substr($request->json('merchant_pic_email'), strpos($request->json('merchant_pic_email'), "@") + 1);
        if (!filter_var($request->json('merchant_pic_email'), FILTER_VALIDATE_EMAIL) ||  checkdnsrr($domain, 'MX') === false) {
            $result = [
                'status'    => 'fail',
                'messages'    => ['Alamat email Anda tidak valid']
            ];
            return response()->json($result);
        }

        $dataUpdate = [
            "merchant_pic_name" => $post['merchant_pic_name'],
            "merchant_pic_id_card_number" => $post['merchant_pic_id_card_number'],
            "merchant_pic_email" => $post['merchant_pic_email'],
            "merchant_pic_phone" => $phone,
            "merchant_completed_step" => 1
        ];

        $update = Merchant::where('id_merchant', $checkData['id_merchant'])->update($dataUpdate);
        if($update){
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Register Merchant',
                $request->user()->phone,
                [
                    'merchant_name' => $checkData['merchant_name'],
                    'merchant_phone' => $checkData['merchant_phone'],
                    "merchant_pic_name" => $post['merchant_pic_name'],
                    "merchant_pic_email" => $post['merchant_pic_email'],
                    "merchant_pic_phone" => $phone
                ]
            );
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function registerDetail(Request $request){
        $post = $request->json()->all();

        if(empty($post['id_merchant'])){
            return response()->json([
                'status' => 'fail',
                'messages' => ['ID can not be empty']
            ]);
        }

        $checkData = Merchant::where('id_merchant', $post['id_merchant'])->first();
        if(empty($checkData)){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Toko/Perusahaan tidak ditemukan']
            ]);
        }

        $detail['id_merchant'] = $checkData['id_merchant'];
        $detail['merchant_completed_step'] = $checkData['merchant_completed_step'];
        $detail['merchant_status'] = $checkData['merchant_status'];

        $detail['step_1'] = [
            "merchant_name" => $checkData['merchant_name'],
            "merchant_license_number" => $checkData['merchant_license_number'],
            "merchant_email" => $checkData['merchant_email'],
            "merchant_phone" => $checkData['merchant_phone'],
            "id_province" => $checkData['id_province'],
            "id_city" => $checkData['id_city'],
            "merchant_address" => $checkData['merchant_address'],
            "merchant_postal_code" => $checkData['merchant_postal_code']
        ];

        $detail['step_2'] = null;
        if($checkData['merchant_completed_step'] == 1){
            $detail['step_2'] = [
                "merchant_pic_name" => $checkData['merchant_pic_name'],
                "merchant_pic_id_card_number" => $checkData['merchant_pic_id_card_number'],
                "merchant_pic_email" => $checkData['merchant_pic_email'],
                "merchant_pic_phone" => $checkData['merchant_pic_phone']
            ];
        }
        return response()->json(MyHelper::checkGet($detail));
    }

    public function productList(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $products = Product::where('id_merchant', $checkMerchant['id_merchant'])->select('products.id_product', 'product_name');

        if(!empty($post['search_key'])){
            $products = $products->where('product_name', 'like', '%'.$post['search_key'].'%');
        }

        $products = $products->paginate(10)->toArray();
        foreach ($products['data'] as $key=>$value){
            $stockItem = ProductDetail::where('id_product', $value['id_product'])->where('id_outlet', $checkMerchant['id_outlet'])->first()['product_detail_stock_item']??0;
            $stockItemVariant = ProductVariantGroup::join('product_variant_group_details', 'product_variant_group_details.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                                ->where('id_product', $value['id_product'])
                                ->where('id_outlet', $checkMerchant['id_outlet'])->sum('product_variant_group_stock_item');
            $products['data'][$key]['stock'] = $stockItem+$stockItemVariant;
            $photo = ProductPhoto::where('id_product', $value['id_product'])->orderBy('product_photo_order', 'asc')->first()['product_photo']??'';
            $price = (int)(ProductGlobalPrice::where('id_product', $value['id_product'])->first()['product_global_price']??0);
            $products['data'][$key]['price'] = 'Rp '.number_format($price,0,",",".");
            $products['data'][$key]['image'] = (!empty($photo) ? config('url.storage_url_api').$photo : config('url.storage_url_api').'img/default.jpg');
        }
        return response()->json(MyHelper::checkGet($products));
    }

    public function variantCombination(Request $request){
        $post = $request->json()->all();

        if(count($post) <= 2){
            $arrays = [];
            foreach ($post as $value){
                $newArr = [];
                foreach ($value['variant_child'] as $child){
                    $newArr[] = $value['variant_name'].'|'.$child['variant_name'].(!empty($child['id_product_variant']) ? '|'.$child['id_product_variant']:'');
                }

                $arrays[] = $newArr;
            }
            $combinations = app($this->product_variant_group)->combinations($arrays);

            $res = [];
            foreach ($combinations as $combination){
                $name = [];
                if(!is_array($combination)){
                    $combination = [$combination];
                }

                $idVariant = [];
                foreach ($combination as $data){
                    $explode = explode("|",$data);
                    $name[] = $explode[1];
                    if(isset($explode[2])){
                        $idVariant[] = $explode[2];
                    }
                }

                $idProductVariantGroup = 0;
                if(!empty($idVariant)){
                    $idProductVariantGroup = ProductVariantPivot::whereIn('id_product_variant', $idVariant)->groupBy('id_product_variant_group')
                            ->havingRaw('COUNT(id_product_variant_group) = '.count($idVariant))->first()['id_product_variant_group']??0;
                }

                $res[] = [
                    'id_product_variant_group' => $idProductVariantGroup,
                    'name' => implode(' ', $name),
                    'price' => 0,
                    'stock' => 0,
                    'data' => $combination
                ];
            }

            $result = [
                'variants' => $post,
                'variants_price' => $res
            ];
            return response()->json(MyHelper::checkGet($result));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Maksimal variasi adalah 2']]);
        }
    }

    public function productCreate(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if(count($post['image_detail']??[]) > 3){
            return ['status' => 'fail', 'messages' => ['You can upload maximum 3 image detail file']];
        }

        $product = [
            'id_merchant' => $checkMerchant['id_merchant'],
            'product_code' => 'P'.rand().'-'.$checkMerchant['id_merchant'],
            'product_name' => $post['product_name'],
            'product_description' => $post['product_description'],
            'id_product_category' => (!empty($post['id_product_category']) ? $post['id_product_category'] : null),
            'product_visibility' => 'Visible',
            'product_status' => 'Active'
        ];

        DB::beginTransaction();
        $create = Product::create($product);

        if(!$create){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan product']]);
        }
        $idProduct = $create['id_product'];

        $img = [];
        if(!empty($post['image'])){
            $image = $post['image'];
            $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            $upload = MyHelper::uploadPhotoStrict($encode, 'img/product/'.$idProduct.'/', 300, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $img[] = $upload['path'];
            }
        }

        foreach ($post['image_detail']??[] as $image){
            $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            $upload = MyHelper::uploadPhotoStrict($encode, 'img/product/'.$idProduct.'/', 720, 360);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $img[] = $upload['path'];
            }
        }

        $insertImg = [];
        foreach ($img as $j=>$img){
            $insertImg[] = [
                'id_product' => $idProduct,
                'product_photo' => $img,
                'product_photo_order' => $j+1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        ProductPhoto::insert($insertImg);

        if(!empty($post['base_price'])){
            ProductGlobalPrice::create(['id_product' => $idProduct, 'product_global_price' => $post['base_price']]);
        }

        ProductDetail::create(['id_product' => $idProduct, 'id_outlet' => $checkMerchant['id_outlet'], 'product_detail_visibility' => 'Visible']);

        if(!empty($post['variants'])){
            $variants = (array)json_decode($post['variants']);

            $dtVariant = [];
            foreach ($variants['variants'] as $key=>$variant){
                $variant = (array)$variant;
                $createVariant = ProductVariant::create([
                    'product_variant_name' => $variant['variant_name'],
                    'product_variant_visibility' => 'Visible',
                    'product_variant_order' => $key+1
                ]);
                $dtVariant[$variant['variant_name']]['id'] = $createVariant['id_product_variant'];

                foreach ($variant['variant_child'] as $index=>$child){
                    $child = (array)$child;
                    $child = $child['variant_name'];
                    $insertChild = ProductVariant::create([
                                    'id_parent' => $createVariant['id_product_variant'],
                                    'product_variant_name' => $child,
                                    'product_variant_visibility' => 'Visible',
                                    'product_variant_order' => $index+1
                                ]);

                    $dtVariant[$variant['variant_name']][$child] = $insertChild['id_product_variant'];
                }
            }

            foreach ($variants['variants_price'] as $combination){
                $combination = (array) $combination;
                $idVariants = [];
                foreach ($combination['data'] as $dt){
                    $first = explode('|',$dt)[0]??'';
                    $second = explode('|',$dt)[1]??'';

                    if(isset($dtVariant[$first][$second])){
                        $idVariants[] = $dtVariant[$first][$second];
                    }
                }

                if(!empty($idVariants)){
                    $variantGroup = ProductVariantGroup::create([
                        'id_product' => $idProduct,
                        'product_variant_group_code' => 'PV'.time().'-'.implode('', $idVariants),
                        'product_variant_group_name' => $combination['name'],
                        'product_variant_group_visibility' => 'Visible',
                        'product_variant_group_price' => $combination['price']
                    ]);

                    $insertPivot = [];
                    foreach ($idVariants as $id){
                        $insertPivot[] = [
                            'id_product_variant' => $id,
                            'id_product_variant_group' => $variantGroup['id_product_variant_group']
                        ];
                    }

                    if(!empty($insertPivot)){
                        ProductVariantPivot::insert($insertPivot);
                    }

                    ProductVariantGroupDetail::create([
                        'id_product_variant_group' => $variantGroup['id_product_variant_group'],
                        'id_outlet' => $checkMerchant['id_outlet'],
                        'product_variant_group_visibility' => 'Visible',
                        'product_variant_group_stock_item' => $combination['stock']]);
                }
            }
        }

        DB::commit();
        return response()->json(MyHelper::checkCreate($create));
    }

    public function productUpdate(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;

        if(!empty($post['id_product'])){
            $checkMerchant = Merchant::where('id_user', $idUser)->first();
            if(empty($checkMerchant)){
                return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
            }

            $checkProduct = Product::where('id_product', $post['id_product'])->first();
            if(empty($checkProduct)){
                return response()->json(['status' => 'fail', 'messages' => ['Data product tidak ditemukan']]);
            }

            if(count($post['image_detail']??[]) > 3){
                return ['status' => 'fail', 'messages' => ['You can upload maximum 3 image detail file']];
            }

            $product = [
                'product_name' => $post['product_name'],
                'product_description' => $post['product_description'],
                'id_product_category' => (!empty($post['id_product_category']) ? $post['id_product_category'] : null)
            ];

            DB::beginTransaction();
            $update = Product::where('id_product', $post['id_product'])->update($product);

            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan product']]);
            }
            $idProduct = $post['id_product'];

            $img = [];
            if(!empty($post['image'])){
                $image = $post['image'];
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
                $upload = MyHelper::uploadPhotoStrict($encode, 'img/product/'.$idProduct.'/', 300, 300);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $checkPhoto = ProductPhoto::where('id_product', $post['id_product'])->orderBy('product_photo_order', 'asc')->first();
                    if(!empty($checkPhoto)){
                        $delete = MyHelper::deletePhoto($checkPhoto['product_photo']);
                        if($delete){
                            ProductPhoto::where('id_product_photo', $checkPhoto['id_product_photo'])->update(['product_photo' => $upload['path']]);
                        }
                    }else{
                        $img[] = $upload['path'];
                    }
                }
            }

            foreach ($post['image_detail']??[] as $image){
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
                $upload = MyHelper::uploadPhotoStrict($encode, 'img/product/'.$idProduct.'/', 720, 360);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $img[] = $upload['path'];
                }
            }

            $insertImg = [];
            $j = ProductPhoto::where('id_product', $post['id_product'])->orderBy('product_photo_order', 'desc')->first()['product_photo_order']??0;
            foreach ($img as $img){
                $insertImg[] = [
                    'id_product' => $idProduct,
                    'product_photo' => $img,
                    'product_photo_order' => $j+1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $j++;
            }

            ProductPhoto::insert($insertImg);

            if(!empty($post['base_price'])){
                ProductGlobalPrice::where('id_product', $idProduct)->update( ['product_global_price' => $post['base_price']]);
            }

            if(!empty($post['variants'])){
                $variants = (array)json_decode($post['variants']);

                $dtVariant = [];
                foreach ($variants['variants'] as $key=>$variant){
                    $variant = (array)$variant;
                    if(empty($variant['id_product_variant'])){
                        $createVariant = ProductVariant::create([
                            'product_variant_name' => $variant['variant_name'],
                            'product_variant_visibility' => 'Visible',
                            'product_variant_order' => $key+1
                        ]);
                        $dtVariant[$variant['variant_name']]['id'] = $createVariant['id_product_variant'];
                        $idProductVariant = $createVariant['id_product_variant'];
                    }else{
                        ProductVariant::where('id_product_variant', $variant['id_product_variant'])->update([
                            'product_variant_name' => $variant['variant_name'],
                            'product_variant_order' => $key+1
                        ]);
                        $dtVariant[$variant['variant_name']]['id'] = $variant['id_product_variant'];
                        $idProductVariant = $variant['id_product_variant'];
                    }

                    foreach ($variant['variant_child'] as $index=>$child){
                        $child = (array)$child;
                        if(empty($child['id_product_variant'])){
                            $insertChild = ProductVariant::create([
                                'id_parent' => $idProductVariant,
                                'product_variant_name' => $child['variant_name'],
                                'product_variant_visibility' => 'Visible',
                                'product_variant_order' => $index+1
                            ]);

                            $dtVariant[$variant['variant_name']][$child['variant_name']] = $insertChild['id_product_variant'];
                        }else{
                            ProductVariant::where('id_product_variant', $child['id_product_variant'])->update([
                                'id_parent' => $idProductVariant,
                                'product_variant_name' => $child['variant_name'],
                                'product_variant_order' => $index+1
                            ]);

                            $dtVariant[$variant['variant_name']][$child['variant_name']] = $child['id_product_variant'];
                        }
                    }
                }

                foreach ($variants['variants_price'] as $combination){
                    $combination = (array) $combination;
                    if(empty($combination['id_product_variant_group'])){
                        $idVariants = [];
                        foreach ($combination['data'] as $dt){
                            $first = explode('|',$dt)[0]??'';
                            $second = explode('|',$dt)[1]??'';

                            if(isset($dtVariant[$first][$second])){
                                $idVariants[] = $dtVariant[$first][$second];
                            }
                        }

                        if(!empty($idVariants)){
                            $variantGroup = ProductVariantGroup::create([
                                'id_product' => $idProduct,
                                'product_variant_group_code' => 'PV'.time().'-'.implode('', $idVariants),
                                'product_variant_group_name' => $combination['name'],
                                'product_variant_group_visibility' => 'Visible',
                                'product_variant_group_price' => $combination['price']
                            ]);

                            $insertPivot = [];
                            foreach ($idVariants as $id){
                                $insertPivot[] = [
                                    'id_product_variant' => $id,
                                    'id_product_variant_group' => $variantGroup['id_product_variant_group']
                                ];
                            }

                            if(!empty($insertPivot)){
                                ProductVariantPivot::insert($insertPivot);
                            }

                            ProductVariantGroupDetail::create([
                                'id_product_variant_group' => $variantGroup['id_product_variant_group'],
                                'id_outlet' => $checkMerchant['id_outlet'],
                                'product_variant_group_visibility' => 'Visible',
                                'product_variant_group_stock_item' => $combination['stock']]);
                        }
                    }else{
                        ProductVariantGroup::where('id_product_variant_group', $combination['id_product_variant_group'])->update([
                            'product_variant_group_name' => $combination['name'],
                            'product_variant_group_price' => $combination['price']
                        ]);

                        ProductVariantGroupDetail::where('id_product_variant_group', $combination['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])
                            ->update(['product_variant_group_stock_item' => $combination['stock']]);
                    }
                }
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productDetail(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;

        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $checkMerchant['id_outlet'])->first();
        if (!$outlet) {
            return [
                'status' => 'fail',
                'messages' => ['Outlet not found']
            ];
        }

        if(!empty($post['id_product'])){
            $detail = Product::leftJoin('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
                    ->where('id_product', $post['id_product'])->select('products.*', 'product_category_name')->first();

            if(empty($detail)){
                return response()->json(['status' => 'fail', 'messages' => ['Data produk tidak ditemukan']]);
            }

            $price = (int)(ProductGlobalPrice::where('id_product', $post['id_product'])->first()['product_global_price']??0);
            $variantTree = Product::getVariantTree($detail['id_product'], $outlet);

            $image = ProductPhoto::where('id_product', $detail['id_product'])->orderBy('product_photo_order', 'asc')->first();
            if(!empty($image)){
                $image = [
                    'id_product_photo' => $image['id_product_photo'],
                    'url_product_photo' => (!empty($image['product_photo']) ? config('url.storage_url_api').$image['product_photo'] : '')
                ];
            }

            $imageDetail = ProductPhoto::where('id_product', $detail['id_product'])->orderBy('product_photo_order', 'asc')->whereNotIn('id_product_photo', [$image['id_product_photo']??null])->get()->toArray();
            $imagesDetail = [];
            foreach ($imageDetail as $dt){
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
                'base_price' => ($variantTree['base_price']??false)?:$price,
                'image' => $image,
                'image_detail' => $imagesDetail
            ];

            $variantGroup = ProductVariantGroup::where('id_product', $detail['id_product'])->get()->toArray();
            $variants = [];
            foreach ($variantGroup as $group){
                $variant = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')->where('id_product_variant_group', $group['id_product_variant_group'])->get()->toArray();
                $variantName = array_column($variant, 'product_variant_name');

                $variantDetail = ProductVariantGroup::where('id_product_variant_group', $group['id_product_variant_group'])->first();

                $variantChild = [];
                foreach ($variant as $value){
                    $childParent = ProductVariant::where('id_product_variant', $value['id_parent'])->first();
                    $childParentName = $childParent['product_variant_name']??'';
                    $variantChild[] = $childParentName.'|'.$value['product_variant_name'];
                    $variantOriginal[$childParentName][] = $value['product_variant_name'];

                    $variants['variants'][] = [
                        'id_product_variant' => $childParent['id_product_variant'],
                        'variant_name' => $childParentName,
                        'variant_child' => ProductVariant::where('id_parent', $childParent['id_product_variant'])->select('id_product_variant', 'product_variant_name as variant_name')->get()->toArray()
                    ];
                }

                $variants['variants_price'][] = [
                    "id_product_variant_group" => $group['id_product_variant_group'],
                    "name" => implode(' ', $variantName),
                    "price" => (int)$group['product_variant_group_price'],
                    "stock" => (int)(ProductVariantGroupDetail::where('id_product_variant_group', $group['id_product_variant_group'])->where('id_outlet', $checkMerchant['id_outlet'])->first()['product_variant_group_stock_item']??0),
                    "data" => $variantChild
                ];
            }

            if(!empty($variants['variants'])){
                $variants['variants'] = array_values(array_map("unserialize", array_unique(array_map("serialize", $variants['variants']))));
            }
            $result['variants'] = $variants;
            return response()->json(MyHelper::checkGet($result));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productDelete(Request $request){
        $post = $request->all();
        if(!empty($post['id_product'])){
            $check = TransactionProduct::where('id_product', $post['id_product'])->first();
            if(!empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Tidak bisa menghapus product. Produk sudah masuk ke transaksi.']]);
            }

            $delete = Product::where('id_product', $post['id_product'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function productPhotoDelete(Request $request){
        $post = $request->all();

        if(!empty($post['id_product_photo'])){
            $data = ProductPhoto::where('id_product_photo', $post['id_product_photo'])->first();
            if(empty($data)){
                return response()->json(['status' => 'fail', 'messages' => ['Data foto tidak temukan']]);
            }
            $delete = MyHelper::deletePhoto($data['product_photo']);
            if($delete){
                $delete = ProductPhoto::where('id_product_photo', $post['id_product_photo'])->delete();
            }

            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function variantDelete(Request $request){
        $post = $request->all();

        if(!empty($post['id_product_variant'])){
            $delete = ProductVariant::where('id_product_variant', $post['id_product_variant'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
