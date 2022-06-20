<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Holiday;
use App\Http\Models\Outlet;
use DB;
use function Clue\StreamFilter\fun;

class ApiHairStylistScheduleController extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

	public function outlet(Request $request)
	{
		$thisMonth = $request->month ?? date('m');
		$thisYear  = $request->year ?? date('Y');
		$firstDate = date('Y-m-d', strtotime(date($thisMonth.'-'.$thisMonth.'-01')));

		$schedules = HairstylistSchedule::join('user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
					->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
					->where([
						['hairstylist_schedules.id_outlet', $request->id_outlet],
						['hairstylist_schedule_dates.date', '>=', $firstDate]
					])
					->select(
						'user_hair_stylist.nickname',
						'user_hair_stylist.fullname',
						'user_hair_stylist.phone_number',
						'hairstylist_schedules.*',
						'hairstylist_schedule_dates.*'
					)
					->orderBy('date','desc')
					->get();

		$res = [];
		foreach ($schedules as $schedule) {
			$year   = date('Y', strtotime($schedule['date']));
			$month  = date('m', strtotime($schedule['date']));
			$date 	= date('j', strtotime($schedule['date']));
			$res[$year][$month][$date][] = $schedule;
		}

		return MyHelper::checkGet($res);
	}

	public function list(Request $request)
	{
        $post = $request->json()->all();
        $data = HairstylistSchedule::leftJoin('users as approver', 'approver.id', 'hairstylist_schedules.approve_by')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist')
        		->join('outlets', 'outlets.id_outlet', 'hairstylist_schedules.id_outlet')
                ->orderBy('request_at', 'desc');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('request_at', '>=', $start_date)->whereDate('request_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'nickname') {
                            if ($row['operator'] == '=') {
                                $data->where('nickname', $row['parameter']);
                            } else {
                                $data->where('nickname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'phone_number') {
                            if ($row['operator'] == '=') {
                                $data->where('phone_number', $row['parameter']);
                            } else {
                                $data->where('phone_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'fullname') {
                            if ($row['operator'] == '=') {
                                $data->where('fullname', $row['parameter']);
                            } else {
                                $data->where('fullname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'id_outlet') {
                            $data->where(function ($q) use($row){
                                $q->where('hairstylist_schedules.id_outlet', $row['operator'])
                                ->orWhereIn('hairstylist_schedules.id_user_hair_stylist', function($query) use($row){
                                    $query->select('hairstylist_attendances.id_user_hair_stylist')
                                        ->from('hairstylist_attendances')
                                        ->where('hairstylist_attendances.id_outlet', $row['operator']);
                                });
                            });
                        }

                        if ($row['subject'] == 'status') {
                        	switch ($row['operator']) {
                        		case 'Approved':
                        			$data->whereNotNull('approve_at');
                        			break;

                        		case 'Rejected':
                        			$data->where(function($q) {
                        				$q->whereNotNull('reject_at');
                        				$q->whereNull('approve_at');
                        			});
                        			break;
                        		
                        		default:
                        			$data->where(function($q) {
                        				$q->whereNull('reject_at');
                        				$q->whereNull('approve_at');
                        			});
                        			break;
                        	}
                        }

                        if ($row['subject'] == 'month') {
                            $data->where('schedule_month', $row['operator']);
                        }

                        if ($row['subject'] == 'year') {
                            $data->where('schedule_year', $row['operator']);
                        }
                    }
                }
            } else {
            	$data->where(function ($subquery) use ($post) {
            		foreach ($post['conditions'] as $row) {
            			if (isset($row['subject'])) {
            				if ($row['subject'] == 'nickname') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('nickname', $row['parameter']);
            					} else {
            						$subquery->orWhere('nickname', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'phone_number') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('phone_number', $row['parameter']);
            					} else {
            						$subquery->orWhere('phone_number', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'fullname') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('fullname', $row['parameter']);
            					} else {
            						$subquery->orWhere('fullname', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'id_outlet') {
                                $subquery->orWhere(function ($q) use($row){
                                    $q->where('hairstylist_schedules.id_outlet', $row['operator'])
                                        ->orWhereIn('hairstylist_schedules.id_user_hair_stylist', function($query) use($row){
                                            $query->select('hairstylist_attendances.id_user_hair_stylist')
                                                ->from('hairstylist_attendances')
                                                ->where('hairstylist_attendances.id_outlet', $row['operator']);
                                        });
                                });
            				}

            				if($row['subject'] == 'status') {
            					switch ($row['operator']) {
            						case 'Approved':
            							$subquery->orWhereNotNull('approve_at');
            							break;

        							case 'Rejected':
                                        $subquery->orWhere(function($q) {
	                        				$q->whereNotNull('reject_at');
	                        				$q->whereNull('approve_at');
	                        			});
	                        			break;

            						default:
            							$subquery->orWhere(function($q) {
            								$q->whereNull('reject_at');
            								$q->whereNull('approve_at');
            							});
            							break;
            					}
            				}

	                        if ($row['subject'] == 'month') {
                                $subquery->orWhere('schedule_month', $row['operator']);
	                        }

	                        if ($row['subject'] == 'year') {
                                $subquery->orWhere('schedule_year', $row['operator']);
	                        }
            			}
                    }
                });
            }
        }

        $data = $data->select(
		        	'hairstylist_schedules.*',
		        	'user_hair_stylist.*', 
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
		        	'approver.name as approve_by_name'
		        )->paginate(25)->toArray();

        foreach ($data['data']??[] as $key=>$value){
            $outlet = [];
            $outlet = HairstylistScheduleDate::join('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('hairstylist_schedule_dates.id_hairstylist_schedule', $value['id_hairstylist_schedule'])
                    ->whereNotIn('hairstylist_attendances.id_outlet', [$value['id_outlet']])
                    ->join('outlets', 'outlets.id_outlet', 'hairstylist_attendances.id_outlet')
                    ->groupBy('outlets.id_outlet')->select('outlets.outlet_name', 'outlets.outlet_code')->get()->toArray();
            $outlet[] = [
                'outlet_name' => $value['outlet_name'],
                'outlet_code' => $value['outlet_code']
            ];
            $data['data'][$key]['attendance_outlet'] = array_map("unserialize", array_unique(array_map("serialize", $outlet)));;
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function detail(Request $request) {
        $post = $request->json()->all();

        if (empty($post['id_hairstylist_schedule'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        $detail = HairstylistSchedule::join('outlets', 'outlets.id_outlet', 'hairstylist_schedules.id_outlet')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist')
        		->leftJoin('users as approver', 'approver.id', 'hairstylist_schedules.approve_by')
        		->leftJoin('users as last_update_user', 'last_update_user.id', 'hairstylist_schedules.last_updated_by')
                ->with([
                	'hairstylist_schedule_dates', 
                	'outlet.outlet_schedules.time_shift'
                ])
                ->where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])
                ->select(
		        	'hairstylist_schedules.*',
		        	'user_hair_stylist.*', 
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
		        	'approver.name as approve_by_name',
		        	'last_update_user.name as last_updated_by_name'
		        )
		        ->first();

		$ids = HairstylistAttendance::whereIn('id_hairstylist_schedule_date', $detail->hairstylist_schedule_dates->pluck('id_hairstylist_schedule_date'))->get()->pluck('id_outlet');

		$outlets = Outlet::whereIn('id_outlet', $ids)->orWhere('id_outlet', $detail->id_outlet)->get();

        if (!$detail) {
        	return MyHelper::checkGet($detail);
        }

        $listDate = MyHelper::getListDate($detail->schedule_month, $detail->schedule_year);
        $outletSchedule = [];
        foreach ($detail['outlet']['outlet_schedules'] as $s) {
        	$outletSchedule[$s['day']] = [
        		'is_closed' => $s['is_closed'],
        		'shift' => $s['time_shift']->pluck('shift')
        	];
        }

        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
	    			->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
	                ->where('id_outlet', $detail['id_outlet'])
	                ->whereMonth('date_holidays.date', $detail->schedule_month)
	                ->where(function($q) use ($detail) {
	                	$q->whereYear('date_holidays.date', $detail->schedule_year)
	                		->orWhere('yearly', '1');
	                })
	                ->get()
	                ->keyBy('date');

        $request->id_outlet = $detail->id_outlet;
        $request->month = $detail->schedule_month;
        $request->year = $detail->schedule_year;
        $allSchedule = $this->outlet($request)['result'] ?? [];

        $selfSchedule = [];
        foreach ($detail['hairstylist_schedule_dates'] as $key => $val) {
        	$date = date('Y-m-d', strtotime($val['date']));
        	$selfSchedule[$date] = $val['shift'];
        }

        $resDate = [];
        foreach ($listDate as $date) {
        	$day = MyHelper::indonesian_date_v2($date, 'l');
        	$day = str_replace('Jum\'at', 'Jumat', $day);
        	$y = date('Y', strtotime($date));
        	$m = date('m', strtotime($date));
        	$d = date('j', strtotime($date));

        	$isClosed = $outletSchedule[$day]['is_closed'] ?? '1';
        	if (isset($holidays[$date]) && isset($outletSchedule[$day])) {
        		$isClosed = '1';
        	}

        	$resDate[] = [
        		'date' => $date,
        		'day' => $day,
        		'outlet_holiday' => $holidays[$date]['holiday_name'] ?? null,
        		'selected_shift' => $selfSchedule[$date] ?? null,
        		'is_closed' => $isClosed,
        		'outlet_shift' => $outletSchedule[$day] ?? [],
        		'all_hs_schedule' => $allSchedule[$y][$m][$d] ?? []
        	];
        }

        $detail['attendance'] = HairstylistAttendance::whereIn('id_hairstylist_schedule_date', $detail->hairstylist_schedule_dates->pluck('id_hairstylist_schedule_date'))->whereDate('attendance_date', date('Y-m-d'))->whereNotNull('clock_in')->first();

        $res = [
        	'detail' => $detail,
        	'list_date' => $resDate,
        	'outlets' => $outlets,
        ];
        return MyHelper::checkGet($res);
    }

    public function update(Request $request) {
        $post = $request->json()->all();
        if (empty($post['id_hairstylist_schedule'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        if (isset($post['update_type'])) {
        	$autocrmTitle = null;
        	if (($post['update_type'] == 'reject')) {
        		$data = [
					'reject_at' => date('Y-m-d H:i:s')
				];
				$autocrmTitle = 'Reject Hairstylist Schedule';
        	} elseif (($post['update_type'] == 'approve')) {
	            $data = [
	            	'approve_by' => $request->user()->id,
	            	'approve_at' => date('Y-m-d H:i:s'),
					'reject_at' => null
	            ];
				$autocrmTitle = 'Approve Hairstylist Schedule';
        	}

        	$update = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->update($data);

        	if ($update && $autocrmTitle) {
				$schedule = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])
							->with('outlet', 'user_hair_stylist')->first();
	        	app($this->autocrm)->SendAutoCRM($autocrmTitle, $schedule['user_hair_stylist']['phone_number'] ?? null,
	                [
	                    "month" 		=> !empty($schedule['schedule_month']) ? date('F', mktime(0, 0, 0, $schedule['schedule_month'], 10)) : null,
	                    "year"  		=> (string) $schedule['schedule_year'] ?? null,
	                    'outlet_name'   => $schedule['outlet']['outlet_name'] ?? null
	                ], null, false, false, $recipient_type = 'hairstylist', null, true
	            );
        	}
        }

        $schedule = HairstylistScheduleDate::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->get();

        $oldData = [];
        foreach ($schedule as $val) {
        	$date = date('Y-m-j', strtotime($val['date']));
        	if (isset($oldData[$date]) && $oldData[$date] != $val['shift']) {
        		$oldData[$date] = [
        			'request_by' => $val['request_by'],
        			'created_at' => $val['created_at'],
        			'shift' => 'Full'
        		];
        	} else {
        		$oldData[$date] = [
        			'request_by' => $val['request_by'],
        			'created_at' => $val['created_at'],
        			'shift' => $val['shift']
        		];
        	}
        }
        
        $fixedSchedule = HairstylistScheduleDate::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->join('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')->select('hairstylist_schedule_dates.id_hairstylist_schedule_date', 'date')->get();
        $fixedScheduleDateId = $fixedSchedule->pluck('id_hairstylist_schedule_date');
        $fixedScheduleDate = $fixedSchedule->pluck('date')->map(function($item) {return date('Y-m-d', strtotime($item));});

        $newData = [];
        foreach ($post['schedule'] as $key => $val) {
        	if (empty($val)) {
        		continue;
        	}

        	if (in_array(date('Y-m-d', strtotime($key)), $fixedScheduleDate->toArray()) || date('Y-m-d', strtotime($key)) < date('Y-m-d')) {
        		continue;
        	}

        	$request_by = 'Admin';
        	$created_at = date('Y-m-d H:i:s');
        	$updated_at = date('Y-m-d H:i:s');
        	if (isset($oldData[$key]) && $oldData[$key]['shift'] == $val) {
        		$request_by = $oldData[$key]['request_by'];
        		$created_at = $oldData[$key]['created_at'];
        	}
        	if ($val == 'Full') {
        		$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => 'Morning',
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];

	        	$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => 'Tengah',
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];

	        	$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => 'Evening',
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];
        	} else {
	        	$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => $val,
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];
        	}
        }

        DB::beginTransaction();

        $update = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->update(['last_updated_by' => $request->user()->id]);
        $delete = HairstylistScheduleDate::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->whereDate('date', '>=', date('Y-m-d'))->whereNotIn('id_hairstylist_schedule_date', $fixedScheduleDateId)->delete();
        $save 	= HairstylistScheduleDate::insert($newData);
    	HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->first()->refreshTimeShift();

        if ($save) {
        	DB::commit();
        } else {
        	DB::rollback();
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function getScheduleYear()
    {
        $data = HairstylistSchedule::groupBy('schedule_year')->get()->pluck('schedule_year');

        return MyHelper::checkGet($data);
    }

	public function checkScheduleHS(){
        $log = MyHelper::logCron('Check Schedule Hair Stylist');
        try{
            DB::beginTransaction();

            $this_date = date('Y-m-d');
            $this_date = explode('-',$this_date);

            $data_hs = UserHairStylist::join('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')->get()->toArray();
            if($data_hs){
                foreach ($data_hs as $key => $hs) {
                    $sch = HairstylistSchedule::where('id_user_hair_stylist', $hs['id_user_hair_stylist'])->where('schedule_month', $this_date[1])->where('schedule_year', $this_date[0])->first();

                    if(!$sch){
                        $sch_before = HairstylistSchedule::where('id_user_hair_stylist', $hs['id_user_hair_stylist'])->where('schedule_month', $this_date[1]-1)->where('schedule_year', $this_date[0])->first();
                        if($sch_before){
                            
                            $check = HairstylistScheduleDate::where('id_hairstylist_schedule',$sch_before['id_hairstylist_schedule'])->whereMonth('date', $this_date[1])->get()->toArray();

                            if(!$check){
                                $month_before = $this_date[1]-1;
        
                                $schedule_before =  HairstylistScheduleDate::where('id_hairstylist_schedule',$sch_before['id_hairstylist_schedule'])->whereMonth('date', $month_before)->get()->toArray();

                                if($schedule_before){
                                    $schedule_month = $sch_before['schedule_month'] + 1;
                                    if($schedule_month > 12 ){
                                        $schedule_month = $schedule_month - 12;
                                        $schedule_year = $sch_before['schedule_year'] + 1;
                                    }else{
                                        $schedule_year = $sch_before['schedule_year'];
                                    }
        
                                    $array_hs = [
                                        "id_user_hair_stylist" => $hs['id_user_hair_stylist'],
                                        "id_outlet" => $hs['id_outlet'],
                                        "approve_by" => $sch_before['approve_by'],
                                        "last_updated_by" => $sch_before['last_updated_by'],
                                        "schedule_month" => $schedule_month,
                                        "schedule_year" => $schedule_year,
                                        "request_at" => date('Y-m-d H:i:s'), 
                                        "approve_at" => date('Y-m-d H:i:s'),
                                        "reject_at" => NULL
                                    ];

                                    $create_schedule = HairstylistSchedule::create($array_hs);
                                    if($create_schedule){
                                        foreach($schedule_before as $new){
                                            $date = explode('-',$new['date']);
                                            $date[1] = $schedule_month;
                                            $date[0] = $schedule_year;
                                            $date =  date('Y-m-d', strtotime(implode('-',$date)));

                                            if($new['is_overtime'] == 1){
                                                $day = date('D', strtotime($date));
                                                switch($day){
                                                    case 'Sun':
                                                        $day = "Minggu";
                                                    break;
                                            
                                                    case 'Mon':			
                                                        $day = "Senin";
                                                    break;
                                            
                                                    case 'Tue':
                                                        $day = "Selasa";
                                                    break;
                                            
                                                    case 'Wed':
                                                        $day = "Rabu";
                                                    break;
                                            
                                                    case 'Thu':
                                                        $day = "Kamis";
                                                    break;
                                            
                                                    case 'Fri':
                                                        $day = "Jumat";
                                                    break;
                                            
                                                    case 'Sat':
                                                        $day = "Sabtu";
                                                    break;
                                                    
                                                    default:
                                                        $day = "Undefined";		
                                                    break;
                                                }

                                                $get_original = OutletSchedule::join('outlet_time_shift','outlet_time_shift.id_outlet_schedule','=','outlet_schedules.id_outlet_schedule')
                                                                            ->where('outlet_schedules.id_outlet', $hs['id_outlet'])
                                                                            ->where('outlet_schedules.day', $day)
                                                                            ->where('outlet_time_shift.shift', $new['shift'])->first();
                                             
                                                $new['time_start'] = $get_original['shift_time_start'];
                                                $new['time_end'] = $get_original['shift_time_end'];
                                            }

                                            $create_schedule_date = HairstylistScheduleDate::create([
                                                'id_hairstylist_schedule' => $create_schedule['id_hairstylist_schedule'],
                                                'date' => $date,
                                                'shift' => $new['shift'],
                                                'request_by' => $new['request_by'],
                                                'is_overtime' =>  0,
                                                'time_start' => $new['time_start'],
                                                'time_end' => $new['time_end'],
                                            ]);
                                        }
                                    }else{
                                        DB::rollback();
                                    }
                                    
                                }
                            }
                        }
                    }
                }
            }
            DB::commit();

            $log->success('success');
            return response()->json(['status' => 'success']);

        }catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }    

	}

    public function create(Request $request){
        $post = $request->all();
        $this_year = date('Y');
        $this_month = date('m');

        if($post['year'] >= (int)$this_year){
            if($post['month'] >= $this_month){
                $check_schedule = HairstylistSchedule::where('id_user_hair_stylist',$post['id_hs'])->where('schedule_month',$post['month'])->where('schedule_year',$post['year'])->first();
                if(!$check_schedule){
                    $hs = UserHairStylist::where('id_user_hair_stylist',$post['id_hs'])->first();
                    $array_hs = [
                        "id_user_hair_stylist" => $post['id_hs'],
                        "id_outlet" => $hs['id_outlet'],
                        "approve_by" => auth()->user()->id,
                        "last_updated_by" => auth()->user()->id,
                        "schedule_month" => $post['month'],
                        "schedule_year" => $post['year'],
                        "request_at" => date('Y-m-d H:i:s'), 
                        "approve_at" => date('Y-m-d H:i:s'),
                        "reject_at" => NULL
                    ];
    
                    DB::beginTransaction();
                    $create_schedule = HairstylistSchedule::create($array_hs);
                    if(!$create_schedule){
                        DB::rollback();
                    }
                    DB::commit();
                    return response()->json([
                        'status' => 'success', 
                        'result' => $create_schedule
                    ]);
                }else{
                    return response()->json([
                        'status' => 'fail', 
                        'messages' => 'The Schedule for the selected month already exists'
                    ]);
                } 
            }else{
                return response()->json([
                    'status' => 'fail', 
                    'messages' => 'The Schedule month cant be smaller than this month'
                ]);
            }
        }else{
            return response()->json([
                'status' => 'fail', 
                'messages' => 'The Schedule year cant be smaller than this year'
            ]);
        }
    }
}
