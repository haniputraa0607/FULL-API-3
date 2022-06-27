<?php

namespace Modules\Outlet\Http\Controllers;

use App\Jobs\SyncronPlasticTypeOutlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\UserOutletApp;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;
use App\Http\Models\OutletPhoto;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Configs;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Merchant\Entities\Merchant;
use Modules\Outlet\Entities\DeliveryOutlet;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Franchise\Entities\UserFranchise;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Outlet\Entities\OutletScheduleUpdate;

use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\Outlet\Upload;
use Modules\Outlet\Http\Requests\Outlet\Update;
use Modules\Outlet\Http\Requests\Outlet\UpdateStatus;
use Modules\Outlet\Http\Requests\Outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\Outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\Outlet\Create;
use Modules\Outlet\Http\Requests\Outlet\Delete;
use Modules\Outlet\Http\Requests\Outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\Outlet\Nearme;
use Modules\Outlet\Http\Requests\Outlet\Filter;
use Modules\Outlet\Http\Requests\Outlet\OutletList;
use Modules\Outlet\Http\Requests\Outlet\OutletListOrderNow;

use Modules\Outlet\Http\Requests\UserOutlet\Create as CreateUserOutlet;
use Modules\Outlet\Http\Requests\UserOutlet\Update as UpdateUserOutlet;

use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Http\Models\Transaction;

use App\Jobs\SendOutletJob;
use Image;

class ApiOutletController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo       			= "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->autocrm              = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->outlet_group_filter = "Modules\Outlet\Http\Controllers\ApiOutletGroupFilterController";
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
    }

    function checkInputOutlet($post=[]) {
        $data = [];

        if (isset($post['outlet_code'])) {
            $data['outlet_code'] = strtoupper($post['outlet_code']);
        }
        if (isset($post['outlet_name'])) {
            $data['outlet_name'] = $post['outlet_name'];
        }
        if (isset($post['outlet_address'])) {
            $data['outlet_address'] = $post['outlet_address'];
        }
        if (isset($post['id_city'])) {
            $data['id_city'] = $post['id_city'];
        }
        if (isset($post['outlet_postal_code'])) {
            $data['outlet_postal_code'] = $post['outlet_postal_code'];
        }
        if (isset($post['outlet_phone'])) {
            $data['outlet_phone'] = $post['outlet_phone'];
        }
        if (isset($post['outlet_fax'])) {
            $data['outlet_fax'] = $post['outlet_fax'];
        }
        if (isset($post['outlet_email'])) {
            $data['outlet_email'] = $post['outlet_email'];
        }
        if (isset($post['outlet_latitude'])) {
            $data['outlet_latitude'] = $post['outlet_latitude'];
        }
        if (isset($post['outlet_longitude'])) {
            $data['outlet_longitude'] = $post['outlet_longitude'];
        }
        if (isset($post['outlet_open_hours'])) {
            $data['outlet_open_hours'] =  date('Y-m-d H:i:s', strtotime($post['outlet_open_hours']));
        }
        if (isset($post['outlet_close_hours'])) {
            $data['outlet_close_hours'] = date('Y-m-d H:i:s', strtotime( $post['outlet_close_hours']));
        }
        if (isset($post['outlet_pin'])) {
            $data['outlet_pin'] = bcrypt($post['outlet_pin']);
        }
        if (isset($post['outlet_status'])) {
            $data['outlet_status'] = $post['outlet_status'];
        }
        if (isset($post['outlet_brands'])) {
            $data['outlet_brands'] = $post['outlet_brands'];
        }
        if (isset($post['deep_link_gojek'])) {
            $data['deep_link_gojek'] = $post['deep_link_gojek'];
        }
        if (isset($post['deep_link_grab'])) {
            $data['deep_link_grab'] = $post['deep_link_grab'];
        }
        if (isset($post['delivery_order'])) {
            $data['delivery_order'] = $post['delivery_order'];
        }else{
            $data['delivery_order'] = 0;
        }

        if (isset($post['status_franchise'])) {
            $data['status_franchise'] = $post['status_franchise'];
        }else{
            $data['status_franchise'] = 0;
        }

        if (isset($post['plastic_used_status'])) {
            $data['plastic_used_status'] = $post['plastic_used_status'];
        }else{
            $data['plastic_used_status'] = 'Inactive';
        }

        if (isset($post['time_zone_utc'])) {
            $data['time_zone_utc'] = $post['time_zone_utc'];
        }

        if (isset($post['delivery_outlet'])) {
            $data['delivery_outlet'] = $post['delivery_outlet'];
        }

        if (isset($post['outlet_license_number'])) {
            $data['outlet_license_number'] = $post['outlet_license_number'];
        }

        if (isset($post['outlet_postal_code'])) {
            $data['outlet_postal_code'] = $post['outlet_postal_code'];
        }

        return $data;
    }

    /* Pengecekan code unique */
    function cekUnique($id, $code) {
        $cek = Outlet::where('outlet_code', strtoupper($code))->first();

        if (empty($cek)) {
            return true;
        }
        else {
            if ($cek->id_product == $id) {
                return true;
            }
            else {
                return false;
            }
        }
    }


    /**
     * create
     */
    function create(Create $request) {
        $post = $this->checkInputOutlet($request->json()->all());
        if (!empty($post['outlet_latitude']) && strpos($post['outlet_latitude'], ',') !== false) {
            return response()->json(['status' => 'fail', 'messages' => ['Please input invalid latitude']]);
        }

        if (!empty($post['outlet_longitude']) && strpos($post['outlet_longitude'], ',') !== false) {
            return response()->json(['status' => 'fail', 'messages' => ['Please input invalid longitude']]);
        }

        if(!isset($post['outlet_code'])){
            do{
                $post['outlet_code'] = MyHelper::createRandomPIN(3);
                $code = Outlet::where('outlet_code', strtoupper($post['outlet_code']))->first();
            }while($code != null);
        }

        if(!isset($post['outlet_pin'])){
	        $request->outlet_pin = MyHelper::createRandomPIN(6, 'angka');
	        $post['outlet_pin'] = bcrypt($request->outlet_pin);
        }

        DB::beginTransaction();
        $save = Outlet::create($post);
        if (!$save) {
            DB::rollBack();
        }

        $data_pin = [
            ['id_outlet' => $save['id_outlet'], 'data' => $request->outlet_pin]
        ];
        MyHelper::updateOutletFile($data_pin);

        if(is_array($brands=$post['outlet_brands']??false)){
            if(in_array('*', $post['outlet_brands'])){
                $brands=Brand::select('id_brand')->get()->toArray();
                $brands=array_column($brands, 'id_brand');
            }
            foreach ($brands as $id_brand) {
                BrandOutlet::create([
                    'id_outlet'=>$save['id_outlet'],
                    'id_brand'=>$id_brand
                ]);
            }
        }

        if(!empty($post['delivery_outlet'])){
            $deliveryOutlet = [];
            foreach ($post['delivery_outlet'] as $key=>$val){
                $deliveryOutlet[] = [
                    'id_outlet' => $save['id_outlet'],
                    'code' => $key,
                    'available_status' => $val['available_status'],
                    'show_status' => $val['show_status'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            DeliveryOutlet::insert($deliveryOutlet);
        }
        //schedule
        if($request->json('day') && $request->json('open') && $request->json('close')){
            $days = $request->json('day');
            $opens = $request->json('open');
            $closes = $request->json('close');
            $is_closed = $request->json('is_closed');
            if(!isset($post['time_zone_utc'])){
                $post['time_zone_utc'] = '7';
            }
            foreach($days as $key => $value){
            	$opens[$key] = $this->setOneTimezone($opens[$key], $post['time_zone_utc']);
            	$closes[$key] = $this->setOneTimezone($closes[$key], $post['time_zone_utc']);
                $data['open'] = $opens[$key];
                $data['close'] = $closes[$key];
                $data['is_closed'] = $is_closed[$key];

                $saveSchedule = OutletSchedule::updateOrCreate(['id_outlet' => $save['id_outlet'], 'day' => $value], $data);
                if (!$saveSchedule) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail']);
                }
            }
        }

        DB::commit();
        SyncronPlasticTypeOutlet::dispatch([])->onQueue('high')->allOnConnection('database');
        // sent pin to outlet
        if (isset($request->outlet_email)) {
        	$variable = $save->toArray();
	        $send 	= app($this->autocrm)->SendAutoCRM('Outlet Pin Sent', $request->outlet_email, [
		                'pin' 			=> $request->outlet_pin,
		                'date_sent' 	=> date('Y-m-d H:i:s'),
		                'outlet_name' 	=> $request->outlet_name,
		                'outlet_code' 	=> $post['outlet_code'],
		            ]+$variable, null, false, false, 'outlet');
        }

        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update
     */
    function update(Update $request) {
        $post = $this->checkInputOutlet($request->json()->all());

        if (!empty($post['outlet_latitude']) && strpos($post['outlet_latitude'], ',') !== false) {
            return response()->json(['status' => 'fail', 'messages' => ['Please input invalid latitude']]);
        }

        if (!empty($post['outlet_longitude']) && strpos($post['outlet_longitude'], ',') !== false) {
            return response()->json(['status' => 'fail', 'messages' => ['Please input invalid longitude']]);
        }

        DB::beginTransaction();
        if(is_array($brands=$post['outlet_brands']??false)){
            if(in_array('*', $post['outlet_brands'])){
                $brands=Brand::select('id_brand')->get()->toArray();
                $brands=array_column($brands, 'id_brand');
            }
            BrandOutlet::where('id_outlet',$request->json('id_outlet'))->delete();
            foreach ($brands as $id_brand) {
                BrandOutlet::create([
                    'id_outlet'=>$request->json('id_outlet'),
                    'id_brand'=>$id_brand
                ]);
            }
        }

        if(!empty($post['delivery_outlet'])){
            $deliveryOutlet = [];
            foreach ($post['delivery_outlet'] as $key=>$val){
                $deliveryOutlet[] = [
                    'id_outlet' => $request->json('id_outlet'),
                    'code' => $key,
                    'available_status' => $val['available_status'],
                    'show_status' => $val['show_status'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            DeliveryOutlet::where('id_outlet',$request->json('id_outlet'))->delete();
            DeliveryOutlet::insert($deliveryOutlet);
        }

        unset($post['outlet_brands']);
        unset($post['delivery_outlet']);
        $checkLicense = Outlet::where('outlet_license_number', $post['outlet_license_number'])->whereNotIn('id_outlet', [$request->json('id_outlet')])->first();
        if(!empty($checkLicense)){
            return response()->json(['status' => 'fail', 'messages' => ['License number already use with outlet : '.$checkLicense['outlet_name']]]);
        }

        if(!empty($request->json('outlet_image_logo_portrait'))){
            $upload = MyHelper::uploadPhotoStrict($request->json('outlet_image_logo_portrait'), 'img/outlet/'.$request->json('id_outlet').'/', 300, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['outlet_image_logo_portrait'] = $upload['path'];
            }
        }

        if(!empty($request->json('outlet_image_logo_landscape'))){
            $decoded = base64_decode($request->json('outlet_image_logo_landscape'));
            $img = Image::make($decoded);
            $imgwidth = $img->width();
            $imgheight = $img->height();
            $uploadDetail = MyHelper::uploadPhotoStrict($request->json('outlet_image_logo_landscape'), 'img/outlet/'.$request->json('id_outlet').'/', $imgwidth, $imgheight);

            if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                $post['outlet_image_logo_landscape'] = $uploadDetail['path'];
            }
        }

        if(!empty($request->json('outlet_image_cover'))){
            $uploadCover = MyHelper::uploadPhotoStrict($request->json('outlet_image_cover'), 'img/outlet/'.$request->json('id_outlet').'/', 720, 360);

            if (isset($uploadCover['status']) && $uploadCover['status'] == "success") {
                $post['outlet_image_cover'] = $uploadCover['path'];
            }
        }

        $save = Outlet::where('id_outlet', $request->json('id_outlet'))->update($post);

        if($save){
            DB::commit();
            SyncronPlasticTypeOutlet::dispatch([])->onQueue('high')->allOnConnection('database');
        }else{
            DB::rollBack();
        }
        return response()->json(MyHelper::checkUpdate($save));
    }

    function updateStatus(UpdateStatus $request) {
        $post = $request->json()->all();
        $save = Outlet::where('id_outlet', $request->json('id_outlet'))->update(['outlet_status' => $post['outlet_status']??'Inactive']);
        // return Outlet::where('id_outlet', $request->json('id_outlet'))->first();

        return response()->json(MyHelper::checkUpdate($save));
    }

    /**
     * delete
     */
    function delete(Request $request) {

        $check = $this->checkDeleteOutlet($request->json('id_outlet'));

        if ($check) {
            // delete holiday
            $deleteHoliday = $this->deleteHolidayOutlet($request->json('id_outlet'));
            // delete photo
            $deletePhoto = $this->deleteFotoStore($request->json('id_outlet'));

            $delete = Outlet::where('id_outlet', $request->json('id_outlet'))->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }
        else {
            return response()->json([
                    'status' => 'fail',
                    'messages' => ['outlet has been used.']
                ]);
        }
    }

    /**
     * delete foto by store
     */
    function deleteFotoStore($id) {
        // info photo
        $dataPhoto = OutletPhoto::where('id_outlet')->get()->toArray();

        if (!empty($dataPhoto)) {
            foreach ($dataPhoto as $key => $value) {
                MyHelper::deletePhoto($value['outlet_photo']);
            }
        }

        $delete = OutletPhoto::where('id_outlet', $id)->delete();

        return $delete;
    }

    function deleteHolidayOutlet($id) {
        $delete = OutletHoliday::where('id_outlet', $id)->delete();
        $deleteholiday = Holiday::whereDoesntHave('outlets')->delete();
        return $deleteholiday;
    }

    /**
     * cek delete outlet
     */
    function checkDeleteOutlet($id) {

        $table = [
            'deals_outlets',
            'enquiries',
            'product_prices',
            'user_outlets'
        ];

        for ($i=0; $i < count($table); $i++) {

            $check = DB::table($table[$i])->where('id_outlet', $id)->count();

            if ($check > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * function upload
     */
    function upload(UploadPhoto $request) {
        $post = $request->json()->all();

        $data = [];

        if (isset($post['photo'])) {

            $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 600, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['outlet_photo'] = $upload['path'];
            }
            else {
                $result = [
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return response()->json($result);
            }
        }

        if (empty($data)) {
            return reponse()->json([
                'status'   => 'fail',
                'messages' => ['fail save to database']
            ]);
        }
        else {
            $data['id_outlet']          = $post['id_outlet'];
            $data['outlet_photo_order'] = $this->cekLastUrutan($post['id_outlet']);
            $save                       = OutletPhoto::create($data);

            return response()->json(MyHelper::checkCreate($save));
        }
    }

    /*
    cari urutan
    */
    function cekLastUrutan($id) {
        $last = OutletPhoto::where('id_outlet', $id)->orderBy('outlet_photo_order', 'DESC')->first();

        if (!empty($last)) {
            $last = $last->outlet_photo_order + 1;
        }
        else {
            $last = 1;
        }

        return $last;
    }

    /**
     * delete upload
     */
    function deleteUpload(DeletePhoto $request) {
        // info
        $dataPhoto = OutletPhoto::where('id_outlet_photo')->get()->toArray();

        if (!empty($dataPhoto)) {
            MyHelper::deletePhoto($dataPhoto[0]['outlet_photo']);
        }

        $delete = OutletPhoto::where('id_outlet_photo', $request->json('id_outlet_photo'))->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }

    /**
    * update foto product
    */
    function updatePhoto(Request $request) {
        $update =   OutletPhoto::where('id_outlet_photo', $request->json('id_outlet_photo'))->update([
            'outlet_photo_order' => $request->json('outlet_photo_order')
        ]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
    * update pin outlet
    */
    function updatePin(Request $request) {
        $post = $request->json()->all();
        $outlet = Outlet::find($post['id_outlet']);

        if(!$outlet){
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Data outlet not found.'
                ]
            ]);
        }

        if ($request->generate_pin_outlet) {
        	$post['outlet_pin'] = MyHelper::createRandomPIN(6, 'angka');
        }

        $pin = bcrypt($post['outlet_pin']);
        $outlet->outlet_pin = $pin;
        $outlet->save();

        $data_pin = [
            ['id_outlet' => $post['id_outlet'], 'data' => $post['outlet_pin']]
        ];
        MyHelper::updateOutletFile($data_pin);
        //delete token
        $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                                    ->where('oauth_access_tokens.user_id', $post['id_outlet'])->where('oauth_access_token_providers.provider', 'outlet-app')->delete();

        // sent pin to outlet
        if (isset($outlet->outlet_email)) {
        	$variable = $outlet->toArray();
	        $send 	= app($this->autocrm)->SendAutoCRM('Outlet Pin Sent', $outlet->outlet_email, [
		                'pin' 			=> $post['outlet_pin'],
		                'date_sent' 	=> date('Y-m-d H:i:s'),
		            ]+$variable, null, false, false, 'outlet');
        }
        
        return response()->json(MyHelper::checkUpdate($outlet));
    }

    function listOutletProductDetail(OutletList $request) {
        $post = $request->json()->all();

        $outlet = Outlet::with(['user_outlets','city','today', 'outlet_schedules'])->select('*');
        if(isset($post['id_product'])){
            $outlet = $outlet->with(['product_detail'=> function($q) use ($post){
                $q->where('id_product', $post['id_product']);
            }]);
        }else{
            $outlet = $outlet->with(['product_detail']);
        }

        $outlet = $outlet->paginate(20);
        return response()->json(MyHelper::checkGet($outlet));
    }

    function listOutletProductSpecialPrice(OutletList $request) {
        $post = $request->json()->all();

        $outlet = Outlet::with(['user_outlets','city','today', 'outlet_schedules'])
            ->where('outlet_different_price', 1)
            ->select('*');
        if(isset($post['id_product'])){
            $outlet = $outlet->with(['product_special_price'=> function($q) use ($post){
                        $q->where('id_product', $post['id_product']);
                    }]);
        }else{
            $outlet = $outlet->with(['product_special_price']);
        }

        return response()->json(MyHelper::checkGet($outlet->paginate(20)));
    }
    /**
     * list
     */
    function listOutlet(OutletList $request) {
        $post = $request->json()->all();

        if (isset($post['webview'])) {
            $outlet = Outlet::with(['today', 'brands']);
        }elseif(isset($post['admin']) && isset($post['type']) && $post['type'] == 'export'){
            $outlet = Outlet::with(['user_outlets','city','today','product_prices','product_prices.product'])->select('*');
        }elseif(isset($post['admin'])){
            $outlet = Outlet::with(['user_outlets','city.province','today', 'outlet_schedules'])->select('*');
            if(isset($post['id_product'])){
                $outlet = $outlet->with(['product_detail'=> function($q) use ($post){
                    $q->where('id_product', $post['id_product']);
                }, 'product_special_price'=> function($q) use ($post){
                    $q->where('id_product', $post['id_product']);
                }]);
            }else{
                $outlet = $outlet->with(['product_detail', 'product_special_price']);
            }
        }
        elseif($post['simple_result']??false) {
            $outlet = Outlet::select('outlets.id_outlet','outlets.outlet_name');
        }elseif(($post['filter']??false) == 'different_price'){
            $outlet = Outlet::where('outlet_different_price','1')->select('id_outlet','outlet_name','outlet_code');
        }elseif(\Request::route()->getName() == 'outlet_be'){
            $outlet = Outlet::with(['today', 'brands', 'city'])->select('id_outlet','status_franchise','outlet_name','outlet_code', 'outlet_status', 'id_city', 'time_zone_utc');
        }else{
            $outlet = Outlet::with(['city', 'outlet_photos', 'outlet_schedules', 'today', 'user_outlets','brands']);
            if(!($post['id_outlet']??false)||!($post['id_outlet']??false)){
                $outlet->select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude', 'outlets.status_franchise', 'outlets.time_zone_utc');
            }
        }
        if($post['rule']??false){
            $this->filterList($outlet,$post['rule'],$post['operator']??'and');
        }
        if(($post['order_field']??false)&&($post['order_method']??false)){
            $outlet->orderBy($post['order_field'],$post['order_method']);
        }
        if($post['simple_result']??false){
            $outlet->select('outlets.id_outlet','outlets.outlet_name');
        }
        if(is_array($post['id_brand']??false)&&$post['id_brand']){
            $outlet->leftJoin('brand_outlet','outlets.id_outlet','brand_outlet.id_outlet');
            $id_brands=$post['id_brand'];
            $outlet->where(function($query) use ($id_brands){
                foreach ($id_brands as $id_brand) {
                    $query->orWhere('brand_outlet.id_brand',$id_brand);
                }
            });
        }

        if($post['key_free']??false){
            $outlet->where('outlets.outlet_name','LIKE','%'.$post['key_free'].'%');
        }

        if (isset($post['outlet_code'])) {
            $outlet->with(['holidays', 'holidays.date_holidays','brands', 'delivery_outlet', 'merchant'])->where('outlet_code', $post['outlet_code']);
        }

        if (isset($post['id_outlet'])) {
            if(!isset($post['webview'])){
                $outlet->with(['holidays', 'holidays.date_holidays', 'product_prices.product']);
            }
            $outlet->where('id_outlet', $post['id_outlet']);
        }

        if (isset($post['id_city'])) {
            $outlet->where('id_city',$post['id_city']);
        }


        if(isset($post['all_outlet']) && $post['all_outlet'] == 0){
            $outlet = $outlet->where('outlet_status', 'Active')->whereNotNull('id_city');
            $outlet->whereHas('brands',function($query){
                $query->where('brand_active','1');
            });
        }

        // qrcode
        if (isset($post['qrcode'])){
            if(isset($post['qrcode_paginate'])){
                $outlet = $outlet->orderBy('outlet_name')->paginate(5)->toArray();
                foreach ($outlet['data'] as $key => $value) {
                    $qr      = $value['outlet_code'];

                    $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
                    $qrCode = html_entity_decode($qrCode);

                    $outlet['data'][$key]['qrcode'] = $qrCode;
                }
                $loopdata=&$outlet['data'];
            }else{
                $outlet = $outlet->orderBy('outlet_name')->get()->toArray();
                foreach ($outlet as $key => $value) {
                    $qr      = $value['outlet_code'];

                    $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
                    $qrCode = html_entity_decode($qrCode);

                    $outlet[$key]['qrcode'] = $qrCode;
                }
                $loopdata=&$outlet;
            }
            $request['page'] = 0;
        }else{
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($_SERVER['HTTP_USER_AGENT'],'iOS')) $useragent = 'iOS';
            if(stristr($_SERVER['HTTP_USER_AGENT'],'okhttp')) $useragent = 'Android';
            if($useragent == 'Android' || $useragent == 'iOS'){
                $outlet = $outlet->orderBy('outlet_name')->get()->toArray();
                foreach ($outlet as $keyOutlet => $valueOutlet) {
                    $countBrandNotActive = 0;
                    foreach ($valueOutlet['brands'] as $keyBrand => $valueBrand) {
                        if ($valueBrand['brand_active'] == 0) {
                            $countBrandNotActive++;
                        }
                    }
                    if (count($valueOutlet['brands']) == $countBrandNotActive) {
                        $outlet[$keyOutlet]['outlet_status'] = 'Inactive';
                    }
                }
            } else {
                $outlet = $outlet->orderBy('outlet_name')->get()->toArray();
            }
            $loopdata=&$outlet;
        }
        $loopdata = array_map(function($var) use ($post){
            $var['url']=config('url.api_url').'api/outlet/webview/'.$var['id_outlet'];
            //get timezone from province
            if(isset($var['city']['province']['time_zone_utc'])){
                $var['time_zone_utc'] = $var['city']['province']['time_zone_utc'];
            }
            if(isset($var['outlet_schedules'])){
                foreach($var['outlet_schedules'] as $index => $sch){
                    $var['outlet_schedules'][$index] = $this->getTimezone($var['outlet_schedules'][$index], $var['time_zone_utc']);
                }
            }
            if (isset($var['time_zone_utc'])) {
            	$var['today'] = $this->getTimezone($var['today'], $var['time_zone_utc']);
            }

            if(($post['latitude']??false)&&($post['longitude']??false)){
                $var['distance']=number_format((float)$this->distance($post['latitude'], $post['longitude'], $var['outlet_latitude'], $var['outlet_longitude'], "K"), 2, '.', '').' km';
            }
            return $var;
        }, $loopdata);

        // promo code
        if(!isset($post['qrcode'])){
            foreach ($outlet as $key => $value) {
                $outlet[$key]['is_promo'] = 0;
            }
        }

        if (isset($post['promo_code'])) {
        	$code=PromoCampaignPromoCode::where('promo_code',$request->promo_code)
	                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
	                ->where('step_complete', '=', 1)
	                ->where( function($q){
	                	$q->whereColumn('usage','<','limitation_usage')
	                		->orWhere('code_type','Single');
	                } )
	                ->with(['promo_campaign.promo_campaign_outlets'])
	                ->first();

	        if(!$code){
	            return [
	                'status'=>'fail',
	                'messages'=>['Promo code not valid']
	            ];
	        }else{

	        	$code = $code->toArray();
        		if ($code['promo_campaign']['is_all_outlet']) {
        			foreach ($outlet as $key => $value) {
    					$outlet[$key]['is_promo'] = 1;
    				}
        		}else{
		        	foreach ($code['promo_campaign']['promo_campaign_outlets'] as $key => $value) {
	        			foreach ($outlet as $key2 => $value2) {
	        				if ( $value2['id_outlet'] == $value['id_outlet'] ) {
	    						$outlet[$key2]['is_promo'] = 1;
	    						break;
	    					}
	        			}
		        	}
        		}
	        }
        }

        if (isset($post['webview'])) {
            if(isset($outlet[0])){
                $latitude  = $post['latitude'];
                $longitude = $post['longitude'];
                $jaraknya = number_format((float)$this->distance($latitude, $longitude, $outlet[0]['outlet_latitude'], $outlet[0]['outlet_longitude'], "K"), 2, '.', '');
                $outlet[0]['distance'] = $jaraknya." km";

                $outlet[0]['url'] = config('url.api_url').'api/outlet/webview/'.$post['id_outlet'];

                if(isset($outlet[0]['holidays'])) unset($outlet[0]['holidays']);
            }
        }
        if($post['simple_result']??false){
            $outlet=array_map(function($var){
                return [
                    'id_outlet'=>$var['id_outlet'],
                    'outlet_name'=>$var['outlet_name']
                ];
            },$outlet);
        }
        if($outlet&&($post['id_outlet']??false)){
            $var=&$outlet[0];
            $var['deep_link_gojek']=$var['deep_link_gojek']??'';
            $var['deep_link_grab']=$var['deep_link_grab']??'';
        }
        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $outlet;
            $outlet = [];
            $pagingOutlet=$this->pagingOutlet($dataOutlet, $page,$post['take']??15);
            if (isset($pagingOutlet['data']) && count($pagingOutlet['data']) > 0) {
                $outlet['current_page']  = $page;
                $outlet['data']          = $pagingOutlet['data'];
                $outlet['total']         = count($dataOutlet);
                $outlet['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $outlet['next_page_url'] = ENV('APP_API_URL').'api/outlet/list?page='.$next_page;
                }
            } else {
                $outlet = [];
            }
        }

        return response()->json(MyHelper::checkGet($outlet));

    }

    /* City Outlet */
    function cityOutlet(Request $request) {
        $outlet = Outlet::join('cities', 'cities.id_city', '=', 'outlets.id_city')->where('outlet_status', 'Active')->select('outlets.id_city', 'city_name')->orderBy('city_name', 'ASC')->distinct()->get()->toArray();

        // if (!empty($outlet)) {
        //     $outlet = array_pluck($outlet, 'city_name');
        // }
        return response()->json(MyHelper::checkGet($outlet));
    }

    /* Near Me*/
    function nearMe(Nearme $request) {
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!$latitude || !$longitude){
            return response()->json(['status' => 'fail', 'messages' => ['Pastikan pengaturan lokasi pada smartphone terhubung']]);
        }

        // outlet
        $outlet = Outlet::select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_phone','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude','time_zone_utc')->with(['today','city.province','brands'=>function($query){$query->select('brands.id_brand','name_brand','logo_brand');}])->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');
        if($request->json('search') && $request->json('search') != ""){
            $outlet = $outlet->where(function($query) use ($request) {
                $query->where('outlet_name', 'LIKE', '%'.$request->json('search').'%')
                    ->orWhere('outlet_address', 'LIKE', '%'.$request->json('search').'%');
            });
        }
        $outlet->whereHas('brands',function($query){
            $query->where('brand_active','1');
        });
        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
            $processing = '0';
            $settingTime = Setting::where('key', 'processing_time')->first();
            if($settingTime && $settingTime->value){
                $processing = $settingTime->value;
            }
            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;

                //get timezone from province
                if(isset($outlet[$key]['city']['province']['time_zone_utc'])){
                    $outlet[$key]['time_zone_utc'] = $outlet[$key]['city']['province']['time_zone_utc'];
                }
                $outlet[$key]['today'] = $this->getTimezone($outlet[$key]['today'], $outlet[$key]['time_zone_utc']);

                // $outlet[$key] = $this->setAvailableOutlet($outlet[$key], $processing);
            }
            usort($outlet, function($a, $b) {
                return $a['dist'] <=> $b['dist'];
            });

        }else{
            return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
        }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $outlet;
            $outlet = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);

            $check_holiday = $this->checkOutletHoliday();
            foreach ($pagingOutlet['data'] as $key => $value) {
	            if ($check_holiday['status'] && in_array($pagingOutlet['data'][$key]['id_outlet'], $check_holiday['list_outlet'])) {
	            	$pagingOutlet['data'][$key]['today']['is_closed'] = 1;
	            }
	            $pagingOutlet['data'][$key] = $this->setAvailableOutlet($pagingOutlet['data'][$key], $processing);
            }

            if (isset($pagingOutlet['data']) && count($pagingOutlet['data']) > 0) {
                $outlet['current_page']  = $page;
                $outlet['data']          = $pagingOutlet['data'];
                $outlet['total']         = count($dataOutlet);
                $outlet['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $outlet['next_page_url'] = ENV('APP_API_URL').'api/outlet/nearme?page='.$next_page;
                }
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
            }
        }

        if(!$outlet){
            return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

    /* Near Me Geolocation, return geojson */
    function nearMeGeolocation(Nearme $request) {
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!$latitude || !$longitude){
            return response()->json(['status' => 'success', 'messages' => ['Pastikan pengaturan lokasi pada smartphone terhubung']]);
        }

        // outlet
        $outlet = Outlet::with(['today', 'city.province', 'outlet_photos'])->orderBy('outlet_name','asc')->where('outlet_status', 'Active')->whereNotNull('id_city');
        if($request->json('search') && $request->json('search') != ""){
            $outlet = $outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%');
        }
        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
            $processing = '0';
            $settingTime = Setting::where('key', 'processing_time')->first();
            if($settingTime && $settingTime->value){
                $processing = $settingTime->value;
            }

            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;

                // $outlet[$key] = $this->setAvailableOutlet($outlet[$key], $processing);
            }
            usort($outlet, function($a, $b) {
                return $a['dist'] <=> $b['dist'];
            });

        }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $outlet;
            $outlet = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);

            $check_holiday = $this->checkOutletHoliday();
            foreach ($pagingOutlet['data'] as $key => $value) {
	            if ($check_holiday['status'] && in_array($pagingOutlet['data'][$key]['id_outlet'], $check_holiday['list_outlet'])) {
	            	$pagingOutlet['data'][$key]['today']['is_closed'] = 1;
	            }
	            $pagingOutlet['data'][$key] = $this->setAvailableOutlet($pagingOutlet['data'][$key], $processing);
            }

            // format outlet data into geojson
            $pagingOutlet['data'] = $this->geoJson($pagingOutlet['data']);

            if (count($pagingOutlet) > 0) {
                $outlet['status'] = 'success';
                $outlet['current_page']  = $page;
                $outlet['data']          = $pagingOutlet['data'];
                $outlet['total']         = count($dataOutlet);
                $outlet['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $outlet['next_page_url'] = ENV('APP_API_URL').'api/outlet/nearme?page='.$next_page;
                }
            } else {
                $outlet['status'] = 'fail';
                $outlet['messages'] = ['empty'];

            }
        }
        else {
            // format result into geojson
            $outlet = $this->geoJson($outlet);
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

    public function pagingOutlet($data, $page,$paginate=15) {
        $next = false;

        if ($page > 0) {
            $resultData = [];
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all >= count($data)) {
                $end = count($data);
                $next = false;
            }

            for ($i=$start; $i < $end; $i++) {
                array_push($resultData, $data[$i]);
            }

            return ['data' => $resultData, 'status' => $next];
        }


        return ['data' => $data, 'status' => $next];
    }

    // create geojson format
    private function geoJson ($locales)
    {
        $original_data = $locales;
        $features = array();

        foreach($original_data as $key => $value) {
            $features[] = array(
                    'type' => 'Feature',
                    'geometry' => array(
                        'type' => 'Point',
                        'coordinates' => array(
                            (float) $value['outlet_longitude'],
                            (float) $value['outlet_latitude']
                        )
                    ),
                    'properties' => array(
                        'title' => $value['outlet_name'],
                        'id_outlet' => $value['id_outlet'],
                        'url' => $value['url'],
                        'today' => $value['today'],
                        'distance' => $value['distance'],
                        'dist' => $value['dist']
                    ),
                );
            };

        $allfeatures = array('type' => 'FeatureCollection', 'features' => $features);

        // write into file
        Storage::disk('s3')->put('stations.geojson', json_encode($allfeatures));
        // Storage::disk('public_custom')->put('stations.geojson', json_encode($allfeatures));

        return $allfeatures;
    }

    function filter_distance($outlet, $location){
        $processing = '0';
        $settingTime = Setting::where('key', 'processing_time')->first();
        if($settingTime && $settingTime->value){
            $processing = $settingTime->value;
        }

        $check_holiday = $this->checkOutletHoliday();

        foreach ($outlet as $key => $value) {
            $jaraknya =   number_format((float)$this->distance($location['latitude'], $location['longitude'], $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
            settype($jaraknya, "float");

            $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
            $outlet[$key]['dist']     = (float) $jaraknya;

            if($location['distance'] == "0-2km"){
                if((float) $jaraknya < 0.01 || (float) $jaraknya > 2.00)
                    unset($outlet[$key]);
                    continue;
            }

            if($location['distance'] == "2-5km"){
                if((float) $jaraknya < 2.00 || (float) $jaraknya > 5.00)
                    unset($outlet[$key]);
                    continue;
            }

            if($location['distance'] == ">5km"){
                if((float) $jaraknya < 5.00)
                    unset($outlet[$key]);
                    continue;
            }

            if($location['id_city'] != "" && $location['id_city'] != $value['id_city']){
                unset($outlet[$key]);
                continue;
            }

            if ($check_holiday['status'] && in_array($outlet[$key]['id_outlet'], $check_holiday['list_outlet'])) {
            	$outlet[$key]['today']['is_closed'] = 1;
            }

            $outlet[$key] = $this->setAvailableOutlet($outlet[$key], $processing);
        }

        return $outlet;
    }

    function check_outlet($outlet, $post, &$promo_error){
        // give all product flag is_promo = 0
        foreach ($outlet as $key => $value) {
            $outlet[$key]['is_promo'] = 0;
        }
        
        $promo_data = $this->applyPromo($post, $outlet, $promo_error);
        if ($promo_data) {
            $outlet = $promo_data;
        }

        $outlet = $this->filter_distance($outlet, [
            'latitude' => $post['latitude'], 
            'longitude' => $post['longitude'],
            'distance' => $post['distance'],
            'id_city' => $post['id_city']
        ]);

        return $outlet;
    }

    /* Filter*/
    function filterProductOutlet(Filter $request){
        $post=$request->except('_token');
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!isset($latitude) || !isset($longitude)){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Pastikan pengaturan lokasi pada smartphone terhubung']
            ]);
        }

        $distance = $request->json('distance');
        $id_city = $request->json('id_city');
        $sort = $request->json('sort');
        $gofood = $request->json('gofood');
        $grabfood = $request->json('grabfood');  

        $product = Product::with(['global_price', 'product_special_price'])->where('product_visibility', 'Visible')->select('id_product','product_name', 'product_code', 'id_product_category','product_description','product_video','product_allow_sync', 'position');
        $outlet = Outlet::with(['today', 'city.province'])->distinct('outlets.id_outlet')->select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_phone','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude','outlets.delivery_order')->with(['brands'=>function($query){$query->select('brands.id_brand','name_brand','logo_brand');}])->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');
        $outlet->whereHas('brands',function($query){
            $query->where('brand_active','1');
        });

        $countAll=$outlet->count();

        if(is_array($post['id_brand']??false)&&$post['id_brand']){
            $outlet->leftJoin('brand_outlet','outlets.id_outlet','brand_outlet.id_outlet');
            $id_brands=$post['id_brand'];
            $outlet->where(function($query) use ($id_brands){
                foreach ($id_brands as $id_brand) {
                    $query->orWhere('brand_outlet.id_brand',$id_brand);
                }
            });
        }

        if ($gofood) {
            $outlet = $outlet->whereNotNull('deep_link_gojek');
        }
        
        if ($grabfood) {
            $outlet = $outlet->whereNotNull('deep_link_grab');
        }
        
        if($request->json('search') && $request->json('search') != ""){
            $search_outlet = clone $outlet;
            $search_product = clone $product;

            $search_outlet = $search_outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%')->get()->toArray();
            $search_product = $search_product->where('product_name', 'LIKE', '%'.$request->json('search').'%')->get()->toArray();
        }else{
            $search_outlet =  $outlet->get()->toArray();
            $search_product =  $product->get()->toArray();
        }

        // kondisi outlet dan product dapat => return outletnya saja
        // kondisi cuma outlet yang dapat   => return outletnya saja
        // kondisi cuma product yg dapat    => return outlet di dalamnya list product yg didapat
        // kondisi tidak dapat apa-apa      => return data empty

        $post['distance'] = $distance; 
        $post['id_city'] = $id_city; 

        if (!empty($search_outlet)) {
            // check outlet based on distance, check promo
            $search_outlet = $this->check_outlet($search_outlet, $post, $promo_error);
            
			if($sort != 'Alphabetical'){
				usort($search_outlet, function($a, $b) {
					return $a['dist'] <=>  $b['dist'];
				});
            }
            
			$urutan = array();
			if($search_outlet){
				foreach($search_outlet as $o){
					array_push($urutan, $o);
				}
            }

        } elseif(!empty($search_product)){

            $outlets = $outlet->get()->toArray();
            //loop through selected outlets
            foreach($outlets as $key_outlet => $outlet){
                //assign product to outlets
                $count_product = 0;
                foreach($search_product as $key_product => $product){
                    if(!empty($product['global_price'])){
                        // if product has global price, assign product to outlet
                        $product_detail = ProductDetail::where('id_outlet', $outlet['id_outlet'])->where('id_product', $product['id_product'])->first();
                        if($product_detail){
                            // if product and outlet exist in product detail
                            // check visibility in product detail
                            if($product_detail['product_detail_visibility'] == 'Visible'){
                                $outlets[$key_outlet]['product'][$count_product] = $product;
                                $outlets[$key_outlet]['product'][$count_product]['product_price'] = $outlets[$key_outlet]['product'][$count_product]['global_price'][0]['product_global_price'] ?? null;
                                
                                if(!empty($product['product_special_price'])){
                                    // if outlet and product exist in product special price
                                    // use special price instead of global price
                                    foreach($product['product_special_price'] as $special_price){
                                        if($special_price['id_outlet'] == $outlets[$key_outlet]['id_outlet']){
                                            $outlets[$key_outlet]['product'][$count_product]['product_price'] = $special_price['product_special_price'];
                                            break;
                                        }
                                    }
                                }
                                
                                unset($outlets[$key_outlet]['product'][$count_product]['global_price']);
                                unset($outlets[$key_outlet]['product'][$count_product]['product_special_price']);

                                $count_product++;
                            }
                            
                        }else{
                            // still use default visibility
                            $outlets[$key_outlet]['product'][$count_product] = $product;
                            $outlets[$key_outlet]['product'][$count_product]['product_price'] = $outlets[$key_outlet]['product'][$count_product]['global_price'][0]['product_global_price'] ?? null;
                            
                            if(!empty($product['product_special_price'])){
                                // if outlet and product exist in product special price
                                // use special price instead of global price
                                foreach($product['product_special_price'] as $special_price){
                                    if($special_price['id_outlet'] == $outlets[$key_outlet]['id_outlet']){
                                        $outlets[$key_outlet]['product'][$count_product]['product_price'] = $special_price['product_special_price'];
                                        break;
                                    }
                                }
                            }
                            
                            unset($outlets[$key_outlet]['product'][$count_product]['global_price']);
                            unset($outlets[$key_outlet]['product'][$count_product]['product_special_price']);

                            $count_product++;
                        }
                    }
                }
            }

            // check outlet based on distance, check promo 
            $outlets = $this->check_outlet($outlets, $post, $promo_error);

            if($sort != 'Alphabetical'){
				usort($outlets, function($a, $b) {
					return $a['dist'] <=>  $b['dist'];
				});
            }
            
			$urutan = array();
			if($outlets){
				foreach($outlets as $o){
					array_push($urutan, $o);
				}
            }
            
        } else {
            if($countAll){
                if($request->json('search')){
                    return response()->json(['status' => 'fail', 'messages' => ['Data tidak ditemukan']]);
                }
                return response()->json(['status' => 'fail', 'messages' => ['empty']]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
            }
        }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $urutan;
            $urutan = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);
            if (isset($pagingOutlet['data']) && count($pagingOutlet['data']) > 0) {
                $urutan['current_page']  = $page;
                $urutan['data']          = $pagingOutlet['data'];
                $urutan['total']         = count($dataOutlet);
                $urutan['total_promo']	 = app($this->promo)->availablePromo();
                $urutan['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $urutan['next_page_url'] = ENV('APP_API_URL').'api/outlet/filter?page='.$next_page;
                }
                $urutan['promo_error']   = $promo_error;
            } else {
                if($countAll){
                    return response()->json(['status' => 'fail', 'messages' => ['empty']]);
                }else{
                    return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
                }
            }
        }
        if(!$urutan){
            if($countAll){
                return response()->json(['status' => 'fail', 'messages' => ['empty']]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
            }
        }
        return response()->json(MyHelper::checkGet($urutan));
    }
  
    /* Filter*/
    function filter(Filter $request) {
        $post=$request->except('_token');
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!isset($latitude) || !isset($longitude)){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Pastikan pengaturan lokasi pada smartphone terhubung']
            ]);
        }

        $distance = $request->json('distance');
        $id_city = $request->json('id_city');
        $sort = $request->json('sort');
        $gofood = $request->json('gofood');
        $grabfood = $request->json('grabfood');

        // outlet
        $outlet = Outlet::with(['today', 'city.province'])->distinct('outlets.id_outlet')->select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_phone','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude','time_zone_utc','delivery_order')->with(['brands'=>function($query){$query->select('brands.id_brand','name_brand','logo_brand');}])->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');

        $outlet->whereHas('brands',function($query){
            $query->where('brand_active','1');
        });

        $countAll=$outlet->count();

        if(is_array($post['id_brand']??false)&&$post['id_brand']){
            $outlet->leftJoin('brand_outlet','outlets.id_outlet','brand_outlet.id_outlet');
            $id_brands=$post['id_brand'];
            $outlet->where(function($query) use ($id_brands){
                foreach ($id_brands as $id_brand) {
                    $query->orWhere('brand_outlet.id_brand',$id_brand);
                }
            });
        }

        if($request->json('search') && $request->json('search') != ""){
            $outlet->where(function($query) use ($request) {
                $query->where('outlet_name', 'LIKE', '%'.$request->json('search').'%')
                    ->orWhere('outlet_address', 'LIKE', '%'.$request->json('search').'%');
            });
        }

        if ($gofood) {
            $outlet = $outlet->whereNotNull('deep_link_gojek');
        }

        if ($grabfood) {
            $outlet = $outlet->whereNotNull('deep_link_grab');
        }

        $outlet = $outlet->get()->toArray();


        if (!empty($outlet)) {
            $processing = '0';
            $settingTime = Setting::where('key', 'processing_time')->first();
            if($settingTime && $settingTime->value){
                $processing = $settingTime->value;
            }

            // give all product flag is_promo = 0
	        foreach ($outlet as $key => $value) {
				$outlet[$key]['is_promo'] = 0;
			}

			$promo_data = $this->applyPromo($post, $outlet, $promo_error);

	        if ($promo_data) {
	        	$outlet = $promo_data;
	        }

            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;

				if($distance == "0-2km"){
					if((float) $jaraknya < 0.01 || (float) $jaraknya > 2.00)
                        unset($outlet[$key]);
                        continue;
				}

				if($distance == "2-5km"){
					if((float) $jaraknya < 2.00 || (float) $jaraknya > 5.00)
                        unset($outlet[$key]);
                        continue;
				}

				if($distance == ">5km"){
					if((float) $jaraknya < 5.00)
                        unset($outlet[$key]);
                        continue;
				}

				if($id_city != "" && $id_city != $value['id_city']){
                    unset($outlet[$key]);
                    continue;
                }
                //get timezone from province
                if(isset($outlet[$key]['city']['province']['time_zone_utc'])){
                    $outlet[$key]['time_zone_utc'] = $outlet[$key]['city']['province']['time_zone_utc'];
                }
                $outlet[$key]['today'] = $this->getTimezone($outlet[$key]['today'], $outlet[$key]['time_zone_utc']);

                // $outlet[$key] = $this->setAvailableOutlet($outlet[$key], $processing);;
            }
			if($sort != 'Alphabetical'){
				usort($outlet, function($a, $b) {
					return $a['dist'] <=>  $b['dist'];
				});
			}
			$urutan = array();
			if($outlet){
				foreach($outlet as $o){
					array_push($urutan, $o);
				}
            }
        } else {
            if($countAll){
                if($request->json('search')){
                    return response()->json(['status' => 'fail', 'messages' => ['Outlet tidak ditemukan']]);
                }
                return response()->json(['status' => 'fail', 'messages' => ['empty']]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
            }
        }

        // if (!isset($request['page'])) {
        //     $request['page'] = 1;
        // }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $urutan;
            $urutan = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);

            $check_holiday = $this->checkOutletHoliday();
            foreach ($pagingOutlet['data'] as $key => $value) {
	            if ($check_holiday['status'] && in_array($pagingOutlet['data'][$key]['id_outlet'], $check_holiday['list_outlet'])) {
	            	$pagingOutlet['data'][$key]['today']['is_closed'] = 1;
	            }
	            $pagingOutlet['data'][$key] = $this->setAvailableOutlet($pagingOutlet['data'][$key], $processing);
            }

            if (isset($pagingOutlet['data']) && count($pagingOutlet['data']) > 0) {
                $urutan['current_page']  = $page;
                $urutan['data']          = $pagingOutlet['data'];
                $urutan['total']         = count($dataOutlet);
                $urutan['total_promo']	 = app($this->promo)->availablePromo();
                $urutan['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $urutan['next_page_url'] = ENV('APP_API_URL').'api/outlet/filter?page='.$next_page;
                }
                $urutan['promo_error']   = $promo_error;
            } else {
                if($countAll){
                    return response()->json(['status' => 'fail', 'messages' => ['empty']]);
                }else{
                    return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
                }
            }
        }
        if(!$urutan){
            if($countAll){
                return response()->json(['status' => 'fail', 'messages' => ['empty']]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['There is no open store','at this moment']]);
            }
        }
        return response()->json(MyHelper::checkGet($urutan));
    }

    /* Filter Geolocation, return geojson */
    function filterGeolocation(Filter $request) {
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!isset($latitude) || !isset($longitude)){
            return response()->json([
                'status' => 'success',
                'messages' => ['Pastikan pengaturan lokasi pada smartphone terhubung']
            ]);
        }

        $distance = $request->json('distance');
        $id_city = $request->json('id_city');
        $sort = $request->json('sort');

        // outlet
        $outlet = Outlet::with(['today', 'city.province', 'outlet_photos'])->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');
        if($request->json('search') && $request->json('search') != ""){
            $outlet = $outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%');
        }
        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
            $processing = '0';
            $settingTime = Setting::where('key', 'processing_time')->first();
            if($settingTime && $settingTime->value){
                $processing = $settingTime->value;
            }

            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;

                if($distance == "0-2km"){
                    if((float) $jaraknya < 0.01 || (float) $jaraknya > 2.00)
                        unset($outlet[$key]);
                }

                if($distance == "2-5km"){
                    if((float) $jaraknya < 2.00 || (float) $jaraknya > 5.00)
                        unset($outlet[$key]);
                }

                if($distance == ">5km"){
                    if((float) $jaraknya < 5.00)
                        unset($outlet[$key]);
                }

                if($id_city != "" && $id_city != $value['id_city']){
                    unset($outlet[$key]);
                }

                // $outlet[$key] = $this->setAvailableOutlet($outlet[$key], $processing);
            }
            if($sort != 'Alphabetical'){
                usort($outlet, function($a, $b) {
                    return $a['dist'] <=>  $b['dist'];
                });
            }
            $urutan = array();
            if($outlet){
                foreach($outlet as $o){
                    array_push($urutan, $o);
                }
            }

        } else {
            return response()->json(MyHelper::checkGet($outlet));
        }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $urutan;
            $urutan = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);

            $check_holiday = $this->checkOutletHoliday();
            foreach ($pagingOutlet['data'] as $key => $value) {
	            if ($check_holiday['status'] && in_array($pagingOutlet['data'][$key]['id_outlet'], $check_holiday['list_outlet'])) {
	            	$pagingOutlet['data'][$key]['today']['is_closed'] = 1;
	            }
	            $pagingOutlet['data'][$key] = $this->setAvailableOutlet($pagingOutlet['data'][$key], $processing);
            }
            
            // format outlet data into geojson
            $pagingOutlet['data'] = $this->geoJson($pagingOutlet['data']);

            if (count($pagingOutlet) > 0) {
                $urutan['status'] = 'success';
                $urutan['current_page']  = $page;
                $urutan['data']          = $pagingOutlet['data'];
                $urutan['total']         = count($dataOutlet);
                $urutan['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $urutan['next_page_url'] = ENV('APP_API_URL').'api/outlet/filter?page='.$next_page;
                }
            } else {
                $urutan['status'] = 'fail';
                $urutan['messages'] = ['empty'];

            }
        }
        else{
            // format result into geojson
            $urutan = $this->geoJson($urutan);
        }

        $geojson_file_url = config('url.api_url') . 'files/stations.geojson' . '?';

        if($urutan && !empty($urutan)) return ['status' => 'success', 'result' => $urutan, 'url'=>$geojson_file_url];
        else if(empty($urutan)) return ['status' => 'fail', 'messages' => ['empty']];
        else return ['status' => 'fail', 'messages' => ['failed to retrieve data']];

        // return response()->json(MyHelper::checkGet($urutan));
    }

    // unset outlet yang tutup dan libur
    function setAvailableOutlet($outlet, $processing){
        $outlet['today']['status'] = 'open';
        $outlet['today']['status_detail'] = '';
        if($outlet['today']['open'] == null || $outlet['today']['close'] == null){
            $outlet['today']['status'] = 'closed';
        }else{
        	$outlet['today']['status_detail'] = 'Hari ini sampai pukul '.$outlet['today']['close'];
            if($outlet['today']['is_closed'] == '1'){
                $schedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])->get()->toArray();
	            $new_days = $this->reorderDays($schedule, $outlet['today']['day']);
	            $i = 0;
	            $found = 0;
	            foreach ($new_days as $key => $value) {
	            	if ($value['is_closed'] != 1) {
                        $outlet['today']['day'] 	= $value['day'];
	            		$outlet['today']['open'] 	= $this->getOneTimezone($value['open'], $outlet['time_zone_utc']);
			           	$outlet['today']['close'] 	= $this->getOneTimezone($value['close'], $outlet['time_zone_utc']);
	            		$found = 1;
	            		break;
	            	}
	            	$i++;
	            }
                $outlet['today']['status'] = 'closed';
                if($i===0) {
                	$outlet['today']['status_detail'] = 'Besok buka pada '.$outlet['today']['open'];
                }elseif($found===0) {
                	$outlet['today']['status_detail'] = 'Tutup sementara';
                }else{
                	$outlet['today']['status_detail'] = $outlet['today']['day'].' buka pada '.$outlet['today']['open'];
                }
            }
            else{
            	$now = $this->getOneTimezone(date('H:i'), $outlet['time_zone_utc']);
            	$now = date('H:i:01', strtotime($now));

            	if (date('H:i', strtotime($outlet['today']['close'])) >= date('H:i', strtotime($outlet['today']['open']))) {
		            if($outlet['today']['open'] && $now < date('H:i', strtotime($outlet['today']['open']))){
                        $outlet['today']['status'] = 'closed';
		                $outlet['today']['status_detail'] = 'Hari ini buka pada '.$outlet['today']['open'];
                    // }elseif($outlet['today']['close'] && $now > date('H:i', strtotime('-'.$processing.' minutes', strtotime($outlet['today']['close'])))){
		            }elseif($outlet['today']['close'] && $now > date('H:i', strtotime($outlet['today']['close']))){
		            	$schedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])->get()->toArray();
			            $new_days = $this->reorderDays($schedule, $outlet['today']['day']);
			            $i = 0;
			            foreach ($new_days as $key => $value) {
			            	if ($value['is_closed'] != 1) {
			            		$outlet['today']['day'] 	= $value['day'];
			            		$outlet['today']['open'] 	= $this->getOneTimezone($value['open'], $outlet['time_zone_utc']);
			            		$outlet['today']['close'] 	= $this->getOneTimezone($value['close'], $outlet['time_zone_utc']);
			            		break;
			            	}
			            	$i++;
			            }
		                $outlet['today']['status'] = 'closed';
		                if ($i===0) {
		            		$outlet['today']['status_detail'] = 'Besok buka pada '.$outlet['today']['open'];
		                }
		                else{
		                	$outlet['today']['status_detail'] = $outlet['today']['day'].' buka pada '.$outlet['today']['open'];
		                }
		            }
		        }
		        else{
		        	if(
		        		$outlet['today']['open'] 
		        		&& $now < date('H:i', strtotime($outlet['today']['open']))
		        		&& $now > date('H:i', strtotime($outlet['today']['close']))
		        	){
		                $outlet['today']['status'] = 'closed';
		                $outlet['today']['status_detail'] = 'Hari ini buka pada '.$outlet['today']['open'];
		            }elseif(
		            	$outlet['today']['close'] 
		            	&& ( $now < date('H:i', strtotime($outlet['today']['open'])) )
		            	&& $now > date('H:i', strtotime('-'.$processing.' minutes', strtotime($outlet['today']['close'])))
		            ){
		            	$schedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])->get()->toArray();
			            $new_days = $this->reorderDays($schedule, $outlet['today']['day']);
			            $i = 0;
			            foreach ($new_days as $key => $value) {
			            	if ($value['is_closed'] != 1) {
			            		$outlet['today']['day'] 	= $value['day'];
			            		$outlet['today']['open'] 	= $value['open'];
			            		$outlet['today']['close'] 	= $value['close'];
			            		break;
			            	}
			            	$i++;
			            }
		                $outlet['today']['status'] = 'closed';
		                if ($i===0) {
		            		$outlet['today']['status_detail'] = 'Besok buka pada '.$outlet['today']['open'];
		                }
		                else{
		                	$outlet['today']['status_detail'] = $outlet['today']['day'].' buka pada '.$outlet['today']['open'];
		                }
		            }
		        }
            }
        }

        return $outlet;
    }

    /**
     * Cek outlet buka atau tutup
     * @param  Array $dataOutlet outlet
     * @return string 'open'/'closed'
     */
    public function checkOutletStatus($dataOutlet){
        if($dataOutlet['today']['open'] == null || $dataOutlet['today']['close'] == null){
            return 'closed';
        }else{
            $processing = '0';
            $settingTime = Setting::where('key', 'processing_time')->first();
            if($settingTime && $settingTime->value){
                $processing = $settingTime->value;
            }
            if($dataOutlet['today']['is_closed'] == '1'){
                return 'closed';
            }else{
                if($dataOutlet['today']['open'] != "00:00" && $dataOutlet['today']['close'] != "00:00"){
                    if($dataOutlet['today']['open'] && date('H:i:01') < date('H:i', strtotime($dataOutlet['today']['open']))){
                        return 'closed';
                    }elseif($dataOutlet['today']['close'] && date('H:i') > date('H:i', strtotime('-'.$processing.' minutes', strtotime($dataOutlet['today']['close'])))){
                        return 'closed';
                    }else{
                        $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                        ->where('id_outlet', $dataOutlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
                        if(count($holiday) > 0){
                            foreach($holiday as $i => $holi){
                                if($holi['yearly'] == '0'){
                                    if($holi['date'] == date('Y-m-d')){
                                            return 'closed';
                                        break;
                                    }
                                }else{
                                        return 'closed';
                                    break;
                                }
                            }

                        }
                    }
                }
            }
        }
        return 'open';
    }

    /* Penghitung jarak */
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        $lat1=floatval($lat1);
        $lat2=floatval($lat2);
        $lon1=floatval($lon1);
        $lon2=floatval($lon2);
        $theta = $lon1 - $lon2;
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit  = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    function listHoliday(Request $request) {
        $post = $request->json()->all();

        $holiday = Holiday::with(['outlets', 'date_holidays']);
        if (isset($post['id_holiday'])) {
            $holiday->where('id_holiday', $post['id_holiday']);
        }

        if (isset($post['id_outlet'])) {
            $holiday->where('id_outlet', $post['id_outlet']);
        }

        $holiday = $holiday->get()->toArray();

        return response()->json(MyHelper::checkGet($holiday));

    }

    function deleteHoliday(HolidayDelete $request) {

        $data = Holiday::where('id_holiday', $request->json('id_holiday'))->first();

        if ($data) {
            $data->date_holidays()->delete();
            $delete = Holiday::where('id_holiday', $request->json('id_holiday'))->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }
        else {
            return response()->json([
                    'status' => 'fail',
                    'messages' => ['data outlet holiday not found.']
                ]);
        }
    }

    function createHoliday(HolidayStore $request) {
        $post = $request->json()->all();

        $yearly = 0;
        if(isset($post['yearly'])){
            $yearly = 1;
        }

        $holiday = [
            'holiday_name'  => $post['holiday_name'],
            'yearly'        => $yearly
        ];

        DB::beginTransaction();
        $insertHoliday = Holiday::create($holiday);

        if ($insertHoliday) {
            $dateHoliday = [];
            $date = $post['date_holiday'];

            foreach ($date as $value) {
                $dataDate = [
                    'id_holiday'    => $insertHoliday['id_holiday'],
                    'date'          => date('Y-m-d', strtotime($value)),
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s')
                ];

                array_push($dateHoliday, $dataDate);
            }

            $insertDateHoliday = DateHoliday::insert($dateHoliday);

            if ($insertDateHoliday) {
                $outletHoliday = [];
                $outlet = $post['id_outlet'];

                foreach ($outlet as $ou) {
                    $dataOutlet = [
                        'id_holiday'    => $insertHoliday['id_holiday'],
                        'id_outlet'     => $ou,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s')
                    ];

                    array_push($outletHoliday, $dataOutlet);
                }

                $insertOutletHoliday = OutletHoliday::insert($outletHoliday);

                if ($insertOutletHoliday) {
                    DB::commit();
                    return response()->json(MyHelper::checkCreate($insertOutletHoliday));

                } else {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'      => [
                            'Data is invalid !!!'
                        ]
                    ]);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Data is invalid !!!'
                    ]
                ]);
            }

        } else {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Data is invalid !!!'
                ]
            ]);
        }
    }

    public function updateHoliday(HolidayUpdate $request) {
        $post = $request->json()->all();

        $yearly = 0;
        if(isset($post['yearly'])){
            $yearly = 1;
        }
        $holiday = [
            'holiday_name'  => $post['holiday_name'],
            'yearly'        => $yearly
        ];

        DB::beginTransaction();
        $updateHoliday = Holiday::where('id_holiday', $post['id_holiday'])->update($holiday);

        if ($updateHoliday) {
            $delete = DateHoliday::where('id_holiday', $post['id_holiday'])->delete();

            if ($delete) {
                $dateHoliday = [];
                $date = $post['date_holiday'];

                foreach ($date as $value) {
                    $dataDate = [
                        'id_holiday'    => $post['id_holiday'],
                        'date'          => date('Y-m-d', strtotime($value)),
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s')
                    ];

                    array_push($dateHoliday, $dataDate);
                }

                $updateDateHoliday = DateHoliday::insert($dateHoliday);

                if ($updateDateHoliday) {
                    $deleteOutletHoliday = OutletHoliday::where('id_holiday', $post['id_holiday'])->delete();

                    if ($deleteOutletHoliday) {
                        $outletHoliday = [];
                        $outlet = $post['id_outlet'];

                        foreach ($outlet as $ou) {
                            $dataOutlet = [
                                'id_holiday'    => $post['id_holiday'],
                                'id_outlet'     => $ou,
                                'created_at'    => date('Y-m-d H:i:s'),
                                'updated_at'    => date('Y-m-d H:i:s')
                            ];

                            array_push($outletHoliday, $dataOutlet);
                        }

                        $insertOutletHoliday = OutletHoliday::insert($outletHoliday);

                        if ($insertOutletHoliday) {
                            DB::commit();
                            return response()->json(MyHelper::checkCreate($insertOutletHoliday));

                        } else {
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'      => [
                                    'Data is invalid !!!'
                                ]
                            ]);
                        }
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'      => [
                                'Data is invalid !!!'
                            ]
                        ]);
                    }

                } else {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'      => [
                            'Data is invalid !!!'
                        ]
                    ]);
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($updateHoliday));
    }

    function exportCity(Request $request) {
        $cities = City::select('city_name as City')->groupBy('city_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($cities));
    }

    function export(Request $request) {
        $brands=$request->json('brands');
        $combo=$request->json('outlet_type')=='combo';
        $all=$request->json('outlet_type')=='all';
        $return=[];
        foreach ($brands??[[]] as $brand) {
            $outlets = Outlet::select('id_outlet','outlets.outlet_code as code',
                'outlets.outlet_name as name',
                'outlets.outlet_address as address',
                'cities.city_name as city',
                'outlets.outlet_phone as phone',
                'outlets.outlet_email as email',
                'outlets.outlet_latitude as latitude',
                'outlets.outlet_longitude as longitude',
                'outlets.outlet_status',
                DB::raw('(CASE
                            WHEN status_franchise = 1 THEN "Mitra"
                            ELSE "Pusat"
                        END) as "status_mitra"'),
                DB::raw('(CASE
                            WHEN delivery_order = 1 THEN "Active"
                            ELSE "Inactive"
                        END) as "delivery"'),
                'outlets.time_zone_utc'
            )->with('brands')->join('cities', 'outlets.id_city', '=', 'cities.id_city');

            foreach ($brand as $bran) {
                $outlets->whereHas('brands',function($query) use ($brand){
                    $query->where('brands.id_brand',$brand);
                });
            }

            $outlets = $outlets->get();
            $count=0;
            foreach ($outlets as $outlet) {
                if($all){
                    $name='All Type';
                }else{
                    if(count($outlet->brands)!=count($brand)){
                        continue;
                    }
                    $continue=false;
                    foreach ($outlet->brands as $outlet_brand) {
                        if(!in_array($outlet_brand->id_brand, $brand)){
                            $continue=true;
                            continue;
                        }
                    }
                    if($continue){
                        continue;
                    }
                    if($combo){
                        $name=$outlet->brands[0]->name_brand.','.$outlet->brands[1]->name_brand;
                    }else{
                        $name=$outlet->brands[0]->name_brand;
                    }
                }
                $outlet_array=$outlet->toArray();
                unset($outlet_array['call']);
                unset($outlet_array['url']);
                unset($outlet_array['brands']);
                unset($outlet_array['id_outlet']);
                $return[$name][]=$outlet_array;
                $count++;
            }
            // if no outlet found
            if(!$count){
                //get name brand
                $brand_name=Brand::select('name_brand')->whereIn('id_brand',$brand)->get()->pluck('name_brand')->toArray();
                //return empty
                $return[implode(',', $brand_name)][]=[
                    'code'=>'',
                    'name'=>'',
                    'address'=>'',
                    'city'=>'',
                    'phone'=>'',
                    'email'=>'',
                    'latitude'=>'',
                    'longitude'=>'',
                    'status_outlet' => '',
                    'status_mitra' => '',
                    'delivery' => ''
                ];
            }

        }
        return response()->json(MyHelper::checkGet($return));
    }

    function import(Request $request)
    {
        $post = $request->json()->all();
        $dataimport = $post['data_import'];
        $data_pin = [];

        if(!empty($dataimport) && count($dataimport)){
            $city = City::get();
            $id_city = array_pluck($city, 'id_city');
            $city_name = array_pluck($city, 'city_name');
            $city_name = array_map('strtolower', $city_name);

            DB::beginTransaction();
            $countImport = 0;
            $failedImport = [];

            foreach ($dataimport as $key => $value) {
                if(
                    empty($value['code']) &&
                    empty($value['name']) &&
                    empty($value['address']) &&
                    empty($value['city']) &&
                    empty($value['phone']) &&
                    empty($value['latitude']) &&
                    empty($value['longitude']) &&
                    empty($value['open_hours']) &&
                    empty($value['close_hours'])
                ){}else{
                    $search = array_search(strtolower($value['city']), $city_name);
                    if(!empty($search) && $key < count($dataimport)){
                        if(!empty($value['open_hours'])){
                            $value['open_hours'] = date('H:i:s', strtotime($value['open_hours']));
                        }
                        if(!empty($value['close_hours'])){
                            $value['close_hours'] = date('H:i:s', strtotime($value['close_hours']));
                        }

                        $value['latitude'] = str_replace(" ","",$value['latitude']);
                        $value['longitude'] = str_replace(" ","",$value['longitude']);

                        if(!empty($value['latitude']) && strpos($value['latitude'], ',') !== false){
                            $failedImport[] = $value['code'].': Invalid latitude please use "." and remove ","';
                        }

                        if(!empty($value['longitude']) && strpos($value['longitude'], ',') !== false){
                            $failedImport[] = $value['code'].': Invalid longitude please use "." and remove ","';
                        }

                        if(empty($value['code'])){
                            do{
                                $value['code'] = MyHelper::createRandomPIN(3);
                                $code = Outlet::where('outlet_code', $value['code'])->first();
                            }while($code != null);
                        }
                        $code = ['outlet_code' => $value['code']];

                        $insert = [
                            'outlet_code' => $value['code']??'',
                            'outlet_name' => $value['name']??'',
                            'outlet_address' => $value['address']??'',
                            'outlet_postal_code' => $value['postal_code']??'',
                            'outlet_phone' => $value['phone']??'',
                            'outlet_email' => $value['email']??'',
                            'outlet_latitude' => $value['latitude']??'',
                            'outlet_longitude' => $value['longitude']??'',
                            'status_franchise' => (isset($value['status_mitra']) && $value['status_mitra'] == 'Mitra' ? 1 : 0),
                            'delivery_order' => (isset($value['delivery']) && $value['delivery'] == 'Active' ? 1 : 0),
                            'deep_link_gojek' => $value['deep_link_gojek']??'',
                            'deep_link_grab' => $value['deep_link_grab']??'',
                            'id_city' => $id_city[$search]??null,
                            'time_zone_utc' => $value['time_zone_utc']??7
                        ];

                        //insert status
                        if(isset($value['outlet_status'])){
                            $insert['outlet_status'] = $value['outlet_status'];
                        }
                        if(!empty($insert['outlet_name'])){
                            $save = Outlet::updateOrCreate($code, $insert);

                            if(empty($save)){
                                DB::rollBack();
                            } else {
                                $outlet = Outlet::where('id_outlet', $save['id_outlet'])->first();
                                if (empty($outlet->outlet_pin)) {
                                    $pin = MyHelper::createRandomPIN(6, 'angka');
                                    $outlet->update(['outlet_pin' => \Hash::make($pin)]);
                                    $data_pin[] = ['id_outlet' => $outlet->id_outlet, 'data' => $pin];

                                    // sent pin to outlet
							        if (isset($outlet['outlet_email'])) {
							        	$variable = $outlet->toArray();
							        	$queue_data[] = [
							        		'pin' 			=> $pin,
							                'date_sent' 	=> date('Y-m-d H:i:s'),
							                'outlet_name' 	=> $outlet['outlet_name'],
							                'outlet_code' 	=> $value['code'],
							        	]+$variable;
							        }
                                }
                                $day = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

                                foreach ($day as $val){
                                    $data = [
                                        'day'       => $val,
                                        'open'      => '10:00:00',
                                        'close'     => '21:30:00',
                                        'is_closed' => 0,
                                        'id_outlet' => $save['id_outlet']
                                    ];
                                    $check = OutletSchedule::where('id_outlet', $save['id_outlet'])->where('day', $val)->select('day')->first();
                                    if(!$check){
                                        $save = OutletSchedule::updateOrCreate(['id_outlet' => $save['id_outlet'], 'day' => $val], $data);
                                        if (!$save) {
                                            DB::rollBack();
                                            return response()->json([
                                                'status'    => 'fail',
                                                'messages'      => [
                                                    'Add shedule failed.'
                                                ]
                                            ]);
                                        }
                                    }
                                }
                                $countImport++;
                            }
                        }else{
                            $failedImport[] = $value['code'].': Outlet Code Not Found';
                        }
                    }else{
                        $failedImport[] = $value['code'].': City '.$value['city'].' not found';
                    }
                }
            }
            MyHelper::updateOutletFile($data_pin);
            if (isset($queue_data)) {
            	SendOutletJob::dispatch($queue_data)->allOnConnection('outletqueue');
            }
            DB::commit();
            SyncronPlasticTypeOutlet::dispatch([])->onQueue('high')->allOnConnection('database');

            if(count($failedImport) > 0){
                return ['status' => 'fail','messages' => [$failedImport]];
            }else{
                return ['status' => 'success', 'message' => $countImport.' data successfully imported'];
            }
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'File is empty.'
                ]
            ]);
        }
    }

    function importBrand(Request $request)
    {
        $post = $request->json()->all();
        $dataimport = $post['data_import'];

        if(!empty($dataimport) && count($dataimport)){
            DB::beginTransaction();
            $countImport = 0;

            $outlets = Outlet::get();
            $id_outlet = array_pluck($outlets, 'id_outlet');
            $outlet_code = array_pluck($outlets, 'outlet_code');
            $outlet_code = array_map('strtolower', $outlet_code);

            $brands = Brand::get();
            $id_brand = array_pluck($brands, 'id_brand');
            $name_brand = array_pluck($brands, 'name_brand');
            $name_brand = array_map('strtolower', $name_brand);

            $countDataImport = count($dataimport);
            for($i=0;$i<$countDataImport;$i++){
                $countDetail = count($dataimport[$i]);
                if(isset($dataimport[$i]['code_outlet'])){
                    $search_outlet = array_search(strtolower($dataimport[$i]['code_outlet']), $outlet_code);
                    if(!empty($search_outlet)){
                        BrandOutlet ::where('id_outlet',$id_outlet[$search_outlet])->delete();

                        foreach ($dataimport[$i] as $key => $val) {
                            if ($key == 'code_outlet') continue;

                            if(strtoupper($val) == 'YES'){
                                $search_brand = array_search(strtolower($key), $name_brand);
                                $insertBrandOutlet = [
                                    'id_brand' => $id_brand[$search_brand]??null,
                                    'id_outlet' => $id_outlet[$search_outlet]??null
                                ];

                                $saveBrandOutlet = BrandOutlet::insert($insertBrandOutlet);

                                if (!$saveBrandOutlet) {
                                    DB::rollBack();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'      => [
                                            'Save brand outlet failed.'
                                        ]
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            if($saveBrandOutlet??false) return ['status' => 'success', 'message' => 'Data successfully imported.'];
            else return ['status' => 'fail','messages' => ['failed to import data']];
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'File is empty.'
                ]
            ]);
        }
    }

    function createAdminOutlet(CreateUserOutlet $request){
        $post = $request->json()->all();

        $outlet = Outlet::where('outlet_code', $post['outlet_code'])->first();
        unset($post['outlet_code']);
        if($outlet){
            $check1 = UserOutlet::where('id_outlet', $outlet->id_outlet)->where('phone', $post['phone'])->first();
            $check2 = UserOutlet::where('id_outlet', $outlet->id_outlet)->where('email', $post['email'])->first();
            if($check1){
                $msg[] = "The phone has already been taken.";
            }
            if($check2){
                $msg[] = "The email has already been taken.";
            }
            if(isset($msg)){
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => $msg
                ]);
            }
            if(isset($post['id_user'])){
                unset($post['id_user']);

            }
            $post['id_outlet'] = $outlet->id_outlet;
            foreach($post['type'] as $value){
                $post[$value] = 1;
            }
            unset($post['type']);
            $save = UserOutlet::create($post);
            return response()->json(MyHelper::checkCreate($save));
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Data outlet not found.'
                ]
            ]);
        }
    }

    function detailAdminOutlet(Request $request){
        $post = $request->json()->all();
        if($post['id_user_outlet']){
            $userOutlet = UserOutlet::find($post['id_user_outlet']);
            return response()->json(MyHelper::checkGet($userOutlet));
        }
    }

    function updateAdminOutlet(UpdateUserOutlet $request){
        $post = $request->json()->all();
            // fix update type just add, not delete current position
            $available_types = ['enquiry','pickup_order','delivery','payment','outlet_apps'];
            foreach($available_types as $value){
                $post[$value] = in_array($value,$post['type'])?1:null;
            }
            unset($post['type']);
            $userOutlet = UserOutlet::where('id_user_outlet', $post['id_user_outlet'])->first();
            $check1 = UserOutlet::whereNotIn('id_user_outlet', [$post['id_user_outlet']])->where('id_outlet', $userOutlet->id_outlet)->where('phone', $post['phone'])->first();
            $check2 = UserOutlet::whereNotIn('id_user_outlet', [$post['id_user_outlet']])->where('id_outlet', $userOutlet->id_outlet)->where('email', $post['email'])->first();
            if($check1){
                $msg[] = "The phone has already been taken.";
            }
            if($check2){
                $msg[] = "The email has already been taken.";
            }
            if(isset($msg)){
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => $msg
                ]);
            }
            $save = $userOutlet->update($post);
            return response()->json(MyHelper::checkUpdate($save));
    }

    function deleteAdminOutlet(Request $request){
        $post = $request->json()->all();
        $delete = UserOutlet::where('id_user_outlet', $post['id_user_outlet'])->delete();
        return response()->json(MyHelper::checkDelete($delete));
    }

    public function scheduleSave(Request $request)
    {
        $post = $request->json()->all();
        DB::beginTransaction();
        $date_time = date('Y-m-d H:i:s');
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();

        foreach ($post['day'] as $key => $value) {
            //get timezone from province
            $city = City::where('id_city', $outlet->id_city)->with('province')->first();
            if(isset($city['province']['time_zone_utc'])){
                $outlet->time_zone_utc = $city['province']['time_zone_utc'];
            }
        	$post['open'][$key] = $this->setOneTimezone($post['open'][$key], $outlet->time_zone_utc);
        	$post['close'][$key] = $this->setOneTimezone($post['close'][$key], $outlet->time_zone_utc);
            $data = [
                'day'       => $value,
                'open'      => $post['open'][$key],
                'close'     => $post['close'][$key],
                'is_closed' => $post['is_closed'][$key],
                'id_outlet' => $post['id_outlet']
            ];
            $old = OutletSchedule::select('id_outlet_schedule','id_outlet','day','open','close','is_closed')->where(['id_outlet' => $post['id_outlet'], 'day' => $value])->first();
            $old_data = $old?$old->toArray():[];
            if($old){
                $save = $old->update($data);
                $new = $old;
                if (!$save) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail']);
                }
            }else{
                $new = OutletSchedule::create([
                    'id_outlet' => $post['id_outlet'],
                    'day' => $value['day']
                ]+$value);
                if (!$new) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail']);
                }
            }
            $new_data = $new->toArray();
            unset($new_data['created_at']);
            unset($new_data['updated_at']);
            if(array_diff($new_data,$old_data)){
                $create = OutletScheduleUpdate::create([
                    'id_outlet' => $post['id_outlet'],
                    'id_outlet_schedule' => $new_data['id_outlet_schedule'],
                    'id_user' => $request->user()->id,
                    'id_outlet_app_otp' => null,
                    'user_type' => 'users',
                    'date_time' => $date_time,
                    'old_data' => $old_data?json_encode($old_data):null,
                    'new_data' => json_encode($new_data)
                ]);
            }
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }

    public function filterList($model,$rule,$operator='and'){
        $newRule=[];
        $where=$operator=='and'?'where':'orWhere';
        foreach ($rule as $var) {
            $var1=['operator'=>$var['operator']??'=','parameter'=>$var['parameter']??null];
            if($var1['operator']=='like'){
                $var1['parameter']='%'.$var1['parameter'].'%';
            }
            $newRule[$var['subject']][]=$var1;
        }
        if($newRule['all_empty']??false){
                $model->$where(function($query){
                    $all=['id_city','outlet_latitude','outlet_longitude'];
                    foreach ($all as $field) {
                        $query->where(function($query) use($field){
                            $query->where($field,'=','')->orWhereNull($field);
                        });
                    }
                });
        }
        if($newRule['empty']??false){
            $all=array_column($newRule['empty'],'parameter');
            foreach ($all as $field) {
                $model->$where(function($query) use ($field){
                    $query->where($field,'')->orWhereNull($field);
                });
            }
        }
        if($rules=$newRule['id_brand']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('brands',function($query) use ($rul){
                    $query->where('brands.id_brand',$rul['operator'],$rul['parameter']);
                });
            }
        }
        $inner=['outlet_code'];
        foreach ($inner as $col_name) {
            if($rules=$newRule[$col_name]??false){
                foreach ($rules as $rul) {
                    $model->$where('outlets.'.$col_name,$rul['operator'],$rul['parameter']);
                }
            }
        }
    }

    public function batchUpdate(Request $request){
        $posts=$request->json()->all();
        DB::beginTransaction();
        $save=1;
        foreach ($posts['outlets']??[] as $id_outlet=>$data) {
            $post = $this->checkInputOutlet($data);
            $save_t = Outlet::where('id_outlet', $id_outlet)->update($post);
            // return Outlet::where('id_outlet', $request->json('id_outlet'))->first();
            if(!$save_t){
                $save=0;
                break;
            }
        }
        if($save){
            DB::commit();
            return ['status'=>'success'];
        }
        DB::rollBack();
        return ['status'=>'fail'];
    }

    public function ajaxHandler(Request $request){
        $post=$request->except('_token');
        $q=(new Outlet)->newQuery();
        if($post['select']??false){
            $q->select($post['select']);
        }
        if($condition=$post['condition']??false){
            $this->filterList($q,$condition['rules']??'',$condition['operator']??'and');
        }
        return MyHelper::checkGet($q->get());
    }

    public function detailTransaction(Request $request) {
        $outlet = Outlet::with(['today','city.province','brands'=>function($query){
                    $query->where([['brand_active',1],['brand_visibility',1]]);
                    $query->select('brands.id_brand','name_brand');
                }])->select('id_outlet','outlet_code','outlet_name','outlet_address','outlet_latitude','outlet_longitude','outlet_phone','outlet_status','delivery_order','time_zone_utc', 'id_city')->find($request->json('id_outlet'));
        if(!$outlet){
            return MyHelper::checkGet([]);
        }
        $outlet = $outlet->toArray();
        $processing = '0';
        $settingTime = Setting::where('key', 'processing_time')->first();
        if($settingTime && $settingTime->value){
            $processing = $settingTime->value;
        }
        
        $check_holiday = $this->checkOutletHoliday();
        
        if ($check_holiday['status'] && in_array($outlet['id_outlet'], $check_holiday['list_outlet'])) {
        	$outlet['today']['is_closed'] = 1;
        }
        //get timezone from province
        if(isset($outlet['city']['province']['time_zone_utc'])){
            $outlet['time_zone_utc'] = $outlet['city']['province']['time_zone_utc'];
        }
        $outlet['today'] = $this->getTimezone($outlet['today'], $outlet['time_zone_utc']);
        $outlet = $this->setAvailableOutlet($outlet, $processing);

        $outlet['status'] = $outlet['today']['status'];
        // $outlet['status'] = $this->checkOutletStatus($outlet);
        return MyHelper::checkGet($outlet);
    }

    public function listOutletOrderNow(OutletListOrderNow $request){
        $post = $request->json()->all();
        $user = $request->user();

        try{
            $title = Setting::where('key', 'order_now_title')->first()->value;
            $subTitleSuccess = Setting::where('key', 'order_now_sub_title_success')->first()->value;
            $subTitleFail = Setting::where('key', 'order_now_sub_title_fail')->first()->value;

            $day = [
                'Mon' => 'Senin',
                'Tue' => 'Selasa',
                'Wed' => 'Rabu',
                'Thu' => 'Kamis',
                'Fri' => 'Jumat',
                'Sat' => 'Sabtu',
                'Sun' => 'Minggu'
            ];

            $data = [
                'current_date' => date('Y-m-d'),
                'current_day' => $day[date('D')],
                'current_hour' => date('H:i:s')
            ];

            $outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
                ->selectRaw('outlets.id_outlet, outlets.outlet_name, outlets.outlet_code,outlets.outlet_status,outlets.outlet_address,outlets.id_city, outlets.outlet_latitude, outlets.outlet_longitude,
                    (111.111 * DEGREES(ACOS(LEAST(1.0, COS(RADIANS(outlets.outlet_latitude))
                         * COS(RADIANS('.$post['latitude'].'))
                         * COS(RADIANS(outlets.outlet_longitude - '.$post['longitude'].'))
                         + SIN(RADIANS(outlets.outlet_latitude))
                         * SIN(RADIANS('.$post['latitude'].')))))) AS distance_in_km' )
                ->with(['user_outlets','city','today', 'outlet_schedules', 'brands'])
                ->where('outlets.outlet_status', 'Active')
                ->whereNotNull('outlets.outlet_latitude')
                ->whereNotNull('outlets.outlet_longitude')
                ->whereHas('brands',function($query){
                    $query->where('brand_active','1');
                })
                ->whereIn('id_outlet',function($query) use ($data){
                    $query->select('id_outlet')
                        ->from('outlet_schedules')
                        ->where('day', $data['current_day'])
                        ->where('is_closed', 0)
                        ->whereRaw('TIME_TO_SEC("'.$data['current_hour'].'") >= TIME_TO_SEC(open) AND TIME_TO_SEC("'.$data['current_hour'].'") <= TIME_TO_SEC(close)');
                })->whereNotIn('id_outlet',function($query) use ($data){
                    $query->select('id_outlet')
                        ->from('outlet_holidays')
                        ->join('date_holidays', 'date_holidays.id_holiday', 'outlet_holidays.id_holiday')
                        ->where('date', $data['current_date']);
                })
                ->orderBy('distance_in_km', 'asc')
                ->limit(5)
                ->get()->toArray();

            if(count($outlet) > 0){
                $loopdata=&$outlet;
                $loopdata = array_map(function($var) use ($post){
                    $var['url']=config('url.api_url').'api/outlet/webview/'.$var['id_outlet'];
                    if(($post['latitude']??false)&&($post['longitude']??false)){
                        $var['distance']=number_format((float)$this->distance($post['latitude'], $post['longitude'], $var['outlet_latitude'], $var['outlet_longitude'], "K"), 2, '.', '').' km';
                    }
                    return $var;
                }, $loopdata);

                $result = [
                    'status' => 'success',
                    'messages' => [],
                    'result' => [
                        'title' => $title,
                        'sub_title' => $subTitleSuccess,
                        'data' => $outlet
                    ]
                ];
            }else{
                $result = [
                    'status' => 'fail',
                    'messages' => [$subTitleFail],
                    'result' => [
                        'title' => $title,
                        'sub_title' => $subTitleFail,
                        'data' => null
                    ]
                ];
            }

        }catch (Exception $e) {
            $result = [
                'status' => 'fail',
                'messages' => ['something went wrong'],
                'result' => [
                    'data' => null
                ]
            ];
        }
        return response()->json($result);
    }

    public function listMaxOrder(Request $request) {
        $post = $request->json()->all();
        if($post['id_outlet']??false){
            $col = 'id_outlet';
            $val = $post['id_outlet'];
        }else{
            $col = 'outlet_code';
            $val = $post['outlet_code'];
        }
        $outlet = Outlet::select('id_outlet','advance_order','max_order')->where($col,$val)->first();
        if(!$outlet){
            return ['status'=>'fail','messages'=>['Outlet not found']];
        }
        $return['outlet'] = $outlet;
        $products = Product::leftJoin('product_detail',function($join) use ($outlet) {
            $join->on('product_detail.id_product','=','products.id_product')
                ->where('product_detail.id_outlet',$outlet->id_outlet);
        })->select('products.id_product','product_code','product_name','product_detail.max_order');

        if($post['rule']??false){
            $filter = $this->filterListProduct($products,$post['rule'],$post['operator']??'and');
        }else{
            $filter = [];
        }

        if($request->page){
            $return['products'] = $products->paginate(10);
        }else{
            $return['products'] = $products->get();
        }
        return MyHelper::checkGet($return)+$filter;
    }
    public function updateMaxOrder(Request $request) {
        $post = $request->json()->all();
        if($post['id_outlet']??false){
            $col = 'id_outlet';
            $val = $post['id_outlet'];
        }else{
            $col = 'outlet_code';
            $val = $post['outlet_code'];
        }
        $outlet = Outlet::where($col,$val)->first();
        if(!($post['advance_order']??false)){
            $post['advance_order'] = 0;
        }
        if(!$outlet){
            return ['status'=>'fail','messages'=>['Outlet not found']];
        };
        DB::beginTransaction();
        $update = Outlet::where($col,$val)->update([
            'advance_order'=>$post['advance_order'],
            'max_order'=>$post['max_order']
        ]);
        if(!$update){
            DB::rollBack();
            return MyHelper::checkUpdate($update);
        }
        foreach ($post['products']??[] as $id_product => $max_order) {
            $up = ProductDetail::updateOrCreate(['id_product'=>$id_product,'id_outlet'=>$outlet->id_outlet], [
                'max_order' => $max_order
            ]);
            if(!$up){
                DB::rollBack();
                return [
                    'status' => 'fail',
                    'messages' => 'Failed update per product max order'
                ];
            }
        }
        DB::commit();
        return MyHelper::checkUpdate($update);
    }
    public function filterListProduct($query,$rules,$operator='and'){
        $newRule=[];
        $total = $query->count();
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']??''];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['product_code','product_name','max_order'];
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query->$where($subject,$rule[0],$rule[1]);
                }
            }
        }
        $filtered = $query->count();
        return ['total'=>$total,'filtered'=>$filtered];
    }

    public function getAllCodeOutlet(Request $request){
        $post = $request->json()->all();
        $outlet = Outlet::select('outlet_code', 'id_outlet', 'outlet_name')->with(['brands','delivery_outlet']);

        if(isset($post['page'])){
            $outlet = $outlet->paginate(30);
        }else{
            $outlet = $outlet->get()->toArray();
        }
        return response()->json(MyHelper::checkGet($outlet));
    }

    public function applyPromo($promo_post, $data_outlet, &$promo_error)
    {
    	// check promo
    	$post 	= $promo_post;
    	$outlet = $data_outlet;

    	// give all product flag is_promo = 0
        foreach ($outlet as $key => $value) {
			$outlet[$key]['is_promo'] = 0;
		}

		$promo_error = null;
		if ((!empty($post['promo_code']) && empty($post['id_deals_user']) && empty($post['id_subscription_user'])) 
			|| (empty($post['promo_code']) && !empty($post['id_deals_user']) && empty($post['id_subscription_user'])) 
			|| (empty($post['promo_code']) && empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
		) {
        // if (isset($post['promo_code'])) {
        	if (!empty($post['promo_code']))
        	{
        		$code = app($this->promo_campaign)->checkPromoCode($post['promo_code'], 1);
        		$source = 'promo_campaign';
        	}
        	elseif (!empty($post['id_deals_user']))
        	{
        		$code = app($this->promo_campaign)->checkVoucher($post['id_deals_user'], 1);
        		$source = 'deals';
        	}
        	elseif (!empty($post['id_subscription_user']))
        	{
        		$code = app($this->subscription_use)->checkSubscription($post['id_subscription_user'], 1);
        		$source = 'subscription';
        	}
	        if(!$code){
	        	$promo_error = 'Promo not valid';
	        	return false;
	        }else{

	        	if ( ($code['promo_campaign']['date_end']??$code['voucher_expired_at']??$code['subscription_expired_at']) < date('Y-m-d H:i:s') ) {
	        		$promo_error = 'Promo is ended';
	        		return false;
	        	}

	        	if ($source == 'promo_campaign') {
	        		$brands 			= $code->promo_campaign->promo_campaign_brands()->pluck('id_brand')->toArray();
	        		$all_outlet 		= $code['promo_campaign']['is_all_outlet']??0;
	        		$promo_outlet 		= $code['promo_campaign']['promo_campaign_outlets']??[];
	        		$promo_outlet_group = $code['promo_campaign']['outlet_groups']??[];
	        		$id_brand 			= $code['promo_campaign']['id_brand']??null;
	        		$brand_rule			= $code['promo_campaign']['brand_rule']??'and';
	        		$promo_type 		= $code['promo_campaign']['promo_type'];
	        	}elseif($source == 'deals'){
	        		$brands 			= $code->dealVoucher->deals->deals_brands()->pluck('id_brand')->toArray();
	        		$all_outlet 		= $code['dealVoucher']['deals']['is_all_outlet']??0;
	        		$promo_outlet 		= $code['dealVoucher']['deals']['outlets_active']??[];
	        		$promo_outlet_group = $code['dealVoucher']['deals']['outlet_groups']??[];
	        		$id_brand 			= $code['dealVoucher']['deals']['id_brand']??null;
	        		$brand_rule			= $code['dealVoucher']['deals']['brand_rule']??'and';
	        		$promo_type			= $code['dealVoucher']['deals']['promo_type'];
	        	}elseif($source == 'subscription'){
	        		$brands 			= $code->subscription_user->subscription->subscription_brands->pluck('id_brand')->toArray();
	        		$all_outlet 		= $code['subscription_user']['subscription']['is_all_outlet']??0;
	        		$promo_outlet 		= $code['subscription_user']['subscription']['outlets_active']??[];
	        		$promo_outlet_group = $code['subscription_user']['subscription']['outlet_groups']??[];
	        		$id_brand 			= $code['subscription_user']['subscription']['id_brand']??null;
	        		$brand_rule			= $code['subscription_user']['subscription']['brand_rule']??'and';
	        		$promo_type			= $code['subscription_user']['subscription']['subscription_discount_type'];
	        	}
	        	// if valid give flag is_promo = 1
	        	$code_obj = $code;
	        	$code = $code->toArray();
	        	$pct = new PromoCampaignTools;

				foreach ($outlet as $key => $value) {
					if ($promo_type == 'discount_delivery' || $promo_type == 'Discount delivery') {
						if (empty($value['delivery_order'])) {
							continue;
						}
					}

					if (isset($id_brand)) {
						$check_outlet = $pct->checkOutletRule($value['id_outlet'], $all_outlet, $promo_outlet, $id_brand);
					}else{
						$check_outlet = $pct->checkOutletBrandRule($value['id_outlet'], $all_outlet, $promo_outlet, $brands, $brand_rule, $promo_outlet_group);
					}
					
					if ($check_outlet) {
						$outlet[$key]['is_promo'] = 1;
					}
				}
	        }
	    }elseif (
        	(!empty($post['promo_code']) && !empty($post['id_deals_user'])) ||
        	(!empty($post['id_subscription_user']) && !empty($post['id_deals_user'])) ||
        	(!empty($post['promo_code']) && !empty($post['id_subscription_user']))
        ) {
        	$promo_error = 'Can only use Subscription, Promo Code, or Voucher';
        }

        return $outlet;
        // end check promo
    }
    /**
     * Get list different outlet
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function differentPrice(Request $request) {
        $data = Outlet::select('id_outlet','outlet_code','outlet_name','outlet_different_price');
        if($keyword = $request->json('keyword')){
            $data->where('outlet_code','like',"%$keyword%")
                ->orWhere('outlet_name','like',"%$keyword%");
        }
        if($request->page){
            return MyHelper::checkGet($data->paginate(20));
        }else{
            return MyHelper::checkGet($data->get());
        }
    }
    public function updateDifferentPrice(Request $request) {
        $post = $request->json()->all();
        $update = Outlet::whereIn('id_outlet',$post['id_outlet']??'')->update(['outlet_different_price'=>$post['status']??0]);
        if($update){
            return [
                'status'=>'success',
                'result'=>$post['status']??0
            ];
        }
        return ['status'=>'fail'];
    }

    function listOutletSimple(Request $request)
    {
        $outlet = Outlet::select('id_outlet', 'outlet_code', 'outlet_name')->where('outlet_status', 'Active')->orderBy('outlet_name')->get()->toArray();

        foreach ($outlet as $key => $value) {
            unset($outlet[$key]['call']);
            unset($outlet[$key]['url']);
            $brands = BrandOutlet::where('id_outlet', $value['id_outlet'])->select('id_brand')->get();
            if($brands){
                $brands = $brands->pluck('id_brand');
            }
            $outlet[$key]['id_brands'] = $brands;
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

    function listUserFranchise(Request $request){
        $post = $request->json()->all();
        $list = UserFranchise::orderBy('created_at');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){

                        if($row['subject'] == 'phone'){
                            if($row['operator'] == '='){
                                $list->where('phone', $row['parameter']);
                            }else{
                                $list->where('phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'email'){
                            if($row['operator'] == '='){
                                $list->where('email', $row['parameter']);
                            }else{
                                $list->where('email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'user_status'){
                            $list->where('user_franchise_type', $row['operator']);
                        }
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'phone'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('phone', $row['parameter']);
                                }else{
                                    $subquery->orWhere('phone', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('email', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'user_status'){
                                $subquery->orWhere('user_franchise_type', $row['operator']);
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(25);
        return response()->json(MyHelper::checkGet($list));
    }

    function detailUserFranchise(Request $request){
        $post = $request->json()->all();
        $userFranchise = UserFranchise::where('phone', $post['phone'])->first();

        $listOutlet = [];
        if($userFranchise){
            $listOutlet = UserFranchiseOultet::join('outlets', 'user_franchise_outlet.id_outlet', 'outlets.id_outlet')
                ->where('id_user_franchise', $userFranchise['id_user_franchise'])->paginate(20);
        }

        $result = [
            'status' => 'success',
            'data_user' => $userFranchise,
            'list_outlet' => $listOutlet
        ];
        return response()->json($result);
    }

    function setPasswordDefaultUserFranchise(Request $request){
        $post = $request->json()->all();

        if(isset($post['phone']) && !empty($post['phone'])){
            if($post['password'] == $post['re_type_password']){
                $data = [
                    'password' => bcrypt($post['password']),
                    'password_default_plain_text' => MyHelper::encrypt2019($post['password'])
                ];

                $update = UserFranchise::where('phone', $post['phone'])->update($data);

                if($update){
                    return response()->json(['status' => 'success']);
                }else{
                    return response()->json(['status' => 'fail', 'message' => 'Failed update password']);
                }
            }else{
                return response()->json(['status' => 'fail', 'message' => 'Password does not match']);
            }
        }else{
            return response()->json(['status' => 'fail' , 'messages' => ['Incompleted data']]);
        }
    }

    public function sendNotifIncompleteOutlet(...$id_outlets)
    {
        if(!$id_outlets){
            // find incomplete outlet
            $outlets = Outlet::where(function($q) {
                $q->whereNull('outlet_latitude')
                    ->orWhereNull('outlet_longitude')
                    ->orWhereNull('outlet_phone')
                    ->orWhereNull('outlet_address');
            })->get();
        }else{
            $outlets = Outlet::whereIn('id_outlet',$id_outlets)->where('notify_admin',0)->get();
        }
        $phone = User::select('phone')->pluck('phone')->first();
        $complete = [0,0];
        foreach ($outlets as $outlet) {
            $variable = [];
            foreach ($outlet->toArray() as $key => $value) {
                $variable[str_replace('outlet_','',$key)] = $value;
            }
            $incomplete = [];
            if(!$outlet['outlet_latitude']){
                $incomplete[] = 'Outlet Latitude';
            }
            if(!$outlet['outlet_longitude']){
                $incomplete[] = 'Outlet Longitude';
            }
            if(!$outlet['outlet_phone']){
                $incomplete[] = 'Outlet Phone';
            }
            if(!$outlet['outlet_address']){
                $incomplete[] = 'Outlet Address';
            }
            $variable['incomplete_data'] = implode(', ', $incomplete);
            $send = app($this->autocrm)->SendAutoCRM('Incomplete Outlet Data', $phone, $variable);
            $complete[1]++;
            if(!$send){
                \Log::warning('Failed send forward email Incomplete Outlet Data for outlet '.$outlet->code.' - '.$outlet->name);
            }else{
                $complete[0]++;
            }
        }
        return ['status'=>'success','result' => ['incomplete'=>$complete[1],'send'=>$complete[0]]];
    }
    public function resetNotify()
    {
        $log = MyHelper::logCron('Reset Notify Flag');
        try {
            Outlet::where('notify_admin',1)->update(['notify_admin'=>0]);
            $log->success();
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function exportPin(Request $request)
    {
        $pin = MyHelper::getOutletFile();
        $outlets = Outlet::select('id_outlet', 'outlet_code', 'outlet_name')->whereNotNull('outlet_pin')->whereIn('id_outlet',array_keys($pin))->get()->toArray();
        foreach ($outlets as $key => &$outlet) {
            $outlet['pin'] = $pin[$outlet['id_outlet']];
        }
        if (!$outlets) {
            $outlets[] = [
                'outlet_code' => null,
                'outlet_name' => 'No saved Outlet PINs found',
                'pin' => null
            ];
        }
        return MyHelper::checkGet($outlets);
    }

    function reorderDays($days, $now)
    {	
    	$temp_days 	= [];
    	$new_days	= [];
		foreach ($days as $key => $value) {
			$temp_days[] = $value;
			if ($value['day'] == $now) {
				$new_days = array_slice($days, $key+1);
				break;
			}
		}
		if (!empty($new_days)) {
			$days = array_merge($new_days, $temp_days);
		}

		return $days;
    }

    function checkOutletHoliday()
    {
    	$result = [
			'status' 		=> false,
			'list_outlet' 	=> []
		];

    	$holiday 	= Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
					->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
	                ->select('outlet_holidays.id_outlet', 'holidays.id_holiday', 'holidays.yearly', 'date_holidays.date')
	                ->whereDay('date_holidays.date', date('d'))
	                ->whereMonth('date_holidays.date', date('m'))
	                ->get()
	                ->toArray();

		if ($holiday) {
			$list_outlet = array_column($holiday, 'id_outlet');
            foreach($holiday as $i => $holi){
                if($holi['yearly'] == '0'){
                    if($holi['date'] == date('Y-m-d')){
                        $result = [
							'status' 		=> true,
							'list_outlet' 	=> $list_outlet
						];
                    }
                }
                else
                {
                    $result = [
						'status' 		=> true,
						'list_outlet' 	=> $list_outlet
					];
                }
            }
		}

		return $result;
    }

    function sendPin(Request $request)
    {
		$pin = MyHelper::getOutletFile();
        $outlets = Outlet::select('id_outlet', 'outlet_code', 'outlet_name', 'outlet_email')->whereNotNull('outlet_pin')->whereIn('id_outlet',array_keys($pin))->get()->toArray();
        $count = 0;

        foreach ($outlets as $key => &$outlet) {
            $outlet['pin'] = $pin[$outlet['id_outlet']];
	        if (isset($outlet['outlet_email'])) {
	        	$queue_data[] = [
	        		'pin' 			=> $outlet['pin'],
	                'date_sent' 	=> date('Y-m-d H:i:s'),
	                'outlet_name' 	=> $outlet['outlet_name'],
	                'outlet_code' 	=> $outlet['outlet_code'],
	        	]+$outlet;

	        	$count++;
	        }
        }

        if (isset($queue_data)) {
        	SendOutletJob::dispatch($queue_data)->allOnConnection('outletqueue');
        }

        return MyHelper::checkGet($count.' pin has been sent');
    }

    public function restoreSchedule(Request $request)
    {
        if ($request->time_verify < time() - 300) {
            return response()->json([
                'status' => 'fail',
                'messages' => [
                    'time_verify should after last 5 minutes. Current time '.time().'. '.url('/'),
                ]
            ], 403);
        }
        // 'open'      => '10:00:00',
        // 'close'     => '21:30:00',

        // get outlets with default schedule
        $outlets = Outlet::select('outlets.id_outlet', 'outlets.outlet_code')->join('outlet_schedules', 'outlet_schedules.id_outlet', '=', 'outlets.id_outlet')
            ->where([
                'outlet_schedules.open' => '10:00:00',
                'outlet_schedules.close' => '21:30:00',
            ])->groupBy('outlets.id_outlet');
        $updated = [];
        foreach ($outlets->cursor() as $outlet) {
            $updated[$outlet->outlet_code] = [];

            $schedules = OutletScheduleUpdate::where('id_outlet', $outlet->id_outlet)->orderBy('id_outlet_schedule_update', 'desc')->take(7)->get();

            foreach ($schedules as $schedule) {
                $to_restore = json_decode($schedule->new_data, true);
                $r = OutletSchedule::updateOrCreate([
                    'id_outlet' => $to_restore['id_outlet'], 
                    'day' => $to_restore['day']
                ], [
                    'open' => $to_restore['open'],
                    'close' => $to_restore['close'],
                    'is_closed' => $to_restore['is_closed'],
                ]);
                if ($request->show_result) {
                    $updated[$outlet->outlet_code][] = $r;
                }
            }
        }
        return MyHelper::checkGet(['updated' => $updated]);
    }

    public function getTimezone($data, $time_zone_utc){
        $data['time_zone_id'] = 'WIB';
        $default_time_zone_utc = 7;
        $time_diff = $time_zone_utc - $default_time_zone_utc;

        $data['open'] = date('H:i', strtotime('+'.$time_diff.' hour',strtotime($data['open']??'00:00')));
        $data['close'] = date('H:i', strtotime('+'.$time_diff.' hour', strtotime($data['close']??'00:00')));

        switch ($time_zone_utc) {
            case 8:
                $data['time_zone_id'] = 'WITA';
            break;
            case 9:
                $data['time_zone_id'] = 'WIT';
            break;
        }
        return $data;
    }

    function setOneTimezone($time, $time_zone_utc)
    {
        $default_time_zone_utc = 7;
        $time_diff = $time_zone_utc - $default_time_zone_utc;

        $data = date('H:i', strtotime('-'.$time_diff.' hour',strtotime($time)));

        return $data;
    }

    function getOneTimezone($time, $time_zone_utc)
    {
        $default_time_zone_utc = 7;
        $time_diff = $time_zone_utc - $default_time_zone_utc;

        $data = date('H:i', strtotime('+'.$time_diff.' hour',strtotime($time)));

        return $data;
    }

    function importDelivery(Request $request)
    {
        $post = $request->json()->all();
        $dataimport = $post['data_import'][0]??[];

        if(!empty($dataimport) && count($dataimport)){
            $updateData = 0;
            $failed = [];
            foreach ($dataimport as $data){
                $checkCode = Outlet::where('outlet_code', $data['outlet_code'])->first();
                if(empty($checkCode)){
                    continue;
                }

                $id_outlet = $checkCode['id_outlet'];
                DeliveryOutlet::where('id_outlet', $id_outlet)->delete();
                $insertDelivOutlet = [];
                $tmpCode = [];
                foreach ($data as $key => $val) {
                    if ($key == 'outlet_name' || $key == 'outlet_code') continue;

                    $code = str_replace('showhide', "", $key);
                    $code = str_replace('enabledisable', "", $code);

                    if(in_array($code, $tmpCode)){
                        continue;
                    }

                    if(strpos($key, 'showhide') !== false){
                        $showStatus = $val;
                        $availableStatus = $data[$code.'enabledisable']??0;
                    }elseif (strpos($key, 'enabledisable') !== false){
                        $showStatus = $data[$code.'showhide']??0;
                        $availableStatus = $val;
                    }

                    $insertDelivOutlet[] = [
                            'id_outlet' => $id_outlet,
                            'code' => $code,
                            'available_status' => (strtolower($availableStatus) == 'enable' ? 1:0),
                            'show_status' => (strtolower($showStatus) == 'show' ? 1:0),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    $tmpCode[] = $code;
                }

                if(!empty($insertDelivOutlet)){
                    $saveDelivOutlet = DeliveryOutlet::insert($insertDelivOutlet);

                    if (!$saveDelivOutlet) {
                        $failed[] = $data['outlet_code'];
                    }
                }
                $updateData++;
            }

            if($saveDelivOutlet??false) return ['status' => 'success', 'message' => $updateData .' data outlet updated'];
            else return ['status' => 'fail','messages' => ['Failed upadate data outlet : '.implode(',',$failed)]];
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'File is empty.'
                ]
            ]);
        }
    }

    function deliveryOutletAjax(Request $request){
        $post = $request->json()->all();

        if(isset($post['start'])){
            $start = $post['start'];
            $length = $post['length'];
        }

        if(!empty($post['id_outlet_group_filter'])){
            $get_id_outlet = app($this->outlet_group_filter)->outletGroupFilter($post['id_outlet_group_filter']);
            $id_outlet = array_column($get_id_outlet, 'id_outlet');
        }

        $outlet = Outlet::leftJoin('cities', 'cities.id_city', 'outlets.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->select(DB::raw('CONCAT(outlet_code," - ", outlet_name) as "0"'), 'id_outlet as 1', 'id_outlet as 2', 'id_outlet');

        if(isset($post["search"]["value"]) && !empty($post["search"]["value"])){
            $key = $post["search"]["value"];
            $outlet->where(function ($q) use ($key){
                $q->orWhere('outlets.outlet_code', 'like', '%'.$key.'%');
                $q->orWhere('outlets.outlet_name', 'like', '%'.$key.'%');
            });
        }

        if($post['filter_type'] == 'outlet_group' && isset($id_outlet)){
            $outlet = $outlet->whereIn('id_outlet', $id_outlet);
        }elseif($post['filter_type'] == 'conditions' && isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'id_city'){
                            $outlet->where('outlets.id_city', $row['operator']);
                        }

                        if($row['subject'] == 'id_province'){
                            $outlet->where('id_province', $row['operator']);
                        }
                    }
                }
            }else{
                $outlet->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'id_city'){
                                $subquery->orWhere('outlets.id_city', $row['operator']);
                            }

                            if($row['subject'] == 'id_province'){
                                $subquery->orWhere('id_province', $row['operator']);
                            }
                        }
                    }
                });
            }
        }

        $total = $outlet->count();
        $data = $outlet->skip($start)->take($length)->with('delivery_outlet')->get()->toArray();

        $result = [
            'status' => 'success',
            'result' => $data,
            'total' => $total
        ];

        return response()->json($result);
    }

    function deliveryOutletByCode(Request $request){
        $post = $request->json()->all();
        $data = DeliveryOutlet::where('code', $post['code'])->get()->toArray();
        return response()->json(MyHelper::checkGet($data));
    }

    public function deliveryOutletUpdate(Request $request){
        $post = $request->json()->all();
        DeliveryOutlet::where('code', $post['code'])->delete();
        $outletAvailable = Outlet::whereNotIn('id_outlet', $post['id_outlet_not_available'])->pluck('id_outlet')->toArray();
        $outletNotAvailable = $post['id_outlet_not_available'];
        $outletHide = $post['id_outlet_hide'];

        $insert = [];
        foreach ($outletNotAvailable as $o){
            $show = 1;
            $check = array_search($o, $outletHide);
            if($check !== false){
                $show = 0;
            }
            $insert[] = [
                'id_outlet' => (int)$o,
                'code' => $post['code'],
                'available_status' => 0,
                'show_status' => $show,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        foreach ($outletAvailable as $o){
            $show = 1;
            $check = array_search($o, $outletHide);
            if($check !== false){
                $show = 0;
            }

            $insert[] = [
                'id_outlet' => (int)$o,
                'code' => $post['code'],
                'available_status' => 1,
                'show_status' => $show,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if(!empty($insert)){
            DeliveryOutlet::insert($insert);
        }

        return response()->json(['status' => 'success']);
    }

    function deliveryOutletAllUpdate(Request $request){
        $post = $request->json()->all();
        if($post['filter_type'] == 'outlet_group' && !empty($post['id_outlet_group_filter'])){
            $get_id_outlet = app($this->outlet_group_filter)->outletGroupFilter($post['id_outlet_group_filter']);
            $id_outlet = array_column($get_id_outlet, 'id_outlet');
        }elseif($post['filter_type'] == 'conditions' && isset($post['conditions']) && !empty($post['conditions'])){
            $outlet = Outlet::leftJoin('cities', 'cities.id_city', 'outlets.id_city')
                ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province');

            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'id_city'){
                            $outlet->where('outlets.id_city', $row['operator']);
                        }

                        if($row['subject'] == 'id_province'){
                            $outlet->where('id_province', $row['operator']);
                        }
                    }
                }
            }else{
                $outlet->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'id_city'){
                                $subquery->orWhere('outlets.id_city', $row['operator']);
                            }

                            if($row['subject'] == 'id_province'){
                                $subquery->orWhere('id_province', $row['operator']);
                            }
                        }
                    }
                });
            }

            $id_outlet = $outlet->pluck('id_outlet')->toArray();
        }else{
            $id_outlet = Outlet::pluck('id_outlet')->toArray();
        }

        if(empty($id_outlet)){
            return response()->json(['status' => 'fail', 'message' => 'ID outlet is empty']);
        }
        DeliveryOutlet::where('code', $post['code'])->whereIn('id_outlet', $id_outlet)->delete();

        $showStatus = $post['show_status_all']??0;
        $availableStatus = $post['available_status_all']??0;

        $insert = [];

        foreach ($id_outlet as $id){
            $insert[] = [
                'id_outlet' => $id,
                'code' => $post['code'],
                'available_status' => $availableStatus,
                'show_status' => $showStatus,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if(!empty($insert)){
            DeliveryOutlet::insert($insert);
        }

        return response()->json(['status' => 'success']);
    }

    public function listDeliveryWithCountOutlet(){
        $setting  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
        $countOutlet = Outlet::count();
        $delivery = [];

        foreach ($setting as $value) {
            if($value['show_status'] == 1){
                $countOff = DeliveryOutlet::where('code', $value['code'])->where('available_status', 0)->count();
                $value['count_outlet_off'] = $countOff;
                $value['count_outlet_on'] = $countOutlet-$countOff;
                $delivery[] = $value;
            }
        }

        usort($delivery, function($a, $b) {
            return $a['position'] - $b['position'];
        });

        return response()->json(MyHelper::checkGet($delivery));
    }

    public function detailOutletMerchant(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_outlet'])){
            $detail = Outlet::where('id_outlet', $post['id_outlet'])->first();

            if(empty($detail)){
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }

            $idMerchant = Merchant::where('id_outlet', $detail['id_outlet'])->first()['id_merchant']??null;
            $detail['id_merchant'] = $idMerchant;
            $bestProduct = app($this->product)->listProductMerchantBestSeller($detail);
            if(!empty($bestProduct)){
                $detail['id_best'] = array_column($bestProduct, 'id_product');
            }
            $newestProduct = app($this->product)->listProductMerchantNewest($detail);

            $result = [
                'is_closed' => (empty($detail['outlet_is_closed']) ?false:true),
                'id_outlet' => $detail['id_outlet'],
                'outlet_name' => $detail['outlet_name'],
                'outlet_description' => $detail['outlet_description'],
                'outlet_image_cover' => $detail['url_outlet_image_cover'],
                'product_newest' => $newestProduct,
                'product_best_seller' => $bestProduct
             ];

            return response()->json(MyHelper::checkGet($result));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function outletMerchantList(Request $request){
        $post = $request->json()->all();

        $list = Outlet::join('merchants', 'merchants.id_outlet', 'outlets.id_outlet')
            ->where('outlet_status', 'Active')
            ->where('outlet_is_closed', 0)
            ->where('merchant_status', 'Active')
            ->select('outlets.id_outlet', 'id_merchant', 'outlet_name');

        if(!empty($post['search_key'])){
            $list = $list->where('outlet_name', 'like', '%'.$post['search_key'].'%');
        }

        if(!empty($post['pagination'])){
            $list = $list->paginate($post['pagination_total_row']??10)->toArray();

            foreach ($list['data'] as $key=>$dt){
                $list['data'][$key]['outlet_name'] = $dt['outlet_name'];
                $list['data'][$key]['outlet_image_logo_landscape'] = $dt['url_outlet_image_logo_landscape'];
                unset($list['data'][$key]['url_outlet_image_logo_landscape']);
                unset($list['data'][$key]['url_outlet_image_logo_portrait']);
                unset($list['data'][$key]['url_outlet_image_cover']);
                unset($list['data'][$key]['call']);
                unset($list['data'][$key]['url']);
            }
        }else{
            $list = $list->get()->toArray();

            foreach ($list as $key=>$dt){
                $list[$key]['outlet_name'] = $dt['outlet_name'];
                $list[$key]['outlet_image_logo_landscape'] = $dt['url_outlet_image_logo_landscape'];
                unset($list[$key]['url_outlet_image_logo_landscape']);
                unset($list[$key]['url_outlet_image_logo_portrait']);
                unset($list[$key]['url_outlet_image_cover']);
                unset($list[$key]['call']);
                unset($list[$key]['url']);
            }
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function outletMerchantStorePage(){
        $topList = Outlet::join('merchants', 'merchants.id_outlet', 'outlets.id_outlet')
                ->where('outlet_status', 'Active')
                ->where('outlet_is_closed', 0)
                ->where('merchant_status', 'Active')->orderBy('merchant_count_transaction', 'desc')->limit(10)->get()->toArray();

        $top = [];
        $idTop = [];
        foreach ($topList as $dt){
            $idTop[] = $dt['id_outlet'];
            $top[] = [
                'id_outlet' => $dt['id_outlet'],
                'id_merchant' => $dt['id_merchant'],
                'outlet_name' => $dt['outlet_name'],
                'outlet_image_logo_portrait' => $dt['url_outlet_image_logo_portrait']
            ];
        }

        $newest = [];
        $newestList  = Outlet::join('merchants', 'merchants.id_outlet', 'outlets.id_outlet')
            ->where('outlet_status', 'Active')
            ->where('outlet_is_closed', 0)
            ->where('merchant_status', 'Active')
            ->whereNotIn('outlets.id_outlet', $idTop)
            ->orderBy('outlets.created_at', 'desc')->limit(10)->get()->toArray();

        foreach ($newestList as $dt){
            $newest[] = [
                'id_outlet' => $dt['id_outlet'],
                'id_merchant' => $dt['id_merchant'],
                'outlet_image_logo_landscape' => $dt['url_outlet_image_logo_portrait'],
                'outlet_image_logo_portrait' => $dt['url_outlet_image_logo_portrait']
            ];
        }

        $result = [
            'top_outlet' => $top,
            'newest_outlet' => $newest
        ];

        return response()->json(MyHelper::checkGet($result));
    }
}
