<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankAccountOutlet;
use Modules\Disburse\Entities\BankName;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Outlet\Entities\DeliveryOutlet;
use DB;
use App\Http\Models\Transaction;

class ApiMerchantController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
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
        $idUser = $request->user()->id;
        $checkData = Merchant::where('id_user', $idUser)->first();
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

        if(empty($checkData)) {
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
        }else{
            $checkPhone = Outlet::where('outlet_phone', $phone)->whereNotIn('id_outlet', [$checkData['id_outlet']])->first();

            if(!empty($checkPhone)){
                return response()->json(['status' => 'fail', 'messages' => ['Nomor telepon sudah terdaftar']]);
            }

            $dataUpdateOutlet = [
                "outlet_name" => $post['merchant_name'],
                "outlet_license_number" => $post['merchant_license_number'],
                "outlet_email" => (empty($post['merchant_email']) ? null : $post['merchant_email']),
                "outlet_phone" => $phone,
                "id_city" => $post['id_city'],
                "outlet_address" => $post['merchant_address'],
                "outlet_postal_code" => (empty($post['merchant_postal_code']) ? null : $post['merchant_postal_code'])
            ];

            $update = Outlet::where('id_outlet', $checkData['id_outlet'])->update($dataUpdateOutlet);
            if(!$update){
                return response()->json(['status' => 'fail', 'messages' => ['Gagal menyimpan data outlet']]);
            }

            return response()->json(MyHelper::checkUpdate($update));
        }
    }

    public function registerSubmitStep2(MerchantCreateStep2 $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;

        $checkData = Merchant::where('id_user', $idUser)->first();
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
        $idUser = $request->user()->id;
        $checkData = Merchant::where('id_user', $idUser)->first();
        if(empty($checkData)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $detail['id_merchant'] = $checkData['id_merchant'];
        $detail['merchant_completed_step'] = $checkData['merchant_completed_step'];
        $detail['merchant_status'] = $checkData['merchant_status'];

        $outlet = Outlet::leftJoin('cities', 'outlets.id_city', 'cities.id_city')
                ->where('id_outlet', $checkData['id_outlet'])->select('outlets.*', 'cities.id_province')->first();

        $detail['step_1'] = [
            "merchant_name" => $outlet['outlet_name'],
            "merchant_license_number" => $outlet['outlet_license_number'],
            "merchant_email" => $outlet['outlet_email'],
            "merchant_phone" => $outlet['outlet_phone'],
            "id_province" => $outlet['id_province'],
            "id_city" => $outlet['id_city'],
            "merchant_address" => $outlet['outlet_address'],
            "merchant_postal_code" => $outlet['outlet_postal_code']
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

    public function profileDetail(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $detail = Merchant::leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
            ->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
            ->where('id_merchant', $checkMerchant['id_merchant'])
            ->select('merchants.*', 'outlets.*', 'cities.city_name')
            ->first();

        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Detail merchant tidak ditemukan']]);
        }

        $detail = [
            'outlet' => [
                'merchant_name' => $detail['outlet_name'],
                'merchant_description' => $detail['outlet_description'],
                'merchant_license_number' => $detail['outlet_license_number'],
                "merchant_email" => $detail['outlet_email'],
                "merchant_phone" => $detail['outlet_phone'],
                "city_name" => $detail['city_name'],
                "image_cover" => (!empty($detail['outlet_image_cover']) ? config('url.storage_url_api').$detail['outlet_image_cover']: ''),
                "image_logo_portrait" => (!empty($detail['outlet_image_logo_portrait']) ? config('url.storage_url_api').$detail['outlet_image_logo_portrait']: ''),
                "image_logo_landscape" => (!empty($detail['outlet_image_logo_landscape']) ? config('url.storage_url_api').$detail['outlet_image_logo_landscape']: '')
            ],
            'pic' => [
                'merchant_pic_name' => $detail['merchant_pic_name'],
                'merchant_pic_id_card_number' => $detail['merchant_pic_id_card_number'],
                'merchant_pic_email' => $detail['merchant_pic_email'],
                'merchant_pic_phone' => $detail['merchant_pic_phone']
            ]
        ];

        return response()->json(MyHelper::checkGet($detail));
    }

    public function profileOutletUpdate(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if(empty($post['merchant_license_number'])){
            $checkLicense = Outlet::where('outlet_license_number', $post['merchant_license_number'])->whereNotIn('id_outlet', [$checkMerchant['id_outlet']])->first();
            if(!empty($checkLicense)){
                return response()->json(['status' => 'fail', 'messages' => ['License number already use with outlet : '.$checkLicense['outlet_name']]]);
            }
        }

        $dataUpdate = [];
        if(!empty($post['image_cover'])){
            $image = $post['image_cover'];
            $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            $upload = MyHelper::uploadPhotoStrict($encode, 'img/outlet/'.$checkMerchant['id_outlet'].'/', 720, 360);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $dataUpdate['outlet_image_cover'] = $upload['path'];
            }
        }

        if(!empty($post['image_logo_portrait'])){
            $image = $post['image_logo_portrait'];
            $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            $upload = MyHelper::uploadPhotoStrict($encode, 'img/outlet/'.$checkMerchant['id_outlet'].'/', 300, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $dataUpdate['outlet_image_logo_portrait'] = $upload['path'];
            }
        }

        if(!empty($post['image_logo_landscape'])){
            $image = $post['image_logo_landscape'];
            $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
            $imgGet = Image::make($image);
            $imgwidth = $imgGet->width();
            $upload = MyHelper::uploadPhotoStrict($encode, 'img/outlet/'.$checkMerchant['id_outlet'].'/', $imgwidth, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $dataUpdate['outlet_image_logo_landscape'] = $upload['path'];
            }
        }

        $dataUpdate['outlet_name'] = $post['merchant_name'];
        $dataUpdate['outlet_description'] = $post['merchant_description'];
        $dataUpdate['outlet_license_number'] = $post['merchant_license_number']??null;
        $dataUpdate['outlet_email'] = $post['merchant_email'];
        $dataUpdate['outlet_phone'] = $post['merchant_phone'];

        $update = Outlet::where('id_outlet', $checkMerchant['id_outlet'])->update($dataUpdate);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function profilePICUpdate(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $dataUpdate['merchant_pic_name'] = $post['merchant_pic_name'];
        $dataUpdate['merchant_pic_id_card_number'] = $post['merchant_pic_id_card_number'];
        $dataUpdate['merchant_pic_email'] = $post['merchant_pic_email'];
        $dataUpdate['merchant_pic_phone'] = $post['merchant_pic_phone'];

        $update = Merchant::where('id_merchant', $checkMerchant['id_merchant'])->update($dataUpdate);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function addressDetail(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();

        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if(empty($post)){
            $data = Merchant::leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
                ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
                ->where('id_merchant', $checkMerchant['id_merchant'])
                ->select('merchants.*', 'provinces.id_province', 'outlets.*')
                ->first();
            if(empty($data)){
                return response()->json(['status' => 'fail', 'messages' => ['Data tidak ditemukan']]);
            }

            $detail = [
                'latitude' => $data['outlet_latitude'],
                'longitude' => $data['outlet_longitude'],
                'id_province' => $data['id_province'],
                'id_city' => $data['id_city'],
                'address' => $data['outlet_address'],
                'postal_code' => $data['outlet_postal_code']
            ];

            return response()->json(MyHelper::checkGet($detail));
        }else{
            $dataUpdate = [
                'outlet_latitude' => $post['latitude']??null,
                'outlet_longitude' => $post['longitude']??null,
                'id_city' => $post['id_city']??null,
                'outlet_address' => $post['address']??null,
                'outlet_postal_code' => $post['postal_code']??null
            ];

            $update = Outlet::where('id_outlet', $checkMerchant['id_outlet'])->update($dataUpdate);
            return response()->json(MyHelper::checkUpdate($update));
        }
    }

    public function bankList(){
        $list = BankName::select('id_bank_name', 'bank_code', 'bank_name', DB::raw("'' as bank_image"))->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function bankAccountCheck(Request $request){
        $post = $request->all();

        if(empty($post['beneficiary_account'])){
            return response()->json(['status' => 'fail', 'messages' => ['Account number can not be empty']]);
        }

        if(empty($post['id_bank_name'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }

        $arr = [0,1];
        shuffle($arr);

        if($arr[0]){
            return response()->json(['status' => 'success', 'result' => [
                'beneficiary_name' => 'Nama',
                'beneficiary_account' => '010101010101'
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Akun tidak ditemukan']]);
        }
    }

    public function bankAccountCreate(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if(empty($post['beneficiary_account'])){
            return response()->json(['status' => 'fail', 'messages' => ['Account number can not be empty']]);
        }

        if(empty($post['id_bank_name'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }

        $check = BankAccount::where('beneficiary_account', $post['beneficiary_account'])->first();
        if(empty($check)){
            $save = BankAccount::create([
                'id_bank_name' => $post['id_bank_name'],
                'beneficiary_name' => 'Beneficiary Name',
                'beneficiary_account' => $post['beneficiary_account']
                ]);
        }

        $save = BankAccountOutlet::updateOrCreate([
            'id_bank_account' => $check['id_bank_account'],
            'id_outlet' => $checkMerchant['id_outlet']
        ], [
            'created_at' =>date('Y-m-d H:i:s'),
            'updated_at' =>date('Y-m-d H:i:s')
        ]);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function bankAccountList(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $list = BankAccount::join('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
                ->join('bank_account_outlets', 'bank_account_outlets.id_bank_account', 'bank_accounts.id_bank_account')
                ->select('bank_account_outlets.id_bank_account', 'beneficiary_name', 'beneficiary_account', DB::raw("'' as bank_image"), 'bank_name.bank_name')
                ->where('id_outlet', $checkMerchant['id_outlet'])
                ->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function bankAccountDelete(Request $request){
        $post = $request->all();

        if(empty($post['id_bank_account'])){
            return response()->json([
                'status' => 'fail',
                'messages' => ['ID can not be empty']
            ]);
        }

        $delete = BankAccount::where('id_bank_account', $post['id_bank_account'])->delete();
        if($delete){
            BankAccountOutlet::where('id_bank_account', $post['id_bank_account'])->delete();
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    public function deliverySetting(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $post = New Request();
        $availableDelivery = app($this->online_trx)->listAvailableDelivery($post);
        if(isset($availableDelivery['status']) && $availableDelivery['status'] == 'fail'){
            return response()->json(['status' => 'fail', 'messages' => ['List delivery not found']]);
        }

        $deliveryOutlet = DeliveryOutlet::where('id_outlet', $checkMerchant['id_outlet'])->where('show_status', 1)
                        ->select('code', 'available_status', 'available_status')->get()->toArray();

        $delivery = $availableDelivery['result']['delivery']??[];
        $res = [];
        foreach ($delivery as $key => $val){
            $check = array_search($val['code'], array_column($deliveryOutlet,'code'));
            $available = 0;
            if($check === false){
                $available = 1;
            }else if($val['show_status'] == 1){
                $available = 1;
            }

            if($available == 1){
                $res[] = [
                    "code" => $val['code'],
                    "delivery_name" => $val['delivery_name'],
                    "delivery_method" =>$val['delivery_method'],
                    "logo" => $val['logo'],
                    "active_status" => $deliveryOutlet[$check]['available_status']??1
                ];
            }
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function deliverySettingUpdate(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if(empty($post['code'])){
            return response()->json(['status' => 'fail', 'messages' => ['Code can not be empty']]);
        }

        if(!isset($post['active_status'])){
            return response()->json(['status' => 'fail', 'messages' => ['Status can not be empty']]);
        }

        $save = DeliveryOutlet::updateOrCreate([
            'code' => $post['code'],
            'id_outlet' => $checkMerchant['id_outlet']
        ], [
            'available_status' => $post['active_status'],
            'created_at' =>date('Y-m-d H:i:s'),
            'updated_at' =>date('Y-m-d H:i:s')
        ]);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function shareMessage(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $message = Setting::where('key', 'merchant_share_message')->first()['value_text']??'';
        $url = str_replace('id', $checkMerchant['id_outlet'], env('URL_SHARE'));

        $result = [
            'message' => $message,
            'url' => $url
        ];
        return response()->json(MyHelper::checkGet($result));
    }

    public function helpPage(){
        $helpPage = Setting::where('key', 'merchant_help_page')->first()['value']??'';
        return response()->json(MyHelper::checkGet(['url' => env('STORAGE_URL_API').'/api/custom-page/webview/'.$helpPage]));
    }

    public function summaryOrder(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $newOrder = Transaction::where('id_outlet', $checkMerchant['id_outlet'])->where('transaction_status', 'Pending')->count();
        $onProgress = Transaction::where('id_outlet', $checkMerchant['id_outlet'])->where('transaction_status', 'On Progress')->count();
        $onDelivery = Transaction::where('id_outlet', $checkMerchant['id_outlet'])->where('transaction_status', 'On Delivery')->count();
        $completed = Transaction::where('id_outlet', $checkMerchant['id_outlet'])->where('transaction_status', 'Completed')->count();

        $result = [
            'new_order' => $newOrder,
            'on_progress' => $onProgress,
            'onDelivery' => $onDelivery,
            'completed' => $completed
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function statisticsOrder(Request $request){
        $post = $request->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        if(empty($post['type'])){
            return response()->json(['status' => 'fail', 'messages' => ['Type can not be empty']]);
        }

        $result = [];
        if($post['type'] == 'weekly'){
            $result = $this->statisticsWeekly($checkMerchant['id_outlet']);
        }elseif($post['type'] == 'monthly'){
            $result = $this->statisticsMonthly($checkMerchant['id_outlet']);
        }elseif($post['type'] == 'yearly'){
            $result = $this->statisticsYearly($checkMerchant['id_outlet']);
        }

        return response()->json(MyHelper::checkGet($result));
    }

    public function statisticsWeekly($id_outlet){
        $currentDate = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-6 day', strtotime($currentDate)));
        $end = $currentDate;

        $transactions = Transaction::where('id_outlet', $id_outlet)->where('transaction_status', 'Completed')
                    ->whereDate('transaction_date', '>=', $start)->whereDate('transaction_date', '<=', $end)
                    ->select('transaction_date', 'transaction_grandtotal')->get()->toArray();

        $resultDate = [];
        foreach ($transactions as $trx){
            $date = date('Y-m-d', strtotime($trx['transaction_date']));
            if(!empty($resultDate[$date])){
                $resultDate[$date] = $resultDate[$date] + 1;
            }else{
                $resultDate[$date] = 1;
            }
        }

        $result = [];
        for($i=0;$i<=6;$i++){
            $date = date('Y-m-d', strtotime('-'.$i.' day', strtotime($currentDate)));
            $result[] = [
                'key' => MyHelper::dateFormatInd($date, false, false),
                'value' => $resultDate[$date]??0
            ];
        }

        return $result;
    }

    public function statisticsMonthly($id_outlet){
        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $date = date("Y-m-01", strtotime( date( 'Y-m-01' )." -$i months"));
            $monthFormat = MyHelper::dateFormatInd($date, false, false);
            $monthFormat = str_replace('01', '', $monthFormat);

            $month = date('m', strtotime($date));
            $year = date('Y', strtotime($date));
            $value = MonthlyReportTrx::where('id_outlet', $id_outlet)
                    ->where('trx_month', $month)
                    ->where('trx_year', $year)->first()['trx_count']??0;
            $result[] = [
                'key' => $monthFormat,
                'value' => (int)$value
            ];
        }

        return $result;
    }

    public function statisticsYearly($id_outlet){
        $currentYear = date('Y');
        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $year = $currentYear-$i;
            $value = MonthlyReportTrx::where('id_outlet', $id_outlet)
                    ->where('trx_year', $year)->sum('trx_count');
            $result[] = [
                'key' => $year,
                'value' => (int)$value
            ];
        }

        return $result;
    }
}
