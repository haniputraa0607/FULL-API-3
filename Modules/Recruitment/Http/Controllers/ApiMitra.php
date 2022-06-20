<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Product;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;
use Modules\Recruitment\Entities\HairstylistIncome;
use Modules\Recruitment\Entities\HairstylistAttendance;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;
use Modules\Recruitment\Entities\HairstylistOverTime;
use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;

class ApiMitra extends Controller
{
	public function __construct() {
		$this->product = "Modules\Product\Http\Controllers\ApiProductController";
		$this->announcement = "Modules\Recruitment\Http\Controllers\ApiAnnouncement";
		$this->outlet = "Modules\Outlet\Http\Controllers\ApiOutletController";
		$this->mitra_log_balance = "Modules\Recruitment\Http\Controllers\MitraLogBalance";
		if (\Module::collections()->has('Autocrm')) {
			$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		}
	}

	public function splash(Request $request){
		$getSetting = Setting::whereIn('key',[
			'default_splash_screen_mitra_apps', 
			'default_splash_screen_mitra_apps_duration'
		])->get()->keyBy('key');

		$splash = $getSetting['default_splash_screen_mitra_apps']['value'] ?? null;
		$duration = $getSetting['default_splash_screen_mitra_apps_duration']['value'] ?? 5;

		if (!empty($splash)) {
			$splash = config('url.storage_url_api').$splash;
		} else {
			$splash = null;
		}
		
		$ext = explode('.', $splash);
		$result = [
			'status' => 'success',
			'result' => [
				'splash_screen_url' => $splash."?update=".time(),
				'splash_screen_duration' => $duration,
				'splash_screen_ext' => '.'.end($ext)
			]
		];
		return $result;
	}

	public function schedule(Request $request)
	{
		$user = $request->user();

		$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
		if (!$outlet) {
			return [
				'status' => 'fail',
				'messages' => ['Outlet tidak ditemukan']
			];
		}
		$thisMonth = $request->month ?? date('n');
		$thisYear  = $request->year  ?? date('Y');
		$date = $thisYear . '-' . $thisMonth . '-01';
		$end  = $thisYear . '-' . $thisMonth . '-' . date('t', strtotime($date));

		$resDate = [];
		$listDate = [];
		while (strtotime($date) <= strtotime($end)) {
			$listDate[] = [
				'date' => date('Y-m-d', strtotime($date)),
				'day'  => date('l', strtotime($date))
			];

			$tempDay = MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($date)), 'l');
			$tempDay = str_replace('Jum\'at', 'Jumat', $tempDay);
			$resDate[] = [
				'date'	=> date('Y-m-d', strtotime($date)),
				'day'	=> $tempDay,
				'date_string'	=> MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($date)), 'D  d/m')
			];
			$date = date("Y-m-d", strtotime("+1 day", strtotime($date)));
		}

		$hairstylists = UserHairStylist::where('id_outlet', $user->id_outlet)
		->where('user_hair_stylist_status', 'Active')
		->with([
			'hairstylist_schedules' => function($q) use ($thisMonth, $thisYear, $user){
				$q->where([
					['schedule_month', $thisMonth],
					['schedule_year', $thisYear],
					['id_outlet', $user->id_outlet],
				]);
			},
			'hairstylist_schedules.hairstylist_schedule_dates' => function($q) {
				$q->orderBy('date','asc');
			}
		])
		->get();

		$resHairstylist = [];
		foreach ($hairstylists as $hs) {

			$schedule = $hs['hairstylist_schedules'][0] ?? null;
			$schedule['status'] = $schedule['approve_at'] ? 'approved' : ($schedule['reject_at'] ? 'rejected' : 'pending');
			$schedule_dates = $schedule['hairstylist_schedule_dates'] ?? [];

			$tmpListDate = [];
			foreach ($schedule_dates as $val) {
				$date = date('Y-m-d', strtotime($val['date']));
				$tmpListDate[$date] = $val;
			}
			
			$tmpShift = [];
			foreach ($listDate as $val) {
				$date = date('d', strtotime($val['date']));
				$shift = 0;
				if (!empty($tmpListDate[$val['date']]['shift'])) {
					$shift = $tmpListDate[$val['date']]['shift'] == 'Morning' ? 1 : ($tmpListDate[$val['date']]['shift'] == 'Middle' ? 2 : 3);
				}
				$tmpShift[] = $shift;
			}

			$resHairstylist[] = [
				'id_user_hair_stylist' => $hs['id_user_hair_stylist'],
				'nickname' => $hs['nickname'],
				'fullname' => $hs['fullname'],
				'shift' => $tmpShift
			];
		}

		$outletSchedule = OutletSchedule::where('id_outlet', $user->id_outlet)->with('time_shift')->get();
		$arrShift = ['Morning' => 1, 'Middle' => 2, 'Evening' => 3];
		$shiftInfo = [];
		foreach ($outletSchedule as $sch) {
			$shiftInfo[$sch['day']] = [];
			foreach ($sch['time_shift'] as $shift) {
				$timeStart 	= date('H:i', strtotime($shift['shift_time_start']));
				$timeEnd 	= date('H:i', strtotime($shift['shift_time_end']));
				$shiftInfo[$sch['day']][] = [
					'shift' => $shift['shift'],
					'value' => $arrShift[$shift['shift']],
					'time' => $timeStart . ' - ' . $timeEnd
				];
			}
		}

		$monthInfo = [
			'prev_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01'))
			],
			'this_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime($thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime($thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime($thisYear . '-' . $thisMonth . '-01'))
			],
			'next_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01'))
			],
			'create_schedule' => null
		];

		if (strtotime($thisYear . '-' . $thisMonth . '-01') == strtotime(date('Y-n-01'))) {
			$monthInfo['next_month'] = null;
		}

		if ($user->level == 'Supervisor') {
			$monthInfo['create_schedule'] = [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('+1 Month ' . date('Y-m-01'))), 'F Y'),
				'month' => date('m', strtotime('+1 Month ' . date('Y-m-01'))),
				'year' => date('Y', strtotime('+1 Month ' . date('Y-m-01')))
			];
		}
		
		$res = [
			'id_outlet' => $outlet['id_outlet'],
			'outlet_name' => $outlet['outlet_name'],
			'month' => $monthInfo,
			'shift_info' => $shiftInfo,
			'list_date' => $resDate,
			'list_hairstylist' => $resHairstylist
		];
		return MyHelper::checkGet($res);
	}

	public function createSchedule(ScheduleCreateRequest $request)
	{
		$user = $request->user();
		$post = $request->json()->all();

		if ($user->level != 'Supervisor') {
			return [
				'status' => 'fail',
				'messages' => ['Jadwal hanya dapat dibuat oleh Hairstylist dengan level Supervisor']
			];
		}

		$thisMonth = $request->month ?? date('n');
		$thisYear  = $request->year  ?? date('Y');
		$date = $thisYear . '-' . $thisMonth . '-01';
		$end  = $thisYear . '-' . $thisMonth . '-' . date('t', strtotime($date));

		$listDate = [];
		while (strtotime($date) <= strtotime($end)) {
			$listDate[] = [
				'date' => date('Y-m-d', strtotime($date)),
				'day'  => date('l', strtotime($date))
			];

			$date = date("Y-m-d", strtotime("+1 day", strtotime($date)));
		}

		$hairstylists = UserHairStylist::where('id_outlet', $user->id_outlet)
		->where('user_hair_stylist_status', 'Active')
		->with([
			'hairstylist_schedules' => function($q) use ($request, $user){
				$q->where([
					['schedule_month', $request->month],
					['schedule_year', $request->year],
					['id_outlet', $user->id_outlet],
				]);
			},
			'hairstylist_schedules.hairstylist_schedule_dates' => function($q) {
				$q->orderBy('date','asc');
			}
		])
		->get();

		$newSchedules = [];
		foreach ($post['schedule'] ?? [] as $val) {
			$newSchedules[$val['id_user_hair_stylist']] = $val['shift'];
		}

		DB::beginTransaction();
		foreach ($hairstylists as $hs) {
			$newSchedule = $newSchedules[$hs['id_user_hair_stylist']] ?? [];
			if (empty($newSchedule)) {
				continue;
			}

			$schedule = $hs['hairstylist_schedules'][0] ?? null;
			$schedule_dates = $schedule['hairstylist_schedule_dates'] ?? [];
			if (!is_array($schedule_dates)) {
				$schedule_dates = $schedule_dates->toArray();
			}

			$tmpListDate = [];
			foreach ($schedule_dates as $val) {
				$date = date('Y-m-d', strtotime($val['date']));
				$tmpListDate[$date] = $val;
			}
			
			$oldSchedule = [];
			foreach ($listDate as $val) {
				$date = date('d', strtotime($val['date']));
				$shift = 0;
				if (!empty($tmpListDate[$val['date']]['shift'])) {
					$shift = $tmpListDate[$val['date']]['shift'] == 'Morning' ? 1 : 2;
				}
				$oldSchedule[] = $shift;
			}

			if ($oldSchedule == $newSchedule) {
				continue;
			}

			if (!$schedule) {
				$schedule = HairstylistSchedule::create([
					'id_user_hair_stylist' 	=> $hs->id_user_hair_stylist,
					'id_outlet' 			=> $hs->id_outlet,
					'schedule_month' 		=> $request->month,
					'schedule_year' 		=> $request->year,
					'request_at' 			=> date('Y-m-d H:i:s')
				]);
			}

			HairstylistScheduleDate::where('id_hairstylist_schedule', $schedule->id_hairstylist_schedule)->delete();
			$schedule->update([
				'approve_at' 		=> null,
				'approve_by' 		=> null,
				'reject_at' 		=> null,
				'last_updated_by' 	=> null
			]);

			$insertData = [];
			$request_by = 'Hairstylist';
			$created_at = date('Y-m-d H:i:s');
			$updated_at = date('Y-m-d H:i:s');

			$arrShift = [1 => 'Morning', 2 => 'Middle', 3 => 'Evening'];
			foreach ($newSchedule as $key => $val) {
				if (empty($val) || empty($arrShift[$val]) || empty($listDate[$key]['date'])) {
					continue;
				}

				$insertData[] = [
					'id_hairstylist_schedule' => $schedule->id_hairstylist_schedule,
					'date' => $listDate[$key]['date'],
					'shift' => $arrShift[$val],
					'request_by' => $request_by,
					'created_at' => $created_at,
					'updated_at' => $updated_at
				];
			}

			$insert = HairstylistScheduleDate::insert($insertData);
			$schedule->refreshTimeShift();

			if (!$insert) {
				DB::rollback();
				return [
					'status' => 'fail',
					'messages' => ['Gagal membuat jadwal']
				];
			}
		}

		DB::commit();
		return ['status' => 'success'];
	}

	public function announcementList(Request $request)
	{
		$user = $request->user();
		$today = date('Y-m-d h:i:s');
		$anns = HairstylistAnnouncement::select('id_hairstylist_announcement', 'date_start as date', 'content')
		->with('hairstylist_announcement_rule_parents.rules')
		->whereDate('date_start','<=',$today)
		->whereDate('date_end','>',$today)
		->get()
		->toArray();

		$res = [];
		foreach ($anns as $key => $ann) {
			$cons = array();
			$cons['subject'] = 'phone_number';
			$cons['operator'] = '=';
			$cons['parameter'] = $user['phone_number'];

			array_push($ann['hairstylist_announcement_rule_parents'], ['rule' => 'and', 'rule_next' => 'and', 'rules' => [$cons]]);
			$users = app($this->announcement)->hairstylistFilter($ann['hairstylist_announcement_rule_parents']);

			if (empty($users['status']) || $users['status'] != 'success') {
				continue;
			}

			$res[] = [
				'id_hairstylist_announcement' => $ann['id_hairstylist_announcement'],
				'date' => $ann['date'],
				'date_indo' => MyHelper::indonesian_date_v2($ann['date'], 'd F Y'),
				'content' => $ann['content']
			];
		}

		return [
			'status' => 'success',
			'result' => $res
		];
	}

	public function home(Request $request)
	{
		$user = $request->user();
		$this->setTimezone();
		$today = date('Y-m-d H:i:s');

		$user->load('outlet.brands');
		$outlet = [
			'id_outlet' => $user['outlet']['id_outlet'],
			'outlet_code' => $user['outlet']['outlet_code'],
			'outlet_name' => $user['outlet']['outlet_name'],
			'outlet_latitude' => $user['outlet']['outlet_latitude'],
			'outlet_longitude' => $user['outlet']['outlet_longitude']
		];

		$brand = [
			'id_brand' => $user['outlet']['brands'][0]['id_brand'],
			'brand_code' => $user['outlet']['brands'][0]['code_brand'],
			'brand_name' => $user['outlet']['brands'][0]['name_brand'],
			'brand_logo' => $user['outlet']['brands'][0]['logo_brand']
		];

		$level = $user['level'];
		$level = ($level == 'Hairstylist') ? 'Mitra' : (($level == 'Supervisor') ? 'SPV' : null);

		$res = [
			'id_user_hair_stylist' => $user['id_user_hair_stylist'],
			'user_hair_stylist_code' => $user['user_hair_stylist_code'],
			'nickname' => $user['nickname'],
			'fullname' => $user['fullname'],
			'name' => $level . ' ' . $user['fullname'],
			'email' => $user['email'],
			'phone_number' => $user['phone_number'],
			'level' => $user['level'],
			'gender' => $user['gender'],
			'recent_address' => $user['recent_address'],
			'total_rating' => $user['total_rating'],
			'total_balance' => $user['total_balance'],
			'today' => $today,
			'outlet' => $outlet,
			'brand' => $brand,
			'outlet_service' => $this->outletServiceScheduleStatus($user->id_user_hair_stylist),
			'home_service' => $this->homeServiceScheduleStatus($user->id_user_hair_stylist, $today),
			'has_otw_home_service' => TransactionHomeService::where(['id_user_hair_stylist' => $user->id_user_hair_stylist, 'status' => 'On The Way'])->exists() ? 1 : 0,
		];

		if(!empty($request->latitude) && !empty($request->longitude)){
			UserHairStylist::where('id_user_hair_stylist', $user['id_user_hair_stylist'])->update([
				'latitude' => $request->latitude,
				'longitude' => $request->longitude
			]);
		}

		if ($request->device_id) {
			$user->devices()->updateOrCreate([
				'device_id' => $request->device_id
			], [
				'device_type' => $request->device_type,
				'device_token' => $request->device_token,
			]);
		}

		return MyHelper::checkGet($res);
	}

	public function logout(Request $request)
	{
		$user = $request->user();
		$user->devices()->where('device_id', $request->device_id)->delete();
		return [
			'status' => 'success'
		];
	}

	public function outletServiceScheduleStatus($id_user_hair_stylist, $date = null)
	{
		$today = $date ?? date('Y-m-d H:i:s');
		$curTime = date('H:i:s', strtotime($today));
		$day = MyHelper::indonesian_date_v2($date, 'l');
		$day = str_replace('Jum\'at', 'Jumat', $day);
		$status = [
			'is_available' => 0,
			'is_active' => 0,
			'messages' => []
		];
        
		$hs = UserHairStylist::find($id_user_hair_stylist);
		$outletSchedule = OutletSchedule::where('id_outlet', $hs->id_outlet)->where('day', $day)->first();
		if (!$outletSchedule) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet tidak memiliki jadwal buka hari ini.";
			return $status;
		}

		if ($outletSchedule->is_closed) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet tutup.";
			return $status;
		}

		$isHoliday = app($this->outlet)->isHoliday($hs->id_outlet);
		if ($isHoliday['status']) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet libur \" " . $isHoliday['holiday'] . "\".";
			return $status;
		}

		$mitraSchedule = HairstylistScheduleDate::join('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
		->whereNotNull('approve_at')->where('id_user_hair_stylist', $id_user_hair_stylist)
		->whereDate('date', date('Y-m-d', strtotime($today)))
		->first();

		if (!$mitraSchedule) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal shift pada hari.";
			return $status;
		}
		$overtime = HairstylistOverTime::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
		->wheredate('date', date('Y-m-d'))
        ->whereNotNull('reject_at')
		->first();
		$outletShift = OutletTimeShift::where('id_outlet_schedule', $outletSchedule->id_outlet_schedule)
		->where(function($q) use ($curTime) {
			$q->where(function($q2) use ($curTime) {
				$q2->whereColumn('shift_time_start', '<', 'shift_time_end')
				->where('shift_time_start', '<', $curTime)
				->where('shift_time_end', '>', $curTime);
			})->orWhere(function($q2) use ($curTime) {
				$q2->whereColumn('shift_time_start', '>', 'shift_time_end')
				->where(function($q3) use ($curTime) {
					$q3->where('shift_time_start', '<', $curTime)
					->orWhere('shift_time_end', '>', $curTime);	
				});
			});
		})
		->where('shift', $mitraSchedule->shift)
		->first()['shift'] ?? null;
       
        $attendance = HairstylistAttendance::where('id_hairstylist_schedule_date', $mitraSchedule->id_hairstylist_schedule_date)
        ->where('id_user_hair_stylist', $id_user_hair_stylist)
        ->whereDate('attendance_date', date('Y-m-d', strtotime($today)))
        ->first();
        $clock_in = $attendance->clock_in ?? null;
        $clock_out = $attendance->clock_out ?? null;
		
        if($mitraSchedule){
            $start = date('Y-m-d',strtotime($mitraSchedule->date))." ".$mitraSchedule->time_start;
            $end = date('Y-m-d',strtotime($mitraSchedule->date))." ".$mitraSchedule->time_end;
            $now = date('Y-m-d H:i:s');

            if($start <= $now && $end >= $now && !$clock_in){
				if($attendance){
					$pending = HairstylistAttendanceLog::where('id_hairstylist_attendance', $attendance['id_hairstylist_attendance'])->first();
					if($pending){
						if($pending['status'] == 'Pending'){
							$status['messages'][] = "Mohon menunggu absensi disetujui terlebih dahulu. ";
							return $status;
						}
					}
				}
                $status['messages'][] = "Silakan lakukan absensi terlebih dahulu untuk memulai layanan outlet. ";
				return $status;
            }elseif(($start > $now || $end < $now) && !$clock_in){
                $status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal shift pada hari dan jam ini.";
                return $status;
            }elseif(($start > $now || $end < $now) && $clock_in && $overtime){
                $status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal shift pada hari dan jam ini.";
				return $status;
            }
		}elseif (!$mitraSchedule) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal layanan outlet hari ini.";
			return $status;
		}elseif ($mitraSchedule->shift != $outletShift) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal layanan outlet pada jam ini.";
			return $status;
		}

		$isClockIn = true;
		if(!$isClockIn){
			$status['is_available'] = 1;
			$status['messages'][] = 'Silakan lakukan absensi terlebih dahulu untuk memulai layanan outlet';
			return $status;
		}

		$status['is_available'] = 1;
		$status['is_active'] = 1;
		return $status;
	}

	public function homeServiceScheduleStatus($id_user_hair_stylist, $date = null)
	{

		$isHomeServiceStart = UserHairStylist::find($id_user_hair_stylist)->home_service_status;
		$status = [
			'is_available' => 0,
			'is_active' => $isHomeServiceStart,
			'messages' => []
		];

		$outletService = $this->outletServiceScheduleStatus($id_user_hair_stylist, $date);
		
		if ($outletService['is_available']) {
			$status['messages'][] = "Layanan tidak bisa diaktifkan.\n karena layanan outlet Anda sedang aktif.";
			$status['is_active'] = 0;
			return $status;
		}
		
		$status['is_available'] = 1;
		return $status;
	}

	public function ratingSummary(Request $request)
	{
		$user = $request->user();
		$ratingHs = UserHairStylist::where('user_hair_stylist.id_user_hair_stylist',$user->id_user_hair_stylist)
		->leftJoin('user_ratings','user_ratings.id_user_hair_stylist','user_hair_stylist.id_user_hair_stylist')
		->select(
			DB::raw('
				user_hair_stylist.id_user_hair_stylist,
				user_hair_stylist.phone_number,
				user_hair_stylist.nickname,
				user_hair_stylist.fullname,
				user_hair_stylist.level,
				user_hair_stylist.total_rating,
				COUNT(DISTINCT user_ratings.id_user) as total_customer
				')
		)
		->first();

		$summary = UserRatingSummary::where('id_user_hair_stylist', $user->id_user_hair_stylist)->get();
		$summaryRating = [];
		$summaryOption = [];
		foreach ($summary as $val) {
			if ($val['summary_type'] == 'rating_value') {
				$summaryRating[$val['key']] = $val['value'];
			} else {
				$summaryOption[$val['key']] = $val['value'];
			}
		}

		$settingOptions = RatingOption::select('star','question','options')->where('rating_target', 'hairstylist')->get();
		$options = [];
		foreach ($settingOptions as $val) {
			$temp = explode(',', $val['options']);
			$options = array_merge($options, $temp);
		}

		$options = array_keys(array_flip($options));
		$resOption = [];
		foreach ($options as $val) {
			$resOption[] = [
				"name" => $val,
				"value" => $summaryOption[$val] ?? 0
			];
		}

		$level = $ratingHs['level'] ?? null;
		$level = ($level == 'Hairstylist') ? 'Mitra' : (($level == 'Supervisor') ? 'SPV' : null);
		$res = [
			'nickname' => $ratingHs['nickname'] ?? null,
			'fullname' => $ratingHs['fullname'] ?? null,
			'name' => $level . ' ' . $ratingHs['fullname'] ?? null,
			'phone_number' => $ratingHs['phone_number'] ?? null,
			'level' => $ratingHs['level'] ?? null,
			'total_customer' => (int) ($ratingHs['total_customer'] ?? null),
			'total_rating' => (float) ($ratingHs['total_rating'] ?? null),
			'rating_value' => [
				'5' => (int) ($summaryRating['5'] ?? null),
				'4' => (int) ($summaryRating['4'] ?? null),
				'3' => (int) ($summaryRating['3'] ?? null),
				'2' => (int) ($summaryRating['2'] ?? null),
				'1' => (int) ($summaryRating['1'] ?? null)
			],
			'rating_option' => $resOption
		];
		
		return MyHelper::checkGet($res);
	}

	public function ratingComment(Request $request)
	{
		$user = $request->user();
		$comment = UserRating::where('user_ratings.id_user_hair_stylist', $user->id_user_hair_stylist)
		->leftJoin('transaction_product_services','user_ratings.id_transaction_product_service','transaction_product_services.id_transaction_product_service')
		->whereNotNull('suggestion')
		->where('suggestion', '!=', "")
		->select(
			'transaction_product_services.order_id',
			'user_ratings.id_user_rating',
			'user_ratings.suggestion',
			'user_ratings.created_at'
		)
		->paginate($request->per_page ?? 10)
		->toArray();

		$resData = [];
		foreach ($comment['data'] ?? [] as $val) {
			$val['created_at_indo'] = MyHelper::dateFormatInd($val['created_at'], true, false);
			$resData[] = $val;
		}

		$comment['data'] = $resData;

		return MyHelper::checkGet($comment);
	}

	public function getOutletShift($id_outlet, $dateTime = null, $array = false, $id_user_hair_stylist = null)
	{
		$res = null;
		$outlet = Outlet::find($id_outlet);
		if (!$outlet) {
			return $res;
		}

		if (!$outlet->city) {
			throw new \Exception('Incomplete Outlet Data. Contact CS');
		}

		$timezone = $outlet->city->province->time_zone_utc;
		$dateTime = $dateTime ?? date('Y-m-d H:i:s');
		$curTime = date('H:i:s', strtotime($dateTime));
		$day = MyHelper::indonesian_date_v2($dateTime, 'l');
		$day = str_replace('Jum\'at', 'Jumat', $day);

		$outletSchedule = OutletSchedule::where('id_outlet', $id_outlet)->where('day', $day)->first();
		if (!$outletSchedule || $outletSchedule->is_closed) {
			return $res;
		}

		$isHoliday = app($this->outlet)->isHoliday($id_outlet);
		if ($isHoliday['status']) {
			return $res;
		}

		$outletShift = OutletTimeShift::where('id_outlet_schedule', $outletSchedule->id_outlet_schedule)
		->where(function($q) use ($curTime) {
			$q->where(function($q2) use ($curTime) {
				$q2->whereColumn('shift_time_start', '<', 'shift_time_end')
				->where('shift_time_start', '<', $curTime)
				->where('shift_time_end', '>', $curTime);
			})->orWhere(function($q2) use ($curTime) {
				$q2->whereColumn('shift_time_start', '>', 'shift_time_end')
				->where(function($q3) use ($curTime) {
					$q3->where('shift_time_start', '<', $curTime)
					->orWhere('shift_time_end', '>', $curTime);	
				});
			});
		})
		->{$array ? 'get' : 'first'}();

		if ($array) {
			return $outletShift->pluck('shift');
		}

		if (!$outletShift) {
			return $res;
		}

		$overtime = HairStylistOvertime::where('id_user_hair_stylist',$id_user_hair_stylist)->where('id_outlet', $id_outlet)->whereNotNull('approve_by')->whereNull('reject_at')->whereDate('date', date('Y-m-d', strtotime($dateTime)))->first();
		if($overtime){
			$shift = HairstylistSchedule::join(
				'hairstylist_schedule_dates', 
				'hairstylist_schedules.id_hairstylist_schedule', 
				'hairstylist_schedule_dates.id_hairstylist_schedule'
			)
			->where('id_user_hair_stylist', $id_user_hair_stylist)
			->where('date', date('Y-m-d'))
			->where('is_overtime', 1)
			->first();

			return $shift['shift'] ?? $res;

		}

		return $outletShift['shift'] ?? $res;
	}

	public function setTimezone()
	{
		if (!request()->user()->outlet) {
			return MyHelper::setTimezone(7);
		}
		if (!request()->user()->outlet->city) {
			throw new \Exception('Incomplete Outlet Data. Contact CS');
		}

		return MyHelper::setTimezone(request()->user()->outlet->city->province->time_zone_utc);
	}

	public function convertTimezoneMitra($date = null, $format = 'Y-m-d H:i:s')
	{
		$timestamp = $date ? strtotime($date) : time();
		$arrTz = [7 => 'Asia/Jakarta', 8 => 'Asia/Ujung_Pandang', 9 => 'Asia/Jayapura'];

		$utc = request()->user()->outlet ? request()->user()->outlet->city->province->time_zone_utc : 7;
		$tz = $arrTz[$utc] ?? 'Asia/Jakarta';

		$dt = new DateTime();
		$dt->setTimezone(new DateTimeZone($tz));
		$dt->setTimestamp($timestamp);
		
		return $dt->format($format);

	}

	public function getTodayShift($id_user_hair_stylist)
	{
		$todayShift = null;
		$hs = UserHairStylist::find($id_user_hair_stylist);

		if (!$hs) {
			return  $todayShift;
		}

		$shift = $this->getOutletShift($hs->id_outlet, null, true, $id_user_hair_stylist);
        if(empty($shift)){
            $shift = [];
        }
		$todayShift = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
		->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
		->where('date', date('Y-m-d'))
		->whereIn('shift', $shift)
		->first();

		return $todayShift;
	}

	public function balanceDetail(Request $request){
		$user = $request->user();
		$outletName = Outlet::where('id_outlet', $user->id_outlet)->first()['outlet_name']??'';

		$dataMitra = [
			'id_user_hair_stylist' => $user->id_user_hair_stylist,
			'id_mitra' => $user->user_hair_stylist_code,
			'name' => $user->fullname,
			'outlet_name' => $outletName,
			'current_balance' => $user->total_balance,
			'currency' => 'Rp'
		];

		return ['status' => 'success', 'result' => $dataMitra];
	}

	public function balanceHistory(Request $request){
		$user = $request->user();
		$history = HairstylistLogBalance::leftJoin('transactions', 'hairstylist_log_balances.id_reference', 'transactions.id_transaction')
		->leftJoin('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->select('hairstylist_log_balances.id_hairstylist_log_balance', 'hairstylist_log_balances.balance', 'hairstylist_log_balances.source',
			'transactions.transaction_receipt_number', 'outlets.outlet_name')
		->get()->toArray();

		return ['status' => 'success', 'result' => $history];
	}

	public function transferCashDetail(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}
		$date = date('Y-m-d', strtotime($post['date']));

		$listTransaction = Transaction::join('hairstylist_log_balances', 'hairstylist_log_balances.id_reference', 'transactions.id_transaction')
		->whereDate('hairstylist_log_balances.created_at', $date)
		->where('source', 'Receive Payment')
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->where('transfer_status', 0)
		->where('id_outlet', $user->id_outlet)
		->select('hairstylist_log_balances.created_at as date_receive_cash', 'transactions.id_transaction', 'transactions.transaction_receipt_number',
			'hairstylist_log_balances.*', 'id_user')
		->with('user')->get()->toArray();

		$res = [];
		foreach ($listTransaction as $transaction){
			$products = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
			->where('id_transaction', $transaction['id_transaction'])->pluck('product_name')->toArray();

			$productName = $products[0].(count($products) > 1?' + '.(count($products)-1).' lainnya':'');
			$res[] = [
				'id_transaction' => $transaction['id_transaction'],
				'time' => date('H:i', strtotime($transaction['date_receive_cash'])),
				'customer_name' => $transaction['user']['name'],
				'transaction_receipt_number' => $transaction['transaction_receipt_number'],
				'transaction_grandtotal' => $transaction['balance'],
				'product' => $productName,
				'currency' => 'Rp'
			];
		}

		return ['status' => 'success', 'result' => $res];
	}

	public function transferCashCreate(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}
		$date = date('Y-m-d', strtotime($post['date']));

		$listCash = HairstylistLogBalance::join('transactions', 'hairstylist_log_balances.id_reference', 'transactions.id_transaction')
		->whereDate('hairstylist_log_balances.created_at', $date)
		->where('source', 'Receive Payment')
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->where('transfer_status', 0)
		->where('id_outlet', $user->id_outlet)
		->select('id_hairstylist_log_balance', 'balance', 'id_reference', 'id_user')->get()->toArray();

		$idTransaction = array_column($listCash, 'id_reference');
		$idLogBalance = array_column($listCash, 'id_hairstylist_log_balance');
		$totalWillTransfer = array_column($listCash, 'balance');
		$totalWillTransfer = array_sum($totalWillTransfer);
		if(empty($totalWillTransfer)){
			return ['status' => 'fail', 'messages' => ['All cash already transfer']];
		}

		$update = HairstylistLogBalance::whereIn('id_hairstylist_log_balance', $idLogBalance)->update(['transfer_status' => 1]);
		if($update){
			$transferPayment = OutletCash::create([
				'id_user_hair_stylist' => $user->id_user_hair_stylist,
				'id_outlet' => $user->id_outlet,
				'outlet_cash_type' => 'Transfer To Supervisor',
				'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
				'outlet_cash_amount' => abs($totalWillTransfer)
			]);
			if($transferPayment){
				$update = TransactionPaymentCash::whereIn('id_transaction', $idTransaction)->update(['id_outlet_cash' => $transferPayment['id_outlet_cash']]);

				if($update){
					$dt = [
						'id_user_hair_stylist'    => $user->id_user_hair_stylist,
						'balance'                 => -$totalWillTransfer,
						'source'                  => 'Transfer To Supervisor',
						'id_reference'            => $transferPayment['id_outlet_cash']
					];

					$update = app($this->mitra_log_balance)->insertLogBalance($dt);

					if($user->level == 'Supervisor'){
						$update = OutletCash::where('id_outlet_cash', $transferPayment['id_outlet_cash'])
						->update(['outlet_cash_status' => 'Confirm', 'confirm_at' => date('Y-m-d H:i:s'), 'confirm_by' => $user->id_user_hair_stylist]);
						if($update){
							$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
							$update = Outlet::where('id_outlet', $user->id_outlet)->update(['total_current_cash' => $outlet['total_current_cash'] + $transferPayment['outlet_cash_amount']]);
						}
					}
				}
			}else{
				$update = false;
			}
		}

		return MyHelper::checkUpdate($update);
	}

	public function transferCashHistory(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['month']) && empty($post['year'])){
			return ['status' => 'fail', 'messages' => ['Month and Year can not be empty']];
		}

		$list = OutletCash::whereYear('created_at', '=', $post['year'])
		->whereMonth('created_at', '=', $post['month'])
		->where('id_outlet', $user->id_outlet)->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->get()->toArray();

		$res = [];
		foreach ($list as $value){
			$date = MyHelper::dateFormatInd(date('Y-m-d', strtotime($value['created_at'])), false, false);
			$res[] = [
				'date' => str_replace(' '.$post['year'], '', $date),
				'time' => date('H:i', strtotime($value['created_at'])),
				'outlet_cash_code' => $value['outlet_cash_code'],
				'outlet_cash_amount' => $value['outlet_cash_amount']
			];
		}

		return ['status' => 'success', 'result' => $res];
	}

	public function incomeDetail(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		$currency = 'Rp';
		$listHS = UserHairStylist::where('id_outlet', $user->id_outlet)
		->where('user_hair_stylist_status', 'Active')->select('id_user_hair_stylist', 'fullname as name')->get()->toArray();

		$projection = Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_payment_cash.cash_received_by')
		->whereDate('transactions.transaction_date', $date)
		->where('transaction_payment_status', 'Completed')
		->where('transactions.id_outlet', $user->id_outlet)
		->select('transaction_grandtotal', 'transactions.id_transaction', 'transactions.transaction_receipt_number', 'transaction_payment_cash.*', 'user_hair_stylist.fullname');

		$acceptance = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->where('outlet_cash.id_outlet', $user->id_outlet)
		->whereDate('outlet_cash.created_at', $date)
		->where('outlet_cash_status', 'Pending')
		->where('outlet_cash_type', 'Transfer To Supervisor')
		->select('id_outlet_cash', DB::raw('DATE_FORMAT(outlet_cash.created_at, "%H:%i") as time'), 'fullname as hair_stylist_name',
			'outlet_cash_status', 'outlet_cash_code', 'outlet_cash_amount as amount');

		$history = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->join('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
		->where('outlet_cash.id_outlet', $user->id_outlet)
		->whereDate('outlet_cash.confirm_at', $date)
		->where('outlet_cash_status', 'Confirm')
		->where('outlet_cash_type', 'Transfer To Supervisor')
		->select('id_outlet_cash', DB::raw('DATE_FORMAT(outlet_cash.created_at, "%H:%i") as time'), 'user_hair_stylist.fullname as hair_stylist_name',
			'outlet_cash_status', 'outlet_cash_code', 'outlet_cash_amount as amount', 'confirm.fullname as confirm_by_name');

		if(!empty($post['id_user_hair_stylist'])){
			$projection = $projection->where('id_user_hair_stylist', $post['id_user_hair_stylist']);
			$acceptance = $acceptance->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
			$history = $history->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}

		$projection = $projection->orderBy('transaction_date', 'desc')->get()->toArray();
		$acceptance = $acceptance->orderBy('outlet_cash.created_at', 'desc')->get()->toArray();
		$history = $history->orderBy('outlet_cash.confirm_at', 'desc')->get()->toArray();

		$resProjection = [];
		foreach ($projection as $value){
			$resProjection[] = [
				'id_transaction' => $value['id_transaction'],
				'time' => date('H:i', strtotime($value['updated_at'])),
				'hair_stylist_name' => $value['fullname'],
				'receipt_number' => $value['transaction_receipt_number'],
				'amount' => $value['transaction_grandtotal']
			];
		}

		$totalProjection = array_sum(array_column($resProjection, 'amount'));
		$totalAcceptance = array_sum(array_column($history, 'amount'));
		$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();

		$spvProjection = Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_payment_cash.cash_received_by')
		->whereDate('transactions.transaction_date', $date)
		->where('transaction_payment_status', 'Completed')
		->where('cash_received_by', $user->id_user_hair_stylist)->sum('cash_nominal');

		$spvAcceptance = OutletCash::where('outlet_cash.id_outlet', $user->id_outlet)
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->where('outlet_cash_type', 'Transfer To Supervisor')
		->where('outlet_cash_status', 'Confirm')
		->whereDate('outlet_cash.created_at', $date)->sum('outlet_cash_amount');

		$result = [
			'total_current_cash_outlet' => $outlet['total_current_cash'],
			'total_projection' => $totalProjection,
			'total_reception' => $totalAcceptance,
			'currency' => $currency,
			'spv_cash_projection' => (int)$spvProjection,
			'spv_cash_acceptance' => (int)$spvAcceptance,
			'list_hair_stylist' => $listHS,
			'projection' => $resProjection,
			'acceptance' => $acceptance,
			'history' => $history
		];
		return ['status' => 'success', 'result' => $result];
	}

	public function acceptanceDetail(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['id_outlet_cash'])){
			return ['status' => 'fail', 'messages' => ['ID can not be empty']];
		}

		$detail = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->leftJoin('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
		->where('id_outlet_cash', $post['id_outlet_cash'])
		->select('outlet_cash.*', 'user_hair_stylist.fullname', 'user_hair_stylist.user_hair_stylist_code', 'confirm.fullname as confirm_by_name')->first();

		if(empty($detail)){
			return ['status' => 'fail', 'messages' => ['Data not found']];
		}

		if($user->id_outlet != $detail['id_outlet']){
			return ['status' => 'fail', 'messages' => ['You are not available for this transaction']];
		}

		$listTransaction = OutletCash::join('transaction_payment_cash', 'transaction_payment_cash.id_outlet_cash', 'outlet_cash.id_outlet_cash')
		->join('transactions', 'transactions.id_transaction', 'transaction_payment_cash.id_transaction')
		->where('transaction_payment_cash.id_outlet_cash', $post['id_outlet_cash'])
		->select('transactions.transaction_receipt_number', 'transaction_payment_cash.cash_nominal as amount')->get()->toArray();

		$result = [
			'id_outlet_cash' => $detail['id_outlet_cash'],
			'date' => MyHelper::dateFormatInd($detail['created_at'], true, false),
			'time' => date('H:i', strtotime($detail['created_at'])),
			'hair_stylist_name' => $detail['fullname'],
			'hair_stylist_code' => $detail['user_hair_stylist_code'],
			'outlet_cash_code' => $detail['outlet_cash_code'],
			'status' => $detail['outlet_cash_status'],
			'amount' => $detail['outlet_cash_amount'],
			'currency' => 'Rp',
			'confirm_at' => (!empty($detail['confirm_at'])? MyHelper::dateFormatInd($detail['confirm_at'], true): null),
			'confirm_by_name' => $detail['confirm_by_name'],
			'list_transaction' => $listTransaction
		];

		return ['status' => 'success', 'result' => $result];
	}

	public function acceptanceConfirm(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['id_outlet_cash'])){
			return ['status' => 'fail', 'messages' => ['ID can not be empty']];
		}

		$detail = OutletCash::where('id_outlet_cash', $post['id_outlet_cash'])->first();

		if(empty($detail)){
			return ['status' => 'fail', 'messages' => ['Data not found']];
		}

		if($detail['transfer_status'] == 'Confirm'){
			return ['status' => 'fail', 'messages' => ['This transaction already confirm']];
		}

		if($user->id_outlet != $detail['id_outlet']){
			return ['status' => 'fail', 'messages' => ['You are not available for this transaction']];
		}

		$update = OutletCash::where('id_outlet_cash', $post['id_outlet_cash'])
		->update(['outlet_cash_status' => 'Confirm', 'confirm_at' => date('Y-m-d H:i:s'), 'confirm_by' => $user->id_user_hair_stylist]);
		if($update){
			$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
			$update = Outlet::where('id_outlet', $user->id_outlet)->update(['total_current_cash' => $outlet['total_current_cash'] + $detail['outlet_cash_amount']]);
		}

		return MyHelper::checkUpdate($update);
	}

	public function cashOutletTransfer(Request $request){
		$user = $request->user();
		$post = $request->all();

		if(!empty($post['amount']) && !empty($post['attachment'])){
			$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
			if($outlet['total_current_cash'] < $post['amount']){
				return ['status' => 'fail', 'messages' => ['Outlet balance is not sufficient']];
			}

			$save = OutletCash::create([
				'id_user_hair_stylist' => $user->id_user_hair_stylist,
				'id_outlet' => $user->id_outlet,
				'outlet_cash_type' => 'Transfer Supervisor To Central',
				'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
				'outlet_cash_amount' => $post['amount'],
				'outlet_cash_description' => $post['description']??null,
				'outlet_cash_status' => 'Confirm',
				'confirm_at' => date('Y-m-d H:i:s'),
				'confirm_by' => $user->id_user_hair_stylist
			]);

			if($save){
				if(!empty($request->file('attachment'))){
					$encode = base64_encode(fread(fopen($request->file('attachment'), "r"), filesize($request->file('attachment'))));
					$originalName = $request->file('attachment')->getClientOriginalName();
					$name = pathinfo($originalName, PATHINFO_FILENAME);
					$ext = pathinfo($originalName, PATHINFO_EXTENSION);
					$upload = MyHelper::uploadFile($encode, 'files/transfer_to_central/',$ext, date('YmdHis').'_'.$name);
					if (isset($upload['status']) && $upload['status'] == "success") {
						$fileName = $upload['path'];
						OutletCashAttachment::create([
							'id_outlet_cash' => $save['id_outlet_cash'],
							'outlet_cash_attachment' => $fileName,
							'outlet_cash_attachment_name' => $name.'.'.$ext
						]);
					}
				}

				$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
				$save = Outlet::where('id_outlet', $user->id_outlet)->update(['total_current_cash' => $outlet['total_current_cash'] - $post['amount']]);
			}

			return MyHelper::checkUpdate($save);
		}else{
			return ['status' => 'fail', 'messages' => ['Transfer amount or attachment can not be empty']];
		}
	}

	public function outletIncomeCreate(Request $request){
		$user = $request->user();
		$post = $request->all();

		if(!empty($post['amount']) && !empty($post['attachment'])){
			$save = OutletCash::create([
				'id_user_hair_stylist' => $user->id_user_hair_stylist,
				'id_outlet' => $user->id_outlet,
				'outlet_cash_type' => 'Income From Central',
				'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
				'outlet_cash_amount' => $post['amount'],
				'outlet_cash_description' => $post['description']??null,
				'outlet_cash_status' => 'Confirm',
				'confirm_at' => date('Y-m-d H:i:s'),
				'confirm_by' => $user->id_user_hair_stylist
			]);

			if($save){
				if(!empty($request->file('attachment'))){
					$encode = base64_encode(fread(fopen($request->file('attachment'), "r"), filesize($request->file('attachment'))));
					$originalName = $request->file('attachment')->getClientOriginalName();
					$name = pathinfo($originalName, PATHINFO_FILENAME);
					$ext = pathinfo($originalName, PATHINFO_EXTENSION);
					$upload = MyHelper::uploadFile($encode, 'files/income_from_central/',$ext, date('YmdHis').'_'.$name);
					if (isset($upload['status']) && $upload['status'] == "success") {
						$fileName = $upload['path'];
						OutletCashAttachment::create([
							'id_outlet_cash' => $save['id_outlet_cash'],
							'outlet_cash_attachment' => $fileName,
							'outlet_cash_attachment_name' => $name.'.'.$ext
						]);
					}
				}

				$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
				$save = Outlet::where('id_outlet', $user->id_outlet)->update(['total_cash_from_central' => $outlet['total_cash_from_central'] + $post['amount']]);
			}

			return MyHelper::checkUpdate($save);
		}else{
			return ['status' => 'fail', 'messages' => ['Transfer amount or attachment can not be empty']];
		}
	}

	public function cashOutletHistory(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['month']) && empty($post['year'])){
			return ['status' => 'fail', 'messages' => ['Month and Year can not be empty']];
		}

		$list = OutletCash::where('id_outlet', $user->id_outlet)
		->whereYear('created_at', '=', $post['year'])
		->whereMonth('created_at', '=', $post['month'])
		->whereIn('outlet_cash_type', ['Transfer Supervisor To Central', 'Income From Central'])
		->orderBy('updated_at', 'desc')
		->get()->toArray();

		$res = [];
		foreach ($list as $value){
			$type = strtok($value['outlet_cash_type'], " ");
			$att = OutletCashAttachment::where('id_outlet_cash', $value['id_outlet_cash'])->select('outlet_cash_attachment', 'outlet_cash_attachment_name')->get()->toArray();
			$res[] = [
				'id_outlet_cash' => $value['id_outlet_cash'],
				'id_user_hair_stylist' => $value['id_user_hair_stylist'],
				'outlet_cash_type' => ($type == 'Income' ? 'Kas Outlet' : 'Transfer'),
				'outlet_cash_amount' => $value['outlet_cash_amount'],
				'outlet_cash_description' => $value['outlet_cash_description'],
				'date' => MyHelper::dateFormatInd($value['created_at'], true, false),
				'outlet_cash_attachment' => $att
			];
		}

		$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();

		$result = [
			'info' => [
				'total_current_cash_outlet' => $outlet['total_current_cash'],
				'total_cash_from_central' => $outlet['total_cash_from_central'],
				'va_number' => '0000000000'
			],
			'data' => $res
		];
		return ['status' => 'success', 'result' => $result];
	}

	public function expenseOutletCreate(Request $request){
		$user = $request->user();
		$post = $request->all();

		if(!empty($post['amount']) && !empty($post['attachment'])){
			$countAttach = count($post['attachment']);
			$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
			if($outlet['total_cash_from_central'] < $post['amount']){
				return ['status' => 'fail', 'messages' => ['Your balance is not enough']];
			}

			if($countAttach > 3){
				return ['status' => 'fail', 'messages' => ['You can upload maximum 3 file']];
			}
			$files = [];
			foreach ($post['attachment'] as $attachment){
				if(!empty($attachment)){
					$encode = base64_encode(fread(fopen($attachment, "r"), filesize($attachment)));
					$originalName = $attachment->getClientOriginalName();
					$name = pathinfo($originalName, PATHINFO_FILENAME);
					$ext = pathinfo($originalName, PATHINFO_EXTENSION);
					$upload = MyHelper::uploadFile($encode, 'files/outlet_expense/',$ext, date('YmdHis').'_'.$name);
					if (isset($upload['status']) && $upload['status'] == "success") {
						$files[] = [
							"outlet_cash_attachment" => $upload['path'],
							"outlet_cash_attachment_name" => $name.'.'.$ext
						];
					}
				}
			}

			$save = OutletCash::create([
				'id_user_hair_stylist' => $user->id_user_hair_stylist,
				'id_outlet' => $user->id_outlet,
				'outlet_cash_type' => 'Expense Outlet',
				'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
				'outlet_cash_amount' => $post['amount'],
				'outlet_cash_description' => $post['description']??null,
				'outlet_cash_status' => 'Confirm',
				'confirm_at' => date('Y-m-d H:i:s'),
				'confirm_by' => $user->id_user_hair_stylist
			]);

			if($save){
				$insertattachment = [];
				foreach ($files??[] as $file){
					$insertattachment[] = [
						'id_outlet_cash' => $save['id_outlet_cash'],
						'outlet_cash_attachment' => $file['outlet_cash_attachment'],
						'outlet_cash_attachment_name' => $file['outlet_cash_attachment_name'],
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s')
					];
				}

				if(!empty($insertattachment)){
					OutletCashAttachment::insert($insertattachment);
				}

				$save = Outlet::where('id_outlet', $user->id_outlet)->update(['total_cash_from_central' => $outlet['total_cash_from_central'] - $post['amount']]);
			}

			return MyHelper::checkUpdate($save);
		}else{
			return ['status' => 'fail', 'messages' => ['Transfer amount or attachment can not be empty']];
		}
	}

	public function expenseOutletHistory(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['month']) && empty($post['year'])){
			return ['status' => 'fail', 'messages' => ['Month and Year can not be empty']];
		}

		$list = OutletCash::where('id_outlet', $user->id_outlet)
		->whereYear('created_at', '=', $post['year'])
		->whereMonth('created_at', '=', $post['month'])
		->whereIn('outlet_cash_type', ['Expense Outlet'])
		->orderBy('updated_at', 'desc')
		->get()->toArray();

		$res = [];
		foreach ($list as $value){
			$att = OutletCashAttachment::where('id_outlet_cash', $value['id_outlet_cash'])->select('outlet_cash_attachment', 'outlet_cash_attachment_name')->get()->toArray();
			$res[] = [
				'id_outlet_cash' => $value['id_outlet_cash'],
				'id_user_hair_stylist' => $value['id_user_hair_stylist'],
				'outlet_cash_amount' => $value['outlet_cash_amount'],
				'outlet_cash_description' => $value['outlet_cash_description'],
				'date' => MyHelper::dateFormatInd($value['created_at'], true, false),
				'outlet_cash_attachment' => $att
			];
		}

		$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
		$totalExpense = array_column($res, 'outlet_cash_amount');
		$result = [
			'total_cash_from_central' => $outlet['total_cash_from_central'],
			'total_expense' => array_sum($totalExpense),
			'data' => $res
		];
		return ['status' => 'success', 'result' => $result];
	}

	function phoneCheck(Request $request)
	{
		$phone = $request->json('phone');

		$phoneOld = $phone;
		$phone = preg_replace("/[^0-9]/", "", $phone);

		$checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

		if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
			return response()->json([
				'status' => 'fail',
				'messages' => $checkPhoneFormat['messages']
			]);
		} elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
			$phone = $checkPhoneFormat['phone'];
		}

		$data = UserHairStylist::select('*',\DB::raw('0 as challenge_key'))->where('phone_number', '=', $phone)->get()->toArray();

		if($data){
			$result['challenge_key'] = $data[0]['challenge_key'];
			return response()->json([
				'status' => 'success',
				'result' => $result
			]);
		}else{
			return response()->json([
				'status' => 'fail',
				'messages' => ['Akun tidak ditemukan']]);
		}
	}

	function forgotPin(users_forgot $request)
	{
		$phone = $request->json('phone');

		$phoneOld = $phone;
		$phone = preg_replace("/[^0-9]/", "", $phone);

		$checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        //get setting rule otp
		$setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if($setting && isset($setting['value_text'])){
        	$setting = json_decode($setting['value_text']);
        	$holdTime = (int)$setting->hold_time;
        }

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
        	return response()->json([
        		'status' => 'fail',
        		'otp_timer' => $holdTime,
        		'messages' => $checkPhoneFormat['messages']
        	]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
        	$phone = $checkPhoneFormat['phone'];
        }

        $user = UserHairStylist::where('phone_number', '=', $phone)->first();

        if (!$user) {
        	$result = [
        		'status'    => 'fail',
        		'otp_timer' => $holdTime,
        		'messages'    => ['User not found.']
        	];
        	return response()->json($result);
        }

        $user->sms_increment = 0;
        $user->save();

        $data = UserHairStylist::select('*',\DB::raw('0 as challenge_key'))->where('phone_number', '=', $phone)
        ->get()
        ->toArray();

        if ($data) {
            //First check rule for request otp
        	$checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
        	if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
        		return response()->json($checkRuleRequest);
        	}

        	if(!isset($checkRuleRequest['otp_timer']) && $checkRuleRequest == true){
        		$pin = MyHelper::createRandomPIN(6, 'angka');
        		$password = bcrypt($pin);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
        		$getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
        		if($getSettingTimeExpired){
        			$dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
        		}else{
        			$dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        		}

        		$update = UserHairStylist::where('id_user_hair_stylist', '=', $data[0]['id_user_hair_stylist'])->update(['otp_forgot' => $password, 'otp_valid_time' => $dateOtpTimeExpired]);

        		if (!empty($request->header('user-agent-view'))) {
        			$useragent = $request->header('user-agent-view');
        		} else {
        			$useragent = $_SERVER['HTTP_USER_AGENT'];
        		}

        		$del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
        		->where('oauth_access_tokens.user_id', $data[0]['id_user_hair_stylist'])->where('oauth_access_token_providers.provider', 'mitra')->delete();

        		if (stristr($useragent, 'iOS')) $useragent = 'iOS';
        		if (stristr($useragent, 'okhttp')) $useragent = 'Android';
        		if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';

        		$autocrm = app($this->autocrm)->SendAutoCRM(
        			'Pin Forgot',
        			$phone,
        			[
        				'pin' => $pin,
        				'useragent' => $useragent,
        				'now' => date('Y-m-d H:i:s'),
        				'date_sent' => date('d-m-y H:i:s'),
        				'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
        			],
        			$useragent,
        			false, false, 'hairstylist', null, true, $request->request_type
        		);
        	}elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
        		$holdTime = $checkRuleRequest['otp_timer'];
        	}

        	switch (env('OTP_TYPE', 'PHONE')) {
        		case 'MISSCALL':
        		$msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
        		break;

        		case 'WA':
        		$msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
        		break;

        		default:
        		$msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
        		break;
        	}

        	$user = UserHairStylist::select('*',\DB::raw('0 as challenge_key'))->where('phone_number', '=', $phone)->first();

        	if (env('APP_ENV') == 'production') {
        		$result = [
        			'status'    => 'success',
        			'result'    => [
        				'otp_timer' => $holdTime,
        				'phone'    =>    $phone,
        				'message'  =>    $msg_otp,
        				'challenge_key' => $user->challenge_key
        			]
        		];
        	} else {
        		$result = [
        			'status'    => 'success',
        			'result'    => [
        				'otp_timer' => $holdTime,
        				'phone'    =>    $phone,
        				'message'  =>    $msg_otp,
        				'challenge_key' => $user->challenge_key
        			]
        		];
        	}
        	return response()->json($result);
        } else {
        	$result = [
        		'status'    => 'fail',
        		'messages'  => ['Data yang kamu masukkan kurang tepat']
        	];
        	return response()->json($result);
        }
    }

    function verifyPin(Request $request)
    {
    	$phone = $request->json('phone');

    	$phone = preg_replace("/[^0-9]/", "", $phone);

    	$checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

    	if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
    		return response()->json([
    			'status' => 'fail',
    			'messages' => [$checkPhoneFormat['messages']]
    		]);
    	} elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
    		$phone = $checkPhoneFormat['phone'];
    	}

    	$data = UserHairStylist::where('phone_number', '=', $phone)->get()->toArray();

    	if ($data) {
    		if(!password_verify($request->json('pin'), $data[0]['otp_forgot'])){
    			return response()->json([
    				'status'    => 'fail',
    				'messages'    => ['OTP yang kamu masukkan salah']
    			]);
    		}

    		/*first if --> check if otp have expired and the current time exceeds the expiration time*/
    		if(!is_null($data[0]['otp_valid_time']) && strtotime(date('Y-m-d H:i:s')) > strtotime($data[0]['otp_valid_time'])){
    			return response()->json(['status' => 'fail', 'otp_check'=> 1, 'messages' => ['This OTP is expired, please re-request OTP from apps']]);
    		}

    		$update = UserHairStylist::where('id_user_hair_stylist', '=', $data[0]['id_user_hair_stylist'])->update(['otp_valid_time' => NULL]);
    		if ($update) {
    			if (\Module::collections()->has('Autocrm')) {
    				$autocrm = app($this->autocrm)->SendAutoCRM('Pin Verify', $phone, null, null, false, false, 'hairstylist');
    			}
    			$result = [
    				'status'    => 'success',
    				'result'    => [
    					'phone'    =>    $data[0]['phone_number']
    				]
    			];
    		}else{
    			$result = [
    				'status'    => 'fail',
    				'messages'    => ['Failed to Update Data']
    			];
    		}
    	} else {
    		$result = [
    			'status'    => 'fail',
    			'messages'    => ['This phone number isn\'t registered']
    		];
    	}
    	return response()->json($result??['status' => 'fail','messages' => ['No Process']]);
    }

    function changePin(Request $request)
    {

    	$phone = $request->json('phone');

    	$phone = preg_replace("/[^0-9]/", "", $phone);

    	$checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

    	if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
    		return response()->json([
    			'status' => 'fail',
    			'messages' => $checkPhoneFormat['messages']
    		]);
    	} elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
    		$phone = $checkPhoneFormat['phone'];
    	}

    	$data = UserHairStylist::where('phone_number', '=', $phone)->first();

    	if ($data) {
    		if(!empty($data['otp_forgot']) && !password_verify($request->json('pin_old'), $data['otp_forgot'])){
    			return response()->json([
    				'status'    => 'fail',
    				'messages'    => ['Current PIN doesn\'t match']
    			]);
    		}elseif(empty($data['otp_forgot']) && !password_verify($request->json('pin_old'), $data['password'])){
    			return response()->json([
    				'status'    => 'fail',
    				'messages'    => ['Current PIN doesn\'t match']
    			]);
    		}

    		$pin     = bcrypt($request->json('pin_new'));
    		$update = UserHairStylist::where('id_user_hair_stylist', '=', $data['id_user_hair_stylist'])->update(['password' => $pin, 'otp_forgot' => null]);
    		if (\Module::collections()->has('Autocrm')) {
    			if ($data['first_update_password'] < 1) {
    				$autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone, null, null, false, false, 'hairstylist');
    				$changepincount = $data['first_update_password'] + 1;
    				$update = UserHairStylist::where('id_user_hair_stylist', '=', $data['id_user_hair_stylist'])->update(['first_update_password' => $changepincount]);
    			} else {
    				$autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone, null, null, false, false, 'hairstylist');

    				$del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
    				->where('oauth_access_tokens.user_id', $data['id_user_hair_stylist'])->where('oauth_access_token_providers.provider', 'mitra')->delete();
    			}
    		}

    		$user = UserHairStylist::select('password',\DB::raw('0 as challenge_key'))->where('phone_number', $phone)->first();

    		$result = [
    			'status'    => 'success',
    			'result'    => [
    				'phone'    =>    $data['phone_number'],
    				'challenge_key' => $user->challenge_key
    			]
    		];
    	} else {
    		$result = [
    			'status'    => 'fail',
    			'messages'    => ['This phone number isn\'t registered']
    		];
    	}
    	return response()->json($result);
    }

    public function commissionDetail(Request $request)
    {
    	$request->validate([
    		'month' => 'date_format:Y-m|sometimes|nullable',
    	]);

    	$hs = $request->user();
    	$hs->load('bank_account', 'bank_account.bank_name');
    	$month = $request->month;

    	$incomes = HairstylistIncome::whereMonth('periode', date('m', strtotime($request->month)))
    	->whereYear('periode', date('Y', strtotime($request->month)))
    	->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
    	->get();


    	$result = [
    		'month' => $month,
    		'bank_name' => $hs->bank_account ? $hs->bank_account->bank_name->bank_name : '-',
    		'account_number' => $hs->bank_account ? $hs->bank_account->beneficiary_account : '-',
    		'account_name' => $hs->bank_account ? $hs->bank_account->beneficiary_name : '-',
    		'footer' => [
    			'footer_title' => 'Total diterima bulan ini setelah potongan',
    			'footer_content' => 'Dalam perhitungan',
    		],
    		'incomes' => [],
    		'attendances' => [],
    		'salary_cuts' => []
    	];

    	if (!$incomes->count()) {
    		$result['footer'] = [
    			'footer_title' => 'Belum ada data untuk pendapatan bulan ini',
    			'footer_content' => '-',
    		];
    		return MyHelper::checkGet($result);
    	}

    	$total = 0;
        // Incomes
    	foreach ($incomes as $income) {
    		$incomePart = [
    			'name' => $income->type == 'middle' ? 'Tengah Bulan' : 'Akhir Bulan',
    			'icon' => $income->type == 'middle' ? 'half' : 'full',
    			'footer' => [
    				'title_title' => $income->type == 'middle' ?'Penerimaan Tengah Bulan' : 'Penerimaan Akhir bulan',
    				'title_content' => 'Dalam Perhitungan', 
    				'subtitle_title' => $income->completed_at ? 'Ditransfer' : 'Belum Ditransfer',
    				'subtitle_content' => $income->completed_at ? date('d F Y', strtotime($income->completed_at)) : '-',
    			],
    			'list' => [],
    		];

    		$subtotalPart = 0;
    		$idOutlets = $income->hairstylist_income_details()
    		->where('source', 'not like', 'salary_cut_%')
    		->select('id_outlet')
    		->distinct()
    		->get()
    		->pluck('id_outlet');
    		foreach ($idOutlets as $idOutlet) {
    			$outlet = Outlet::find($idOutlet);
    			$incomeOutlet = [
    				'header_title' => 'Outlet',
    				'header_content' => $outlet->outlet_name ?? '-',
    				'footer_title' => 'Total',
    				'footer_content' => 'Dalam Perhitungan', 
    				'contents' => []
    			];

    			$subtotalOutlet = 0;
    			$incentiveDetails = $income->hairstylist_income_details()
    			->leftJoin('hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_income_details.reference')
    			->where('hairstylist_income_details.source', 'like', 'incentive_%')
    			->where('hairstylist_income_details.id_outlet', $outlet->id_outlet)
    			->get();

    			foreach ($incentiveDetails as $incentiveDetail) {
    				$incomeOutlet['contents'][] = [
    					'title' => $incentiveDetail->name,
    					'content' => MyHelper::requestNumber($incentiveDetail->amount, '_CURRENCY'),
    				];
    				$subtotalOutlet += $incentiveDetail->amount;
    			}

    			$commissionDetails = $income->hairstylist_income_details()
    			->leftJoin('transaction_products', 'transaction_products.id_transaction_product', 'hairstylist_income_details.reference')
    			->where('hairstylist_income_details.source', 'product_commission')
    			->where('hairstylist_income_details.id_outlet', $outlet->id_outlet)
    			->get()->groupBy('id_product');

    			foreach ($commissionDetails as $id_product => $commissionDetail) {
    				$product = Product::find($id_product);
    				$incomeOutlet['contents'][] = [
    					'title' => 'Komisi ' . ($product->product_name ?? '-'),
    					'content' => MyHelper::requestNumber($commissionDetail->sum('amount'), '_CURRENCY'),
    				];
    				$subtotalOutlet += $commissionDetail->sum('amount');
    			}

    			$incomeOutlet['footer_content'] = MyHelper::requestNumber($subtotalOutlet, '_CURRENCY');
    			$subtotalPart += $subtotalOutlet;
    			$incomePart['list'][] = $incomeOutlet;
    		}
    		$incomePart['footer']['title_content'] = MyHelper::requestNumber($subtotalPart, '_CURRENCY');
    		$total += $subtotalPart;
    		$result['incomes'][] = $incomePart;
    	}

        //Attendances
    	foreach ($incomes as $income) {
    		$attendancePart = [
    			'name' => $income->type == 'middle' ? 'Tengah Bulan' : 'Akhir Bulan',
    			'icon' => $income->type == 'middle' ? 'half' : 'full',
    			'footer' => null,
    			'list' => []
    		];

    		$idOutlets = $income->hairstylist_income_details()
    		->where('source', 'not like', 'salary_cut_%')
    		->select('id_outlet')
    		->distinct()
    		->get()
    		->pluck('id_outlet');
    		foreach ($idOutlets as $idOutlet) {
    			$outlet = Outlet::find($idOutlet);
    			$attendanceOutlet = [
    				'header_title' => 'Outlet',
    				'header_content' => $outlet->outlet_name ?? '-',
    				'footer_title' => null,
    				'footer_content' => null,
    				'contents' => [
    					[
    						'title' => 'Hari Masuk',
    						'content' => HairstylistAttendance::where('id_outlet', $idOutlet)->where(function ($query) {
    							$query->whereNotNull('clock_in')
    							->orWhereNotNull('clock_out');
    						})
    						->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
    						->count(),
    					],
    				]
    			];

    			$commissionDetails = $income->hairstylist_income_details()
    			->leftJoin('transaction_products', 'transaction_products.id_transaction_product', 'hairstylist_income_details.reference')
    			->where('hairstylist_income_details.source', 'product_commission')
    			->where('hairstylist_income_details.id_outlet', $outlet->id_outlet)
    			->get()->groupBy('id_product');

    			foreach ($commissionDetails as $id_product => $commissionDetail) {
    				$product = Product::find($id_product);
    				$incomeOutlet['contents'][] = [
    					'title' => 'Customer ' . ($product->product_name ?? '-'),
    					'content' => MyHelper::requestNumber($commissionDetail->sum('amount'), '_CURRENCY'),
    				];
    			}
    			$attendancePart['list'][] = $attendanceOutlet;
    		}
    		$result['attendances'][] = $attendancePart;
    	}

        // Incomes
    	foreach ($incomes as $income) {
    		$cutPart = [
    			'name' => $income->type == 'middle' ? 'Tengah Bulan' : 'Akhir Bulan',
    			'icon' => $income->type == 'middle' ? 'half' : 'full',
    			'footer' => [
    				'title_title' => 'Total Potongan',
    				'title_content' => 'Dalam Perhitungan', 
    				'subtitle_title' => null,
    				'subtitle_content' => null,
    			],
    			'list' => [],
    		];

    		$subtotalCut = 0;
    		$idOutlets = $income->hairstylist_income_details()
    		->where('source', 'like', 'salary_cut_%')
    		->select('id_outlet')
    		->distinct()
    		->get()
    		->pluck('id_outlet');

    		$cutOutlet = [
    			'header_title' => null,
    			'header_content' => $outlet->outlet_name ?? null,
    			'footer_title' => null,
    			'footer_content' => null, 
    			'contents' => []
    		];

    		$cutDetails = $income->hairstylist_income_details()
    		->selectRaw('hairstylist_income_details.id_hairstylist_income, hairstylist_group_default_potongans.name, SUM(amount)')
    		->leftJoin('hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans', 'hairstylist_income_details.reference')
    		->where('hairstylist_income_details.source', 'like', 'salary_cut_%')
    		->groupBy('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
    		->get();

    		foreach ($cutDetails as $cutDetail) {
    			$cutOutlet['contents'][] = [
    				'title' => $cutDetail->name,
    				'content' => MyHelper::requestNumber($cutDetail->amount, '_CURRENCY'),
    			];
    			$subtotalCut += $cutDetail->amount;
    		}
    		$cutPart['list'][] = $cutOutlet;

    		$cutPart['footer']['title_content'] = MyHelper::requestNumber($subtotalCut, '_CURRENCY');
    		$total -= $subtotalCut;
    		$result['salary_cuts'][] = $cutPart;
    	}
    	$result['footer']['footer_content'] =  MyHelper::requestNumber($total, '_CURRENCY');
    	return MyHelper::checkGet($result);
    }
}
