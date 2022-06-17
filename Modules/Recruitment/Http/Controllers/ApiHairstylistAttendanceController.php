<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Province;
use App\Http\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;

class ApiHairstylistAttendanceController extends Controller
{
    /**
     * Menampilkan info clock in / clock out hari ini & informasi yg akan tampil saat akan clock in clock out
     * @return [type] [description]
     */
    public function liveAttendance(Request $request)
    {
        $today = date('Y-m-d');
        $hairstylist = $request->user();
        $outlet = $hairstylist->outlet()->select('outlet_name', 'outlet_latitude', 'outlet_longitude', 'id_city')->first();
        $outlet->setHidden(['call', 'url']);
        // get current schedule
        $todaySchedule = $hairstylist->hairstylist_schedules()
            ->selectRaw('date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement, shift')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
            ->whereNotNull('approve_at')
            ->where([
                'schedule_month' => date('m'),
                'schedule_year' => date('Y')
            ])
            ->whereDate('date', date('Y-m-d'))
            ->first();
        if (!$todaySchedule || !$todaySchedule->date) {
            return [
                'status' => 'fail',
                'messages' => ['Tidak ada kehadiran dibutuhkan untuk hari ini']
            ];
        }

        $attendance = $hairstylist->getAttendanceByDate($todaySchedule);

        // $logs = [];
        // $clock_in = $attendance->clock_in ?: $attendance->logs()->where('type', 'clock_in')->where('status', '<>', 'Rejected')->min('datetime');
        // $clock_out = $attendance->clock_out ?: $attendance->logs()->where('type', 'clock_out')->where('status', '<>', 'Rejected')->max('datetime');
        // if ($clock_in) {
        //     $logs[] = [
        //         'name' => 'Clock In',
        //         'value' => MyHelper::adjustTimezone($clock_in, null, 'H:i', true),
        //     ];
        // }
        // if ($clock_out) {
        //     $logs[] = [
        //         'name' => 'Clock Out',
        //         'value' => MyHelper::adjustTimezone($clock_out, null, 'H:i', true),
        //     ];
        // }

        $shiftNameMap = [
            'Morning' => 'Pagi',
            'Middle' => 'Tengah',
            'Evening' => 'Sore',
        ];

        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                    ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $result = [
            'clock_in_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_in_requirement, $timeZone, 'H:i', true),
            'clock_out_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_out_requirement, $timeZone, 'H:i', true),
            'shift_name' => $shiftNameMap[$todaySchedule->shift] ?? $todaySchedule->shift,
            'outlet' => $outlet,
            'logs' => $attendance->logs()->get()->transform(function($item) use($timeZone){
                return [
                    'location_name' => $item->location_name,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'longitude' => $item->longitude,
                    'type' => ucwords(str_replace('_', ' ',$item->type)),
                    'photo' => $item->photo_path ? config('url.storage_url_api') . $item->photo_path : null,
                    'date' => MyHelper::adjustTimezone($item->datetime, $timeZone, 'l, d F Y', true),
                    'time' => MyHelper::adjustTimezone($item->datetime, $timeZone, 'H:i'),
                    'notes' => $item->notes ?: '',
                ];
            }),
        ];

        return MyHelper::checkGet($result);
    }

    /**
     * Clock in / Clock Out
     * @return [type] [description]
     */
    public function storeLiveAttendance(Request $request)
    {
        $request->validate([
            'type' => 'string|required|in:clock_in,clock_out',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_name' => 'string|nullable|sometimes',
            'photo' => 'string|required',
        ]);
        $hairstylist = $request->user();
        $outlet = $hairstylist->outlet;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $date_time_now = MyHelper::adjustTimezone(date('Y-m-d H:i:s'), $timeZone, 'Y-m-d H:i:s', true);
        $attendance = $hairstylist->getAttendanceByDate(date('Y-m-d'));

        if ($request->type == 'clock_out' && !$attendance->logs()->where('type', 'clock_in')->exists()) {
            return [
                'status' => 'fail',
                'messages' => ['Tidak bisa melakukan Clock Out sebelum melakukan Clock In'],
            ];
        }

        $maximumRadius = MyHelper::setting('hairstylist_attendance_max_radius', 'value', 50);
        $distance = MyHelper::getDistance($request->latitude, $request->longitude, $outlet->outlet_latitude, $outlet->outlet_longitude);
        $outsideRadius = $distance > $maximumRadius;

        if ($outsideRadius && !$request->radius_confirmation) {
            return MyHelper::checkGet([
                'need_confirmation' => true,
                'message' => 'Waktu Jam Masuk/Keluar Anda akan diproses sebagai permintaan kehadiran dan memerlukan persetujuan dari atasan Anda.',
            ]);
        }

        $photoPath = null;
        $upload = MyHelper::uploadPhoto($request->photo, 'upload/attendances/');
        if ($upload['status'] == 'success') {
            $photoPath = $upload['path'];
        }

        $attendance->storeClock([
            'type' => $request->type,
            'datetime' => MyHelper::reverseAdjustTimezone($date_time_now, $timeZone, 'Y-m-d H:i:s', true),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_name' => $request->location_name ?: '',
            'photo_path' => $photoPath,
            'status' => $outsideRadius ? 'Pending' : 'Approved',
            'approved_by' => null,
            'notes' => $request->notes,
        ]);

        return MyHelper::checkGet([
            'need_confirmation' => false,
            'message' => 'Berhasil',
        ]);
    }

    /**
     * Menampilkan riwayat attendance & attendance requirement
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function histories(Request $request)
    {
        $request->validate([
            'month' => 'numeric|min:1|max:12|required',
            'year' => 'numeric|min:2020|max:3000',
        ]);
        $hairstylist = $request->user();
        $outlet = $hairstylist->outlet;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $scheduleMonth = $hairstylist->hairstylist_schedules()
            ->where('schedule_year', $request->year)
            ->where('schedule_month', $request->month)
            ->first();
        // $schedules = $scheduleMonth->hairstylist_schedule_dates()->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_attendance', 'hairstylist_schedule_dates.id_hairstylist_attendance')->orderBy('is_overtime')->get();
        if ($scheduleMonth) {
            $schedules = $scheduleMonth->hairstylist_schedule_dates()
                ->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                ->get();
        } else {
            $schedules = [];
        }
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year);
        
        $histories = [];
        for ($i = 1; $i <= $numOfDays; $i++) { 
            $date = "{$request->year}-{$request->month}-$i";
            $histories[$i] = [
                'date' => MyHelper::adjustTimezone($date, null, 'd M', true),
                'clock_in' => null,
                'clock_out' => null,
                'is_holiday' => true,
                'breakdown' => [],
            ];
        }

        foreach ($schedules as $schedule) {
            $history = &$histories[(int)date('d', strtotime($schedule->date))];
            $history['clock_in'] = $schedule->clock_in ? MyHelper::adjustTimezone($schedule->clock_in, $timeZone, 'H:i') : null;
            $history['clock_out'] = $schedule->clock_out ? MyHelper::adjustTimezone($schedule->clock_out, $timeZone, 'H:i') : null;
            $history['is_holiday'] = false;
            if ($schedule->is_overtime) {
                $history['breakdown'][] = [
                    'name' => 'Lembur',
                    'time_start' => MyHelper::adjustTimezone($schedule->time_start, $timeZone, 'H:i'),
                    'time_end' => MyHelper::adjustTimezone($schedule->time_end, $timeZone, 'H:i'),
                ];
            }
        }

        return MyHelper::checkGet([
            'histories' => array_values($histories)
        ]);
    }

    public function list(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_schedules', 'hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule', 'hairstylist_schedules.id_hairstylist_schedule')
            ->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date');
        $countTotal = null;
        $result->groupBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'user_hair_stylist_code',
                'fullname',
                'total_schedule',
                'total_ontime',
                'total_late',
                'total_absent',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('user_hair_stylist.id_user_hair_stylist, user_hair_stylist_code, fullname, count(user_hair_stylist.id_user_hair_stylist) as total_schedule, SUM(CASE WHEN hairstylist_attendances.is_on_time = 1 THEN 1 ELSE 0 END) as total_ontime, SUM(CASE WHEN hairstylist_attendances.is_on_time = 0 AND (hairstylist_attendances.clock_in IS NOT NULL OR hairstylist_attendances.clock_out IS NOT NULL) THEN 1 ELSE 0 END) as total_late, SUM(CASE WHEN (hairstylist_attendances.clock_in IS NULL and hairstylist_attendances.clock_out IS NULL) THEN 1 ELSE 0 END) as total_absent');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['fullname', 'user_hair_stylist_code', 'level', 'id_outlet'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('user_hair_stylist.id_outlet', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
    }


    public function detail(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_schedules', 'hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule', 'hairstylist_schedules.id_hairstylist_schedule')
            ->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->with(['outlet', 'attendance_logs' => function ($query) { $query->where('status', 'Approved')->selectRaw('*, null as photo_url');}]);
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetail($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'date',
                'shift',
                'clock_in',
                'clock_out',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('*, COALESCE(hairstylist_attendances.id_outlet, hairstylist_schedules.id_outlet, user_hair_stylist.id_outlet) AS id_outlet, (CASE WHEN (hairstylist_attendances.clock_in IS NULL AND hairstylist_attendances.clock_out IS NULL) THEN "Absent" WHEN is_on_time = 1 THEN "On Time" WHEN is_on_time = 0 THEN "Late" ELSE "" END) as status');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }
        foreach($result['data'] ?? [] as $r => $data){
            $outlet = Outlet::where('id_outlet',$data['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('cities.id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            if($timeZone == 7){
                $time_zone = 'WIB';
            }elseif($timeZone == 8){
                $time_zone = 'WITA';
            }elseif($timeZone == 9){
                $time_zone = 'WIT';
            }
            $result['data'][$r]['clock_in'] =  $data['clock_in'] ? MyHelper::adjustTimezone($data['clock_in'], $timeZone, 'H:i:s', true).' '. $time_zone : null;
            $result['data'][$r]['clock_out'] = $data['clock_out'] ? MyHelper::adjustTimezone($data['clock_out'], $timeZone, 'H:i:s', true).' '. $time_zone : null;
            $result['data'][$r]['time_start'] =  $data['time_start'] ? MyHelper::adjustTimezone($data['time_start'], $timeZone, 'H:i:s', true).' '. $time_zone : null;
            $result['data'][$r]['time_end'] = $data['time_end'] ? MyHelper::adjustTimezone($data['time_end'], $timeZone, 'H:i:s', true).' '. $time_zone : null;
            $result['data'][$r]['clock_in_requirement'] = $data['clock_in_requirement'] ? MyHelper::adjustTimezone($data['clock_in_requirement'], $timeZone, 'H:i:s', true).' '. $time_zone : null;
            $result['data'][$r]['clock_out_requirement'] =  $data['clock_out_requirement'] ? MyHelper::adjustTimezone($data['clock_out_requirement'], $timeZone, 'H:i:s', true).' '. $time_zone : null;

            foreach($data['attendance_logs'] ?? [] as $l => $log){
                $result['data'][$r]['attendance_logs'][$l]['datetime'] = $log['datetime'] ? MyHelper::adjustTimezone($log['datetime'], $timeZone, ' Y-m-d H:i:s', true) :  null;
                $result['data'][$r]['attendance_logs'][$l]['time_zone'] = $time_zone;
            }
        }

        return MyHelper::checkGet($result);
    }

    public function delete(Request $request)
    {
        $delete = HairstylistAttendance::where('id_hairstylist_attendance', $request->id_hairstylist_attendance)->delete();
        return MyHelper::checkDelete($delete);
    }

    public function filterListDetail($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['shift'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'attendance_status';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    switch ($rule[1]) {
                        case 'ontime':
                            $query2->$where('is_on_time', 1);
                            break;

                        case 'late':
                            $query2->$where('is_on_time', 0);
                            break;

                        case 'absent':
                            $query2->{$where . 'Null'}('is_on_time');
                            break;
                    }
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id_user_hair_stylist'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('user_hair_stylist.id_user_hair_stylist', $rul[0], $rul[1]);
            }
        }
    }

    public function listPending(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_attendances', 'hairstylist_attendances.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_attendance_logs', function($join) {
                $join->on('hairstylist_attendance_logs.id_hairstylist_attendance', 'hairstylist_attendances.id_hairstylist_attendance')
                    ->where('hairstylist_attendance_logs.status', 'Pending');
            });
        $countTotal = null;
        $result->groupBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListPending($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'user_hair_stylist_code',
                'fullname',
                'total_pending',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('user_hair_stylist.id_user_hair_stylist, user_hair_stylist_code, fullname, count(*) as total_pending');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterListPending($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['fullname', 'user_hair_stylist_code', 'level', 'id_outlet'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('user_hair_stylist.id_outlet', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
    }


    public function detailPending(Request $request)
    {
        $result = HairstylistAttendanceLog::selectRaw('*, null as photo_url')
            ->join('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_attendance', 'hairstylist_attendance_logs.id_hairstylist_attendance')
            ->where('hairstylist_attendance_logs.status', 'Pending')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule_date', 'hairstylist_attendances.id_hairstylist_schedule_date');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetailPending($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'datetime',
                'shift',
                'clock_in',
                'clock_out',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        // $result->selectRaw('*, ');
        $result->orderBy('hairstylist_attendance_logs.id_hairstylist_attendance_log');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            foreach ($result['data']??[] as $key=>$value){
                $idOutlet = UserHairStylist::where('id_user_hair_stylist', $value['id_user_hair_stylist'])->first()['id_outlet']??null;
                $timeZone = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'cities.id_province', 'provinces.id_province')
                ->where('id_outlet', $idOutlet)->first()['time_zone_utc']??null;
                if($timeZone == 7){
                    $time_zone = 'WIB';
                }elseif($timeZone == 8){
                    $time_zone = 'WITA';
                }elseif($timeZone == 9){
                    $time_zone = 'WIT';
                }
                $time = MyHelper::adjustTimezone($value['datetime'], $timeZone, 'H:i', true);
                $result['data'][$key]['datetime'] =date('Y-m-d', strtotime($value['datetime'])).' '.$time;
                $result['data'][$key]['clock_in_requirement'] = MyHelper::adjustTimezone($value['clock_in_requirement'], $timeZone, 'H:i', true);
                $result['data'][$key]['clock_out_requirement'] = MyHelper::adjustTimezone($value['clock_out_requirement'], $timeZone, 'H:i', true);
                $result['data'][$key]['time_start'] = MyHelper::adjustTimezone($value['time_start'], $timeZone, 'H:i', true);
                $result['data'][$key]['time_end'] = MyHelper::adjustTimezone($value['time_end'], $timeZone, 'H:i', true);
                $result['data'][$key]['timezone'] = $time_zone ?? null;
            }
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterListDetailPending($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['shift', 'type'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id_user_hair_stylist'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('hairstylist_attendances.id_user_hair_stylist', $rul[0], $rul[1]);
            }
        }
    }

    public function updatePending(Request $request)
    {
        $request->validate([
            'status' => 'string|in:Approved,Rejected',
        ]);
        $log = HairstylistAttendanceLog::find($request->id_hairstylist_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected pending attendance not found']
            ];
        }
        $log->update(['status' => $request->status]);
        $log->hairstylist_attendance->recalculate();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' pending attendance'
            ],
        ];
    }

    public function listRequest(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_attendance_requests', 'hairstylist_attendance_requests.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule_date', 'hairstylist_attendance_requests.id_hairstylist_schedule_date');
        $countTotal = null;
        $result->groupBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListRequest($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'user_hair_stylist_code',
                'fullname',
                'total_request',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('user_hair_stylist.id_user_hair_stylist, user_hair_stylist_code, fullname, count(*) as total_request');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterListRequest($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['fullname', 'user_hair_stylist_code', 'level', 'id_outlet'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('user_hair_stylist.id_outlet', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
    }


    public function detailRequest(Request $request)
    {
        $result = HairstylistAttendanceLog::selectRaw('*, null as photo_url')
            ->join('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_attendance', 'hairstylist_attendance_logs.id_hairstylist_attendance')
            ->where('hairstylist_attendance_logs.status', 'Request')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule_date', 'hairstylist_attendances.id_hairstylist_schedule_date');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetailRequest($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'datetime',
                'shift',
                'clock_in',
                'clock_out',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        // $result->selectRaw('*, ');
        $result->orderBy('hairstylist_attendance_logs.id_hairstylist_attendance_log');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterListDetailRequest($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['shift', 'type'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id_user_hair_stylist'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('hairstylist_attendances.id_user_hair_stylist', $rul[0], $rul[1]);
            }
        }
    }

    public function updateRequest(Request $request)
    {
        $request->validate([
            'status' => 'string|in:Approved,Rejected',
        ]);
        $log = HairstylistAttendanceLog::find($request->id_hairstylist_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected request attendance not found']
            ];
        }
        $log->update(['status' => $request->status]);
        $log->hairstylist_attendance->recalculate();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' request attendance'
            ],
        ];
    }

    public function cronLate()
    {
        $log = MyHelper::logCron('Cancel Transaction');
        try {
            HairstylistAttendance::where(function($query) {
                    $query->whereNull('clock_out')->orWhereNull('clock_in');
                })
                ->whereDate('attendance_date', '<', date('Y-m-d'))
                ->update(['is_on_time' => 0]);

            $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function setting(Request $request){
        $post = $request->json()->all();

        if(empty($post)){
            $clockin = Setting::where('key', 'clock_in_tolerance')->first()['value']??null;
            $clockout = Setting::where('key', 'clock_out_tolerance')->first()['value']??null;
            $radius = Setting::where('key', 'hairstylist_attendance_max_radius')->first()['value']??null;

            $result = [
                'clock_in_tolerance' => $clockin,
                'clock_out_tolerance' => $clockout,
                'hairstylist_attendance_max_radius' => $radius
            ];

            return response()->json(MyHelper::checkGet($result));
        }else{
            Setting::updateOrCreate(['key' => 'clock_in_tolerance'], ['value' => $post['clock_in_tolerance']]);
            Setting::updateOrCreate(['key' => 'clock_out_tolerance'], ['value' => $post['clock_out_tolerance']]);
            Setting::updateOrCreate(['key' => 'hairstylist_attendance_max_radius'], ['value' => $post['hairstylist_attendance_max_radius']]);

            return response()->json(MyHelper::checkUpdate(true));
        }
    }
}
