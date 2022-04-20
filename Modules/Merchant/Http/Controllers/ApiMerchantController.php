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

        $checkRegister = Merchant::where('id_user', $request->user()->id)->first();
        if(!empty($checkRegister)){
            return response()->json(['status' => 'fail', 'messages' => ['Anda sudah pernah mendaftar']]);
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
}
