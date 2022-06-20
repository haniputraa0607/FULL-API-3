<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use App\Http\Models\Outlet;
use DB;
use App\Lib\MyHelper;
use App\Http\Models\Province;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairStylistTimeOff;
use Modules\Recruitment\Entities\HairstylistOverTime;
use Modules\Transaction\Entities\HairstylistNotAvailable;


class ApiHairStylistTimeOffOvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function listHS(Request $request){
        $post = $request->all();
        $list_hs = UserHairStylist::where('id_outlet', $post['id_outlet'])->where('user_hair_stylist_status', 'Active')->get()->toArray();
        return $list_hs;
    }

    public function listDate(Request $request){
        $post = $request->all();
        if(empty($post['id_user_hair_stylist']) || empty($post['month']) || empty($post['year'])){
            return response()->json([
            	'status' => 'empty', 
            ]);
        }

        if($post['year']>=date('Y') || (isset($post['type']) && $post['type'] == 'getDetail')){
            if($post['month']>=date('m')|| (isset($post['type']) && $post['type'] == 'getDetail')){
                $schedule = HairstylistSchedule::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->where('schedule_month', $post['month'])->where('schedule_year', $post['year'])->first();
                if($schedule){
                    $id_schedule = $schedule['id_hairstylist_schedule'];
                    $data_outlet = Outlet::where('id_outlet', $schedule['id_outlet'])->first();
                    $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                    ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
                    if($timeZone == 7){
                        $send_timezone = 'WIB';
                    }elseif($timeZone == 8){
                        $send_timezone = 'WITA';
                    }elseif($timeZone == 9){
                        $send_timezone = 'WIT';
                    }
                    if(isset($post['date'])){
                        $time = HairstylistScheduleDate::where('id_hairstylist_schedule',$id_schedule)->where('date',$post['date'])->first();
                        $time['time_start'] = $time['time_start'] ? MyHelper::adjustTimezone($time['time_start'], $timeZone, 'H:i') : null;
                        $time['time_end'] = $time['time_end'] ? MyHelper::adjustTimezone($time['time_end'], $timeZone, 'H:i') : null;
                        $time['timezone'] = $send_timezone ?? null;
                        return response()->json([
                            'status' => 'success', 
                            'result' => $time
                        ]); 
                    }

                    $detail = HairstylistScheduleDate::where('id_hairstylist_schedule',$id_schedule)->get()->toArray();
                    if($detail){
                        $send = [];
                        foreach($detail as $key => $data){
                            if($data['date'] >= date('Y-m-d 00:00:00')){
                                $send[$key]['id_hairstylist_schedule_date'] = $data['id_hairstylist_schedule_date'];
                                $send[$key]['date'] = $data['date'];
                                $send[$key]['date_format'] = date('d F Y', strtotime($data['date']));
                                $send[$key]['time_start'] = $data['time_start'] ? MyHelper::adjustTimezone($data['time_start'], $timeZone, 'H:i') : null;
                                $send[$key]['time_end'] = $data['time_end'] ? MyHelper::adjustTimezone($data['time_end'], $timeZone, 'H:i') : null;
                                $send[$key]['timezone'] = $send_timezone ?? null;
                            }
                        }
                        return response()->json([
                            'status' => 'success', 
                            'result' => $send
                        ]); 
                    }
                }
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['The schedule for this hair stylist has not been created yet']
                ]);
            }else{
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['The month must be greater than or equal to this month']
                ]);
            }
        }else{
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['The year must be greater than or equal to this year']
            ]);
        }
       
    }

    public function createTimeOff(Request $request){
        $post = $request->all();
        $data_store = [];
        if(isset($post['id_hs'])){
            $data_store['id_user_hair_stylist'] = $post['id_hs'];
        }
        if(isset($post['id_outlet'])){
            $data_store['id_outlet'] = $post['id_outlet'];
            $data_outlet = Outlet::where('id_outlet', $data_store['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
        }
        if(isset($post['date'])){
            $data_store['date'] = $post['date'];
        }
        if(isset($post['time_start'])){
            $data_store['start_time'] = $post['time_start'] ? MyHelper::reverseAdjustTimezone($post['time_start'], $timeZone, 'H:i:s') : null;
        }
        if(isset($post['time_end'])){
            $data_store['end_time'] = $post['time_end'] ? MyHelper::reverseAdjustTimezone($post['time_end'], $timeZone, 'H:i:s') : null;
        }
        
        $data_store['request_by'] = auth()->user()->id;
        $data_store['request_at'] = date('Y-m-d');

        if($data_store){
            DB::beginTransaction();
            $store = HairStylistTimeOff::create($data_store);
            if(!$store){
                DB::rollBack();
                return response()->json([
                    'status' => 'success', 
                    'messages' => ['Failed to create a request hair stylist time off']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success', 
                'result' => $store
            ]);
        }
    }

    public function listTimeOff(Request $request)
    {
        $post = $request->all();
        $time_off = HairStylistTimeOff::join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','=','hairstylist_time_off.id_user_hair_stylist')
                    ->join('outlets', 'outlets.id_outlet', '=', 'hairstylist_time_off.id_outlet')
                    ->join('users', 'users.id', '=', 'hairstylist_time_off.request_by')
                    ->select(
                        'hairstylist_time_off.*',
                        'user_hair_stylist.fullname',
                        'outlets.outlet_name',
                        'users.name as request_by'
                    );
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){
                         
                        if($condition['subject']=='name_hs'){
                            $subject = 'user_hair_stylist.fullname';
                        }elseif($condition['subject']=='outlet'){
                            $subject = 'outlets.outlet_name';
                        }elseif($condition['subject']=='request'){
                            $subject = 'users.name';
                        }else{
                            $subject = $condition['subject'];  
                        }

                        if($condition['operator'] == '='){
                            $time_off = $time_off->where($subject, $condition['parameter']);
                        }else{
                            $time_off = $time_off->where($subject, 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $time_off = $time_off->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['subject']=='name_hs'){
                                $subject = 'user_hair_stylist.fullname';
                            }elseif($condition['subject']=='outlet'){
                                $subject = 'outlets.outlet_name';
                            }elseif($condition['subject']=='request'){
                                $subject = 'users.name';
                            }else{
                                $subject = $condition['subject'];  
                            }

                            if($condition['operator'] == '='){
                                $q->orWhere($subject, $condition['parameter']);
                            }else{
                                $q->orWhere($subject, 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if($post['order']=='name_hs'){
                $order = 'user_hair_stylist.fullname';
            }elseif($post['order']=='outlet'){
                $order = 'outlets.outlet_name';
            }elseif($post['order']=='request'){
                $order = 'users.name';
            }else{
                $order = 'hairstylist_time_off.created_at';
            }
            if(isset($post['page'])){
                $time_off = $time_off->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $time_off = $time_off->orderBy('hairstylist_time_off.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy('hairstylist_time_off.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($time_off);
    }

    public function deleteTimeOff(Request $request){
        $post = $request->all();
        $delete = HairStylistTimeOff::where('id_hairstylist_time_off', $post['id_hairstylist_time_off'])->update(['reject_at' => date('Y-m-d')]);
        if($delete){
            $delete_hs_not_avail = HairstylistNotAvailable::where('id_hairstylist_time_off', $post['id_hairstylist_time_off'])->delete();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function detailTimeOff(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_time_off']) && !empty($post['id_hairstylist_time_off'])){
            $time_off = HairStylistTimeOff::where('id_hairstylist_time_off', $post['id_hairstylist_time_off'])->with(['hair_stylist','outlet','approve','request'])->first();
            $data_outlet = Outlet::where('id_outlet', $time_off['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
            if($timeZone == 7){
                $send_timezone = 'WIB';
            }elseif($timeZone == 8){
                $send_timezone = 'WITA';
            }elseif($timeZone == 9){
                $send_timezone = 'WIT';
            }
            if($time_off==null){
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => 'Empty',
                ]]);
            } else {
                $time_off['start_time'] = $time_off['start_time'] ? MyHelper::adjustTimezone($time_off['start_time'], $timeZone, 'H:i') : null;
                $time_off['end_time'] = $time_off['end_time'] ? MyHelper::adjustTimezone($time_off['end_time'], $timeZone, 'H:i') : null;
                $time_off['timezone'] = $send_timezone ?? null;
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => $time_off,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateTimeOff(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_time_off']) && !empty($post['id_hairstylist_time_off'])){
            $data_update = [];
            if(isset($post['id_hs'])){
                $data_update['id_user_hair_stylist'] = $post['id_hs'];
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
                $data_outlet = Outlet::where('id_outlet', $data_update['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
            }
            if(isset($post['date'])){
                $data_update['date'] = $post['date'];
            }
            if(isset($post['time_start'])){
                $data_update['start_time'] = $post['time_start'] ? MyHelper::reverseAdjustTimezone($post['time_start'], $timeZone, 'H:i:s') : null;
            }
            if(isset($post['time_end'])){
                $data_update['end_time'] = $post['time_end'] ? MyHelper::reverseAdjustTimezone($post['time_end'], $timeZone, 'H:i:s') : null;
            }
            if(isset($post['approve'])){
                $data_update['approve_by'] = auth()->user()->id;
                $data_update['approve_at'] = date('Y-m-d');
            }

            if($data_update){
                DB::beginTransaction();
                $update = HairStylistTimeOff::where('id_hairstylist_time_off',$post['id_hairstylist_time_off'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request hair stylist time off']
                    ]);
                }
                if(isset($post['approve'])){
                    $data_not_avail = [
                        "id_outlet" => $data_update['id_outlet'],
                        "id_user_hair_stylist" => $data_update['id_user_hair_stylist'],
                        "id_hairstylist_time_off" => $post['id_hairstylist_time_off'],
                        "booking_start" => date('Y-m-d', strtotime($data_update['date'])).' '.$data_update['start_time'],
                        "booking_end" => date('Y-m-d', strtotime($data_update['date'])).' '.$data_update['end_time'],
                    ];
                    $store_not_avail = HairstylistNotAvailable::create($data_not_avail);
                    if(!$store_not_avail){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'success', 
                            'messages' => ['Failed to updated a request hair stylist time off']
                        ]);
                    }
                }
                DB::commit();
                return response()->json([
                    'status' => 'success'
                ]);
            }
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function createOvertime(Request $request){
        $post = $request->all();
        $data_store = [];
        if(isset($post['id_hs'])){
            $data_store['id_user_hair_stylist'] = $post['id_hs'];
        }
        if(isset($post['id_outlet'])){
            $data_store['id_outlet'] = $post['id_outlet'];
        }
        if(isset($post['date'])){
            $data_store['date'] = $post['date'];
        }
        if(isset($post['time'])){
            $data_store['time'] =$post['time'];
        }
        if(isset($post['duration'])){
            $data_store['duration'] = date('H:i:s', strtotime($post['duration']));
        }
        
        $data_store['request_by'] = auth()->user()->id;
        $data_store['request_at'] = date('Y-m-d');
        if($data_store){
            DB::beginTransaction();
            $store = HairstylistOverTime::create($data_store);
            if(!$store){
                DB::rollBack();
                return response()->json([
                    'status' => 'success', 
                    'messages' => ['Failed to create a request hair stylist overtime']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success', 
                'result' => $store
            ]);
        }
    }

    public function listOvertime(Request $request)
    {
        $post = $request->all();
        $time_off = HairstylistOverTime::join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','=','hairstylist_overtime.id_user_hair_stylist')
                    ->join('outlets', 'outlets.id_outlet', '=', 'hairstylist_overtime.id_outlet')
                    ->join('users', 'users.id', '=', 'hairstylist_overtime.request_by')
                    ->select(
                        'hairstylist_overtime.*',
                        'user_hair_stylist.fullname',
                        'outlets.outlet_name',
                        'users.name as request_by'
                    );
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){
                         
                        if($condition['subject']=='name_hs'){
                            $subject = 'user_hair_stylist.fullname';
                        }elseif($condition['subject']=='outlet'){
                            $subject = 'outlets.outlet_name';
                        }elseif($condition['subject']=='request'){
                            $subject = 'users.name';
                        }else{
                            $subject = $condition['subject'];  
                        }

                        if($condition['operator'] == '='){
                            $time_off = $time_off->where($subject, $condition['parameter']);
                        }else{
                            $time_off = $time_off->where($subject, 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $time_off = $time_off->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['subject']=='name_hs'){
                                $subject = 'user_hair_stylist.fullname';
                            }elseif($condition['subject']=='outlet'){
                                $subject = 'outlets.outlet_name';
                            }elseif($condition['subject']=='request'){
                                $subject = 'users.name';
                            }else{
                                $subject = $condition['subject'];  
                            }

                            if($condition['operator'] == '='){
                                $q->orWhere($subject, $condition['parameter']);
                            }else{
                                $q->orWhere($subject, 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if($post['order']=='name_hs'){
                $order = 'user_hair_stylist.fullname';
            }elseif($post['order']=='outlet'){
                $order = 'outlets.outlet_name';
            }elseif($post['order']=='request'){
                $order = 'users.name';
            }else{
                $order = 'hairstylist_overtime.created_at';
            }
            if(isset($post['page'])){
                $time_off = $time_off->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $time_off = $time_off->orderBy('hairstylist_overtime.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy('hairstylist_overtime.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($time_off);
    }

    public function deleteOvertime(Request $request){
        $post = $request->all();
        $check = HairstylistOverTime::where('id_hairstylist_overtime', $post['id_hairstylist_overtime'])->first();
        if($check){
            DB::beginTransaction();
            $month_sc = date('m', strtotime($check['date']));
            $year_sc = date('Y', strtotime($check['date']));
            $get_schedule = HairstylistSchedule::where('id_user_hair_stylist', $check['id_user_hair_stylist'])->where('schedule_month', $month_sc)->where('schedule_year',$year_sc)->first();
            if($get_schedule){
                $get_schedule_date = HairstylistScheduleDate::where('id_hairstylist_schedule',$get_schedule['id_hairstylist_schedule'])->where('date',$check['date'])->first();
                if($get_schedule_date){
                    if($check['time'] == 'after'){
                        $duration = strtotime($check['duration']);
                        $start = strtotime($get_schedule_date['time_end']);
                        $diff = $start - $duration;
                        $hour = floor($diff / (60*60));
                        $minute = floor(($diff - ($hour*60*60))/(60));
                        $second = floor(($diff - ($hour*60*60))%(60));
                        $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
                        $order = 'time_end';
                        $order_att = 'clock_out_requirement';
                    }elseif($check['time'] = 'before'){
                        $secs = strtotime($check['duration'])-strtotime("00:00:00");
                        $new_time = date("H:i:s",strtotime($get_schedule_date['time_start'])+$secs);
                        $order = 'time_start';
                        $order_att = 'clock_in_requirement';
                    }
                    $update_schedule = HairstylistScheduleDate::where('id_hairstylist_schedule_date',$get_schedule_date['id_hairstylist_schedule_date'])->update([$order => $new_time,  'is_overtime' => 0, 'id_outlet_box' => null]);
                    $update_overtime = HairstylistOverTime::where('id_hairstylist_overtime', $post['id_hairstylist_overtime'])->update(['reject_at' => date('Y-m-d')]);
                    if(!$update_overtime || !$update_schedule){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'fail'
                        ]);
                    }
                    $attendance = HairstylistAttendance::where('id_hairstylist_schedule_date',$get_schedule_date['id_hairstylist_schedule_date'])->where('id_user_hair_stylist', $check['id_user_hair_stylist'])->where('attendance_date',$check['date'])->update([$order_att => $new_time]);
                    DB::commit();
                    return response()->json([
                        'status' => 'success'
                    ]);

                }
            }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detailOvertime(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_overtime']) && !empty($post['id_hairstylist_overtime'])){
            $time_off = HairstylistOverTime::where('id_hairstylist_overtime', $post['id_hairstylist_overtime'])->with(['hair_stylist','outlet','approve','request'])->first();
            
            if($time_off==null){
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => $time_off,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateOvertime(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_overtime']) && !empty($post['id_hairstylist_overtime'])){
            $data_update = [];
            if(isset($post['id_hs'])){
                $data_update['id_user_hair_stylist'] = $post['id_hs'];
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
            }
            if(isset($post['date'])){
                $data_update['date'] = $post['date'];
            }
            if(isset($post['time'])){
                $data_update['time'] =$post['time'];
            }
            if(isset($post['duration'])){
                $data_update['duration'] = date('H:i:s', strtotime($post['duration']));
            }
            
            if(isset($post['approve'])){
                $data_update['approve_by'] = auth()->user()->id;
                $data_update['approve_at'] = date('Y-m-d');
            }

            
            if($data_update){
                DB::beginTransaction();
                $update = HairstylistOverTime::where('id_hairstylist_overtime',$post['id_hairstylist_overtime'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'fail', 
                        'messages' => ['Failed to updated a request hair stylist overtime']
                    ]);
                }
                if(isset($post['approve'])){
                    $update_schedule = $this->updatedScheduleOvertime($data_update);
                    if(!$update_schedule){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'fail', 
                            'messages' => ['Failed to updated a request hair stylist overtime']
                        ]);
                    }
                }
                DB::commit();
                return response()->json([
                    'status' => 'success'
                ]);
            }
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updatedScheduleOvertime($data){
        //get schedule
        $month_sc = date('m', strtotime($data['date']));
        $year_sc = date('Y', strtotime($data['date']));
        $get_schedule = HairstylistSchedule::where('id_user_hair_stylist', $data['id_user_hair_stylist'])->where('schedule_month', $month_sc)->where('schedule_year',$year_sc)->first();
        if($get_schedule){
            $get_schedule_date = HairstylistScheduleDate::where('id_hairstylist_schedule',$get_schedule['id_hairstylist_schedule'])->where('date',$data['date'])->first();
            if($get_schedule_date){
                //update
                if($data['time']=='before'){
                    $duration = strtotime($data['duration']);
                    $start = strtotime($get_schedule_date['time_start']);
                    $diff = $start - $duration;
                    $hour = floor($diff / (60*60));
                    $minute = floor(($diff - ($hour*60*60))/(60));
                    $second = floor(($diff - ($hour*60*60))%(60));
                    $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
                    $order = 'time_start';
                    $order_att = 'clock_in_requirement';
                }elseif($data['time']=='after'){
                    $secs = strtotime($data['duration'])-strtotime("00:00:00");
                    $new_time = date("H:i:s",strtotime($get_schedule_date['time_end'])+$secs);
                    $order = 'time_end';
                    $order_att = 'clock_out_requirement';
                }else{
                    return false;
                }

                $update_date = HairstylistScheduleDate::where('id_hairstylist_schedule_date',$get_schedule_date['id_hairstylist_schedule_date'])->update([$order => $new_time,  'is_overtime' => 1]);
                if($update_date){
                    $attendance = HairstylistAttendance::where('id_hairstylist_schedule_date',$get_schedule_date['id_hairstylist_schedule_date'])->where('id_user_hair_stylist', $get_schedule['id_user_hair_stylist'])->whereDate('attendance_date',$get_schedule_date['date'])->first();
                    if($attendance){
                        $update_attendance = HairstylistAttendance::where('id_hairstylist_attendance', $attendance['id_hairstylist_attendance'])->update([$order_att => $new_time]);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function checkTimeOffOvertime(){
        $log = MyHelper::logCron('Check Request Hair Stylist Time Off and Overtime');
        try{
            $data_time_off = HairStylistTimeOff::whereNull('reject_at')->whereNull('approve_at')->whereDate('request_at','<',date('Y-m-d'))->get()->toArray();
            if($data_time_off){
                foreach($data_time_off as $time_off){
                    $update = HairStylistTimeOff::where('id_hairstylist_time_off', $time_off['id_hairstylist_time_off'])->update(['reject_at' => date('Y-m-d')]);
                }
            }

            $data_overtime = HairstylistOverTime::whereNull('reject_at')->whereNull('approve_at')->whereDate('request_at','<',date('Y-m-d'))->get()->toArray();
            if($data_overtime){
                foreach($data_overtime as $overtime){
                    $update = HairstylistOverTime::where('id_hairstylist_overtime', $overtime['id_hairstylist_overtime'])->update(['reject_at' => date('Y-m-d')]);
                }
            }
            $log->success('success');
            return response()->json(['status' => 'success']);

        }catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }    
    }
}
