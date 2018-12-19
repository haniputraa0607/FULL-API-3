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

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;

use Modules\Outlet\Http\Requests\outlet\Upload;
use Modules\Outlet\Http\Requests\outlet\Update;
use Modules\Outlet\Http\Requests\outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\outlet\Create;
use Modules\Outlet\Http\Requests\outlet\Delete;
use Modules\Outlet\Http\Requests\outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\outlet\Nearme;
use Modules\Outlet\Http\Requests\outlet\Filter;											   

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

        if (isset($post['outlet_type'])) {
            $data['outlet_type'] = $post['outlet_type'];
        }
        if (isset($post['outlet_code'])) {
            $data['outlet_code'] = $post['outlet_code'];
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
        if (isset($post['outlet_limit_reservation'])) {
            $data['outlet_limit_reservation'] = $post['outlet_limit_reservation'];
        }
        if (isset($post['outlet_pin'])) {
            $data['outlet_pin'] = bcrypt($post['outlet_pin']);
        }

        return $data;
    }

    /* Pengecekan code unique */
    function cekUnique($id, $code) {
        $cek = Outlet::where('outlet_code', $code)->first();

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
                $code = Outlet::where('outlet_code', $post['outlet_code'])->first();
            }while($code != null);
        }
        $save = Outlet::create($post);
        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update
     */
    function update(Update $request) {
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
        
        return response()->json(MyHelper::checkUpdate($outlet));
    }

    /**
     * list
     */
    function listOutlet(Request $request) {
        $post = $request->json()->all();
        $outlet = Outlet::with(['city', 'outlet_photos', 'product_prices', 'product_prices.product']);
        if (isset($post['outlet_code'])) {
            $outlet->with(['holidays', 'holidays.date_holidays'])->where('outlet_code', $post['outlet_code']);
        }
        
        if (isset($post['id_outlet'])) {
            $outlet->with(['holidays', 'holidays.date_holidays'])->where('id_outlet', $post['id_outlet']);
        }

        if (isset($post['id_city'])) {
            $outlet->where('id_city',$post['id_city']); 
        }

        if (isset($post['admin'])){
            $outlet->with(['user_outlets', 'product_prices.product']); 
        }

        $outlet = $outlet->orderBy('outlet_name')->get()->toArray();

        if(isset($post['type']) && $post['type'] == 'transaction'){
            $outlet = $this->setAvailableOutlet($outlet);
        }

        return response()->json(MyHelper::checkGet($outlet));
     
    }

    /* City Outlet */
    function cityOutlet(Request $request) {
        $outlet = Outlet::join('cities', 'cities.id_city', '=', 'outlets.id_city')->select('outlets.id_city', 'city_name')->distinct()->get()->toArray();

        // if (!empty($outlet)) {
        //     $outlet = array_pluck($outlet, 'city_name');
        // }
        return response()->json(MyHelper::checkGet($outlet));
    }

    /* Near Me*/
    function nearMe(Nearme $request) {
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');
        
        // outlet
        $outlet = Outlet::with(['city', 'outlet_photos'])->orderBy('outlet_name')->get()->toArray();

        if (!empty($outlet)) {
            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = $jaraknya." km";
                $outlet[$key]['dist']     = (float) $jaraknya;
            }
            usort($outlet, function($a, $b) { 
                return $a['dist'] <=> $b['dist']; 
            }); 

            if($request->json('type') && $request->json('type') == 'transaction'){
                $outlet = $this->setAvailableOutlet($outlet);
            }
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

	 /* Filter*/
    function filter(Filter $request) {
        $latitude  = $request->json('latitude');
        $longitude = $request->json('longitude');
        $distance = $request->json('distance');
        $id_city = $request->json('id_city');
        $sort = $request->json('sort');
        
        // outlet
        $outlet = Outlet::with(['city', 'outlet_photos'])->orderBy('outlet_name','asc')->get()->toArray();
		
		
        if (!empty($outlet)) {
            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)$this->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");
				
                $outlet[$key]['distance'] = $jaraknya." km";
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
        }

        return response()->json(MyHelper::checkGet($urutan));
    }

    // unset outlet yang tutp dan libur
    function setAvailableOutlet($listOutlet){
        $outlet = $listOutlet;
        foreach($listOutlet as $index => $dataOutlet){
            if($dataOutlet['outlet_open_hours'] && date('H:i:01') < date('H:i', strtotime($dataOutlet['outlet_open_hours']))){
                unset($outlet[$index]);
            }elseif($dataOutlet['outlet_close_hours'] && date('H:i') >= date('H:i', strtotime($dataOutlet['outlet_close_hours']))){
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
}
