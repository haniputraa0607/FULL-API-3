<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
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

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\outlet\Upload;
use Modules\Outlet\Http\Requests\outlet\Update;
use Modules\Outlet\Http\Requests\outlet\UpdateStatus;
use Modules\Outlet\Http\Requests\outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\outlet\Create;
use Modules\Outlet\Http\Requests\outlet\Delete;
use Modules\Outlet\Http\Requests\outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\outlet\Nearme;
use Modules\Outlet\Http\Requests\outlet\Filter;
use Modules\Outlet\Http\Requests\outlet\OutletList;

use Modules\Outlet\Http\Requests\UserOutlet\Create as CreateUserOutlet;
use Modules\Outlet\Http\Requests\UserOutlet\Update as UpdateUserOutlet;

use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;

class ApiOutletController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
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
        if (isset($post['big_order'])) {
            $data['big_order'] = $post['big_order'];
        }else{
            $data['big_order'] = 0;
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
        if(!isset($post['outlet_code'])){
            do{
                $post['outlet_code'] = MyHelper::createRandomPIN(3);
                $code = Outlet::where('outlet_code', strtoupper($post['outlet_code']))->first();
            }while($code != null);
        }

        DB::beginTransaction();
        $save = Outlet::create($post);
        if (!$save) {
            DB::rollBack();
        }

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
        //schedule
        if($request->json('day') && $request->json('open') && $request->json('close')){
            $days = $request->json('day');
            $opens = $request->json('open');
            $closes = $request->json('close');
            $is_closed = $request->json('is_closed');
            foreach($days as $key => $value){
                $data['open'] = $opens[$key];
                $data['close'] = $closes[$key];
                $data['is_close'] = $is_closed[$key];
                $saveSchedule = OutletSchedule::updateOrCreate(['id_outlet' => $save['id_outlet'], 'day' => $value], $data);
                if (!$saveSchedule) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail']);
                }
            }
        }

        DB::commit();
        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update
     */
    function update(Update $request) {
        $post = $this->checkInputOutlet($request->json()->all());

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

        unset($post['outlet_brands']);
        $save = Outlet::where('id_outlet', $request->json('id_outlet'))->update($post);
        // return Outlet::where('id_outlet', $request->json('id_outlet'))->first();
        if($save){
            DB::commit();
        }else{
            DB::rollBack();
        }
        return response()->json(MyHelper::checkUpdate($save));
    }

    function updateStatus(UpdateStatus $request) {
        $post = $this->checkInputOutlet($request->json()->all());
        $save = Outlet::where('id_outlet', $request->json('id_outlet'))->update($post);
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

        $pin = bcrypt($post['outlet_pin']);
        $outlet->outlet_pin = $pin;
        $outlet->save();

        //delete token
        $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                                    ->where('oauth_access_tokens.user_id', $post['id_outlet'])->where('oauth_access_token_providers.provider', 'outlet-app')->delete();


        return response()->json(MyHelper::checkUpdate($outlet));
    }
    /**
     * list
     */
    function listOutlet(OutletList $request) {
        $post = $request->json()->all();

        if (isset($post['webview'])) {
            $outlet = Outlet::with(['today']);
        }elseif(isset($post['admin'])){
            $outlet = Outlet::with(['user_outlets','city','today'])->select('*');
            if(isset($post['id_product'])){
                $outlet = $outlet->with(['product_prices'=> function($q) use ($post){
                    $q->where('id_product', $post['id_product']);
                }]);
            }else{
                $outlet = $outlet->with(['product_prices']);
            }
        }
        elseif($post['simple_result']??false) {
            $outlet = Outlet::select('outlets.id_outlet','outlets.outlet_name');
        }else{
            $outlet = Outlet::with(['city', 'outlet_photos', 'outlet_schedules', 'today', 'user_outlets','brands']);
            if(!($post['id_outlet']??false)||!($post['id_outlet']??false)){                
                $outlet->select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude');
            }
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
            $outlet->with(['holidays', 'holidays.date_holidays','brands'])->where('outlet_code', $post['outlet_code']);
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

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($_SERVER['HTTP_USER_AGENT'],'iOS')) $useragent = 'iOS';
        if(stristr($_SERVER['HTTP_USER_AGENT'],'okhttp')) $useragent = 'Android';
        if($useragent == 'Android' || $useragent == 'iOS'){
            $outlet = $outlet->where('outlet_status', 'Active')->whereNotNull('id_city');
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
            }else{
                $outlet = $outlet->orderBy('outlet_name')->get()->toArray();
                foreach ($outlet as $key => $value) {
                    $qr      = $value['outlet_code'];

                    $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
                    $qrCode = html_entity_decode($qrCode);

                    $outlet[$key]['qrcode'] = $qrCode;
                }
            }
            $request['page'] = 0;
        }else{
            $outlet = $outlet->orderBy('outlet_name')->get()->toArray();
        }

        if(isset($post['type']) && $post['type'] == 'transaction'){
            $outlet = $this->setAvailableOutlet($outlet);
        }
        $outlet = array_map(function($var) use ($post){
            $var['url']=env('VIEW_URL').'/outlet/webview/'.$var['id_outlet'];
            if(($post['latitude']??false)&&($post['longitude']??false)){
                $var['distance']=number_format((float)$this->distance($post['latitude'], $post['longitude'], $var['outlet_latitude'], $var['outlet_longitude'], "K"), 2, '.', '').' km';
            }
            return $var;
        }, $outlet);
        if (isset($post['webview'])) {
            if(isset($outlet[0])){
                $latitude  = $post['latitude'];
                $longitude = $post['longitude'];
                $jaraknya = number_format((float)$this->distance($latitude, $longitude, $outlet[0]['outlet_latitude'], $outlet[0]['outlet_longitude'], "K"), 2, '.', '');
                $outlet[0]['distance'] = $jaraknya." km";

                $outlet[0]['url'] = env('VIEW_URL').'/outlet/webview/'.$post['id_outlet'];

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
        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $outlet;
            $outlet = [];
            $pagingOutlet=$this->pagingOutlet($dataOutlet, $page);
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
            return response()->json(['status' => 'success', 'messages' => ['We need to access your location']]);
        }

        // outlet
        $outlet = Outlet::select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_phone','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude')->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');
        if($request->json('search') && $request->json('search') != ""){
            $outlet = $outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%');
        }
        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;
            }
            usort($outlet, function($a, $b) {
                return $a['dist'] <=> $b['dist'];
            });

            if($request->json('type') && $request->json('type') == 'transaction'){
                $outlet = $this->setAvailableOutlet($outlet);
            }
        }else{
            return response()->json(['status' => 'success', 'messages' => ['There is no open store','at this moment']]);
        }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $outlet;
            $outlet = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);
            if (isset($pagingOutlet['data']) && count($pagingOutlet['data']) > 0) {
                $outlet['status'] = 'success';
                $outlet['current_page']  = $page;
                $outlet['data']          = $pagingOutlet['data'];
                $outlet['total']         = count($dataOutlet);
                $outlet['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $outlet['next_page_url'] = ENV('APP_API_URL').'api/outlet/nearme?page='.$next_page;
                }
            } else {
                return response()->json(['status' => 'success', 'messages' => ['There is no open store','at this moment']]);
            }
        }

        if(!$outlet){
            return response()->json(['status' => 'success', 'messages' => ['There is no open store','at this moment']]);
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

    /* Near Me Geolocation, return geojson */
    function nearMeGeolocation(Nearme $request) {
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!$latitude || !$longitude){
            return response()->json(['status' => 'success', 'messages' => ['We need to access your location']]);
        }

        // outlet
        $outlet = Outlet::with(['today', 'city', 'outlet_photos'])->orderBy('outlet_name','asc')->where('outlet_status', 'Active')->whereNotNull('id_city');
        if($request->json('search') && $request->json('search') != ""){
            $outlet = $outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%');
        }
        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;
            }
            usort($outlet, function($a, $b) {
                return $a['dist'] <=> $b['dist'];
            });

            if($request->json('type') && $request->json('type') == 'transaction'){
                $outlet = $this->setAvailableOutlet($outlet);
            }
        }

        if(isset($request['page']) && $request['page'] > 0){
            $page = $request['page'];
            $next_page = $page + 1;

            $dataOutlet = $outlet;
            $outlet = [];

            $pagingOutlet = $this->pagingOutlet($dataOutlet, $page);
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

    public function pagingOutlet($data, $page) {
        $next = false;

        if ($page > 0) {
            $resultData = [];
            $paginate   = 15;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($data)) {
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

	/* Filter*/
    function filter(Filter $request) {
        $post=$request->except('_token');
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');

        if(!isset($latitude) || !isset($longitude)){
            return response()->json([
                'status' => 'success',
                'messages' => ['We need to access your location']
            ]);
        }

        $distance = $request->json('distance');
        $id_city = $request->json('id_city');
        $sort = $request->json('sort');
        $gofood = $request->json('gofood');
        $grabfood = $request->json('grabfood');

        // outlet
        $outlet = Outlet::with(['today'])->select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_phone','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude')->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');

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
            $outlet = $outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%');
        }

        if ($gofood) {
            $outlet = $outlet->whereNotNull('deep_link_gojek');
        }

        if ($grabfood) {
            $outlet = $outlet->whereNotNull('deep_link_grab');
        }

        $outlet = $outlet->get()->toArray();


        if (!empty($outlet)) {
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

            if($request->json('type') && $request->json('type') == 'transaction'){
                $urutan = $this->setAvailableOutlet($urutan);
            }
        } else {
            return response()->json(['status' => 'success', 'messages' => ['There is no open store','at this moment']]);
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
            if (isset($pagingOutlet['data']) && count($pagingOutlet['data']) > 0) {
                $urutan['status'] = 'success';
                $urutan['current_page']  = $page;
                $urutan['data']          = $pagingOutlet['data'];
                $urutan['total']         = count($dataOutlet);
                $urutan['next_page_url'] = null;

                if ($pagingOutlet['status'] == true) {
                    $urutan['next_page_url'] = ENV('APP_API_URL').'api/outlet/filter?page='.$next_page;
                }
            } else {
                return response()->json(['status' => 'success', 'messages' => ['There is no open store','at this moment']]);
            }
        }
        if(!$urutan){
            return response()->json(['status' => 'success', 'messages' => ['There is no open store','at this moment']]);
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
                'messages' => ['We need to access your location']
            ]);
        }

        $distance = $request->json('distance');
        $id_city = $request->json('id_city');
        $sort = $request->json('sort');

        // outlet
        $outlet = Outlet::with(['today', 'city', 'outlet_photos'])->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');
        if($request->json('search') && $request->json('search') != ""){
            $outlet = $outlet->where('outlet_name', 'LIKE', '%'.$request->json('search').'%');
        }
        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
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

            if($request->json('type') && $request->json('type') == 'transaction'){
                $urutan = $this->setAvailableOutlet($urutan);
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

        $geojson_file_url = env('API_URL') . 'files/stations.geojson' . '?';

        if($urutan && !empty($urutan)) return ['status' => 'success', 'result' => $urutan, 'url'=>$geojson_file_url];
        else if(empty($urutan)) return ['status' => 'fail', 'messages' => ['empty']];
        else return ['status' => 'fail', 'messages' => ['failed to retrieve data']];

        // return response()->json(MyHelper::checkGet($urutan));
    }

    // unset outlet yang tutup dan libur
    function setAvailableOutlet($listOutlet){
        $processing = '0';
        $settingTime = Setting::where('key', 'processing_time')->first();
        if($settingTime && $settingTime->value){
            $processing = $settingTime->value;
        }

        $outlet = $listOutlet;
        foreach($listOutlet as $index => $dataOutlet){
            if($dataOutlet['today']['open'] == null || $dataOutlet['today']['close'] == null){
                unset($outlet[$index]);
            }else{
                if($dataOutlet['today']['is_closed'] == '1'){
                    unset($outlet[$index]);
                }else{
                    if($dataOutlet['today']['open'] != "00:00" && $dataOutlet['today']['close'] != "00:00"){
                        if($dataOutlet['today']['open'] && date('H:i:01') < date('H:i', strtotime($dataOutlet['today']['open']))){
                            unset($outlet[$index]);
                        }elseif($dataOutlet['today']['close'] && date('H:i') > date('H:i', strtotime('-'.$processing.' minutes', strtotime($dataOutlet['today']['close'])))){
                            unset($outlet[$index]);
                        }else{
                            $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                            ->where('id_outlet', $dataOutlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
                            if(count($holiday) > 0){
                                foreach($holiday as $i => $holi){
                                    if($holi['yearly'] == '0'){
                                        if($holi['date'] == date('Y-m-d')){
                                            unset($outlet[$index]);
                                            break;
                                        }
                                    }else{
                                        unset($outlet[$index]);
                                        break;
                                    }
                                }

                            }
                        }
                    }
                }
            }
            unset($outlet[$index]['product_prices']);
            unset($outlet[$index]['outlet_schedules']);
            unset($outlet[$index]['outlet_photos']);
            unset($outlet[$index]['outlet_pin']);
        }
        return array_values($outlet);
    }

    /* Penghitung jarak */
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {
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

        return response()->json(MyHelper::checkUpdate($update));
    }

    function export(Request $request) {
            $outlet = Outlet::select('outlets.outlet_code as code',
            'outlets.outlet_name as name',
            'outlets.outlet_address as address',
            'cities.city_name as city',
            'outlets.outlet_phone as phone',
            'outlets.outlet_email as email',
            'outlets.outlet_latitude as latitude',
            'outlets.outlet_longitude as longitude',
            'outlets.outlet_open_hours as open_hours',
            'outlets.outlet_close_hours as close_hours'
            )->join('cities', 'outlets.id_city', '=', 'cities.id_city');

            $outlet = $outlet->get()->toArray();

            return response()->json(MyHelper::checkGet($outlet));
    }

    function import(Request $request) {
        $path = $request->file('import_file')->getRealPath();
        $dataimport = Excel::load($path, function($reader) {})->get();

        if(!empty($dataimport) && $dataimport->count()){
        $city = City::get();
        $id_city = array_pluck($city, 'id_city');
        $city_name = array_pluck($city, 'city_name');
        $city_name = array_map('strtolower', $city_name);

        DB::beginTransaction();
        $countImport = 0;
        foreach ($dataimport as $key => $value) {
            if(
                empty($value->code) &&
                empty($value->name) &&
                empty($value->address) &&
                empty($value->city) &&
                empty($value->phone) &&
                empty($value->latitude) &&
                empty($value->longitude) &&
                empty($value->open_hours) &&
                empty($value->close_hours)
            )
            {}else{
                $search = array_search(strtolower($value->city), $city_name);
                if(!empty($search) && $key < count($dataimport)){
                    if(!empty($value->open_hours)){
                        $value->open_hours = date('H:i:s', strtotime($value->open_hours));
                    }
                    if(!empty($value->close_hours)){
                        $value->close_hours = date('H:i:s', strtotime($value->open_hours));
                    }
                    if(empty($value->code)){
                        do{
                            $value->code = MyHelper::createRandomPIN(3);
                            $code = Outlet::where('outlet_code', $value->code)->first();
                        }while($code != null);
                    }
                    $code = ['outlet_code' => $value->code];
                    $insert = [
                        'outlet_code' => $value->code,
                        'outlet_name' => $value->name,
                        'outlet_address' => $value->address,
                        'outlet_postal_code' => $value->postal_code,
                        'outlet_phone' => $value->phone,
                        'outlet_email' => $value->email,
                        'outlet_latitude' => $value->latitude,
                        'outlet_longitude' => $value->longitude,
                        'outlet_open_hours' => $value->open_hours,
                        'outlet_close_hours' => $value->close_hours,
                        'id_city' => $id_city[$search]
                    ];

                    if(!empty($insert['outlet_name'])){
                        $save = Outlet::updateOrCreate($code, $insert);

                        if(empty($save)){
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'      => [
                                    'Something went wrong'
                                ]
                            ]);
                        }else{
                            $countImport++;
                        }
                    }else{
                        if($key == count($dataimport) - 1){
                            DB::commit();
                            if($save) return ['status' => 'success', 'message' => $countImport.' data successfully imported.'];
                            else return ['status' => 'fail','messages' => ['failed to update data']];
                        }else{
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => 'outlet name is required.'
                            ]);
                        }
                    }
                }else{
                    if($key < count($dataimport)){
                        DB::rollBack();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'      => [
                                'Data city not found.'
                                ]
                            ]);
                    }else{
                    DB::commit();
                    if($save) return ['status' => 'success', 'message' => $countImport.' data successfully imported.'];
                    else return ['status' => 'fail','messages' => ['failed to update data']];
                    }
                }
            }
        }

        DB::commit();
        if($save) return ['status' => 'success', 'message' => $countImport.' data successfully imported.'];
        else return ['status' => 'fail','messages' => ['failed to update data']];
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

            foreach($post['type'] as $value){
                $post[$value] = 1;
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
        foreach ($post['day'] as $key => $value) {
            $data = [
                'day'       => $value,
                'open'      => $post['open'][$key],
                'close'     => $post['close'][$key],
                'is_closed' => $post['is_closed'][$key],
                'id_outlet' => $post['id_outlet']
            ];

            $save = OutletSchedule::updateOrCreate(['id_outlet' => $post['id_outlet'], 'day' => $value], $data);
            if (!$save) {
                DB::rollBack();
                return response()->json(['status' => 'fail']);
            }
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }
}
