<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorSpecialist;
use Modules\Doctor\Entities\DoctorSchedule;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionConsultation;
use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use Modules\Doctor\Entities\SubmissionChangeDoctorData;
use Modules\Doctor\Http\Requests\DoctorCreate;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRating;
use Validator;
use Image;
use DB;
use Carbon\Carbon;

class ApiDoctorController extends Controller
{
    function __construct()
    {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $countTotal = null;

        $doctor = Doctor::with('outlet')->with('specialists');

        if (isset($post['rule'])) {
            $countTotal = $doctor->count();
            $this->filterList($doctor, $post['rule'], $post['operator'] ?: 'and');
        }

        if (isset($post['order'])) {
            $column_name = null;
            $dir = $post['order'][0]['dir'];

            switch($post['order'][0]['column']){
                case '0':
                    $column_name = "doctor_name";
                    break;
                case '1':
                    $column_name = "doctor_phone";
                    break;
                case '2':
                    $column_name = "outlet.outlet_name";
                    break;
                case '3':
                    $column_name = "doctor_session_price";
                    break;
            }
            
            $doctor->orderBy($column_name, $dir);
        } else {
            $doctor->orderBy('created_at', 'DESC');
        }

        //filter by id_doctor_specialist_category
        // if(isset($post['id_doctor_specialist_category'])){
        //     $doctor->whereHas('specialists', function($query) use ($post) {
        //         $query->whereHas('category', function($query2) use ($post) {
        //             $query2->where('id_doctor_specialist_category', $post['id_doctor_specialist_category']);
        //         });
        //      });
        // }

        if(isset($post['id_outlet'])){
            $doctor->where('id_outlet', $post['id_outlet']);
        }

        if(isset($post['doctor_recomendation_status'])){
            $doctor->where('doctor_recomendation_status', $post['doctor_recomendation_status']);
        }

        if($request['page']) {
            $doctor = $doctor->paginate($post['length'] ?: 10);
        } else {
            $doctor = $doctor->get()->toArray();
        }

        return response()->json(['status'  => 'success', 'result' => $doctor]);
    }

    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $where=$operator=='and'?'where':'orWhere';
        $subjects=['doctor_name', 'doctor_phone', 'doctor_session_price'];
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query->$where($subject,$rule[0],$rule[1]);
                }
            }
        }

        if($rules2=$newRule['outlet']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('outlet', function($query2) use ($rule) {
                    $query2->where('outlet_name', $rule[0], $rule[1]);
                });
            }
        }
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function listDoctor(Request $request)
    {
        $post = $request->json()->all();

        $doctor = Doctor::with('outlet')->with('specialists')->orderBy('created_at', 'DESC');

        // get filter by id_doctor_specialist_category
        // if(isset($post['id_doctor_specialist_category'])){
        //     $doctor->whereHas('specialists', function($query) use ($post) {
        //         $query->whereHas('category', function($query2) use ($post) {
        //             $query2->where('id_doctor_specialist_category', $post['id_doctor_specialist_category']);
        //         });
        //      });
        // }

        if(isset($post['id_outlet'])){
            $doctor->where('id_outlet', $post['id_outlet']);
        }

        if(isset($post['search'])){
            $doctor->where(function ($query) use ($post) {
                $query->WhereHas('specialists', function($query) use ($post) {
                            $query->where('doctor_specialist_name', 'LIKE' , '%'.$post['search'].'%');
                        })
                        ->orWhere('doctor_name', 'LIKE' , '%'.$post['search'].'%');
            });
        }

        if($request['page']) {
            $doctor = $doctor->paginate($post['length'] ?: 10);
        } else {
            $doctor = $doctor->get()->toArray();
        }

        return response()->json(['status'  => 'success', 'result' => $doctor]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(DoctorCreate $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        DB::beginTransaction();
        //birthday explode
        $d = explode('/', $post['birthday']);
        $post['birthday'] = $d[2] . "-" . $d[0] . "-" . $d[1];

        //doctor session price
        $post['doctor_session_price'] = str_replace(".", '', $post['doctor_session_price']);

        //set password
        if(!isset($post['id_doctor'])){
            if ($post['pin'] == null) {
                $pin = MyHelper::createRandomPIN(8, 'kecil');
                if(env('APP_ENV') != "production"){
                    $pin = '77777777';
                }
            } else {
                $pin = $post['pin'];
            }
            unset($post['pin']);
            $post['password'] = bcrypt($pin);

            //sentPin
            $sent_pin = $post['sent_pin'];
            unset($post['sent_pin']);
        }

        $post['provider'] = MyHelper::cariOperator($post['doctor_phone']);

        //upload photo id doctor
        if (isset($post['doctor_photo'])) {
            $upload = MyHelper::uploadPhotoStrict($post['doctor_photo'], $path = 'img/doctor/', 300, 300);
            if ($upload['status'] == "success") {
                $post['doctor_photo'] = $upload['path'];
            } else {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['fail upload image']
                ];
                return response()->json($result);
            }
        } 

        //set active
        if (isset($post['is_active'])) { 
            if($post['is_active'] == "on") {
                $post['is_active'] = 1;
            } else {
                $post['is_active'] = 0;
            }
        }

        //get specialist id
        $specialist_id = $post['doctor_specialist'];
        unset($post['doctor_specialist']);

        if(!isset($post['id_doctor'])) {
            $post['id_doctor'] = null;
        }

        $save = Doctor::updateOrCreate(['id_doctor' => $post['id_doctor']], $post);

        //save specialists
        if($post['id_doctor'] != null) {
            $oldSpecialist = Doctor::find($post['id_doctor'])->specialists()->detach(); 
            $specialist = $save->specialists()->attach($specialist_id);
        } else {
            $specialist = $save->specialists()->attach($specialist_id);
        }

        $result = MyHelper::checkGet($save);

        // TO DO Pending Task AutoCRM error 
        if ($result['status'] == "success") {
            if (isset($sent_pin) && $sent_pin == 'Yes') {
                if (!empty($request->header('user-agent-view'))) {
                    $useragent = $request->header('user-agent-view');
                } else {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if (stristr($useragent, 'iOS')) $useragent = 'iOS';
                if (stristr($useragent, 'okhttp')) $useragent = 'Android';
                if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Doctor Pin Sent',
                        $post['doctor_phone'],
                        [
                            'pin' => $pin,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s'),
                            'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                        ],
                        $useragent,
                        false,
                        false,
                        'doctor'
                    );
                }
            }
        }

        DB::commit();
        return response()->json(['status'  => 'success', 'result' => ['id_doctor' => $post['id_doctor'], 'crm' => $autocrm??true]]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function changePassword(Request $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        $password = bcrypt($post['pin']);

        $update_password = Doctor::where('id_doctor', $post['id_doctor'])->update(['password' => $password]);
        
        return response()->json(['status'  => 'success', 'result' => ['id_doctor' => $post['id_doctor'], 'crm' => $autocrm??true]]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $doctor = Doctor::where('id_doctor', $id)->with('outlet')->with('specialists')->first();
        unset($doctor['password']);

        $schedule = $this->getScheduleDoctor($id);

        $doctor['schedules'] = $schedule;

        $schedule_be = DoctorSchedule::where('id_doctor', $id)->with('schedule_time')->get();

        $doctor['schedules_raw'] = $schedule_be;

        return response()->json(['status'  => 'success', 'result' => $doctor]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            //TO DO add validation delete where has konsultasi
            $id_doctor = $request->json('id_doctor');
            $doctor = Doctor::where('id_doctor', $id_doctor)->first();
            $delete = $doctor->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Doctor has been used.']
            ]);
        }
    }

    public function mySettings(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        DB::beginTransaction();
        try {
            //initialize value
            $value = 1; //on
            if($post['value'] == "off") {
                $value = 0;
            }

            Doctor::where('id_doctor', $user['id_doctor'])->update([$post['action'] => $value]); 
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Update Settings Schedule Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        //if off value case
        if($post['value'] == "off") {
            return response()->json(['status'  => 'success', 'result' => $post['action']." Successfully Deactivated"]);
        }

        //default for on value case
        return response()->json(['status'  => 'success', 'result' => $post['action']." Has Been Activated Successfully"]);
    }

    public function myProfile(Request $request)
    {
        $user = $request->user();

        $doctor = Doctor::where('id_doctor', $user['id_doctor'])->with('outlet')->with('specialists')->orderBy('created_at', 'DESC');

        if(empty($doctor)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor not found']
            ]);
        }

        $doctor = $doctor->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $doctor]);
    }

    public function submissionChangeDataStore(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        DB::beginTransaction();
        try {
            $submission = SubmissionChangeDoctorData::create($post); 
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Submission Change Data Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        return response()->json(['status'  => 'success', 'result' => $submission]);
    }

    public function ratingSummary(Request $request)
	{
		$user = $request->user();
		$ratingDc = Doctor::where('doctors.id_doctor',$user->id_doctor)
		->leftJoin('user_ratings','user_ratings.id_doctor','doctors.id_doctor')
		->select(
			DB::raw('
				doctors.id_doctor,
				doctors.doctor_phone,
				doctors.doctor_name,
				doctors.total_rating,
				COUNT(DISTINCT user_ratings.id_user) as total_customer
				')
		)
		->first();

		$summary = Doctor::where('id_doctor', $user->id_doctor)->get();
		$summaryRating = [];
		$summaryOption = [];
		foreach ($summary as $val) {
			if ($val['summary_type'] == 'rating_value') {
				$summaryRating[$val['key']] = $val['value'];
			} else {
				$summaryOption[$val['key']] = $val['value'];
			}
		}

		$settingOptions = RatingOption::select('star','question','options')->where('rating_target', 'doctor')->get();
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

		$res = [
			'doctor_name' => $ratingDc['doctor_name'] ?? null,
			'doctor_phone' => $ratingDc['doctor_phone'] ?? null,
			'total_customer' => (int) ($ratingDc['total_customer'] ?? null),
			'total_rating' => (float) ($ratingDc['total_rating'] ?? null),
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
		$comment = UserRating::where('user_ratings.id_doctor', $user->id_doctor)
		->leftJoin('transaction_consultations','user_ratings.id_transaction_consultation','transaction_consultations.id_transaction_consultation')
		->whereNotNull('suggestion')
		->where('suggestion', '!=', "")
		->select(
			'transaction_consultations.order_id',
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

    public function listAllDoctor(Request $request)
    {
        $post = $request->json()->all();

        $doctor = Doctor::with('outlet')->orderBy('created_at', 'DESC');

        if(!empty($post['id_outlet'])){
            $doctor = $doctor->where('id_outlet', $post['id_outlet']);
        }

        $doctor = $doctor->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $doctor]);
    }

    public function updateRecomendationStatus(Request $request)
    {
        $post = $request->json()->all(); 

        DB::beginTransaction();
        try {
            //initialize value
            $ids_doctor = $post['id_doctor'];

            //reset doctor recomendation
            DB::table('doctors')->update([ 'doctor_recomendation_status' => false]);

            //new doctor recomendation
            Doctor::whereIn('id_doctor', $ids_doctor)->update(['doctor_recomendation_status' => true]);
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Update Doctor Recomendation Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        //default for on value case
        return response()->json(['status'  => 'success', 'result' => "Doctor Recomendation Has Been Updated Successfully"]);
    }

    public function getDoctorRecomendation(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        //Logic doctor recomendation
        $recomendationDoctor = array();

        //1. Doctor From Consultation History
        $historyConsultation = Transaction::where('id_user', $user->id)->where('trasaction_type', 'consultation')->get();
        if(!empty($historyConsultation)){
            foreach($historyConsultation as $hc){
                $doctorId = TransactionConsultation::where('id_transaction', $hc->id_transaction)->pluck('id_doctor');
                $doctor = Doctor::with('outlet')->with('specialists')->first();

                if(in_array($doctor, $recomendationDoctor) == false && count($recomendationDoctor) < 3) {
                    $recomendationDoctor[] = $doctor;
                }

                if(count($recomendationDoctor) >= 3) {
                    return response()->json(['status'  => 'success', 'result' => $recomendationDoctor]);
                }
            }
        }

        //2. Doctor From Outlet Related Transaction Product History
        $historyTransaction = Transaction::where('id_user', $user->id)->where('trasaction_type', 'product')->get();
        if(!empty($historyTransaction)){
            foreach($historyTransaction as $ht){
                $doctor = Doctor::with('outlet')->with('specialists')->where('id_outlet', $ht->id_outlet)->first();

                if(in_array($doctor, $recomendationDoctor) == false && count($recomendationDoctor) < 3) {
                    $recomendationDoctor[] = $doctor;
                }

                if(count($recomendationDoctor) >= 3) {
                    return response()->json(['status'  => 'success', 'result' => $recomendationDoctor]);
                }
            }
        }

        //3. From Setting
        $doctorRecomendationDefault = Doctor::with('outlet')->with('specialists')->where('doctor_recomendation_status', true)->get();

        if(empty($doctorRecomendationDefault)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor Recomendation Settings not found']
            ]);
        }

        foreach($doctorRecomendationDefault as $dr){
            if(in_array($dr, $recomendationDoctor) == false && count($recomendationDoctor) < 4) {
                $recomendationDoctor[] = $dr;
            }

            if(count($recomendationDoctor) >= 4) {
                return response()->json(['status'  => 'success', 'result' => $recomendationDoctor]);
            }
        }

        return response()->json(['status'  => 'success', 'result' => $recomendationDoctor]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function getScheduleDoctor($id_doctor)
    {
        $doctor_schedule = DoctorSchedule::where('id_doctor', $id_doctor)->with('schedule_time')->onlyActive();

        $doctor_schedule = $doctor_schedule->get()->toArray();

        //problems hereee
        $schedule = array();
        if(!empty($doctor_schedule)){
            $i = 0;
            while(count($schedule) < 4){
                if($i > 0) {
                    $post['date'] = date("Y-m-d", strtotime("+$i day"));
                    $date = date("d-m-Y", strtotime("+$i day"));
                    $day = strtolower(date("l", strtotime($date)));

                    $dateId = Carbon::parse($date)->locale('id');
                    $dateId->settings(['formatFunction' => 'translatedFormat']);

                    $dayId = $dateId->format('l');
                } else {
                    $post['date'] = date("Y-m-d");
                    $date = date("d-m-Y");
                    $day = strtolower(date("l", strtotime($date)));

                    $dateId = Carbon::parse($date)->locale('id');
                    $dateId->settings(['formatFunction' => 'translatedFormat']);

                    $dayId = $dateId->format('l');
                }
                $i += 1;

                foreach($doctor_schedule as $row) {
                    if($row['day'] == $day) {
                        $row['date'] = $date;
                        $row['day'] = $dayId;
                                
                        foreach($row['schedule_time'] as $key2 => $time) {
                            $post['time'] = date("H:i:s", strtotime($time['start_time']));

                            //cek validation avaibility time from consultation
                            $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $post['date'])
                            ->where('schedule_start_time', $post['time'])->count();
                            $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
                            $quota = $getSetting['value'];
    
                            if($quota <= $doctor_constultation && $quota != null){
                                $row['schedule_time'][$key2]['status_session'] = "disable";
                                $row['schedule_time'][$key2]['disable_reason'] = "Kuota Sudah Penuh";
                            } else {
                                $row['schedule_time'][$key2]['status_session'] = "available";
                                $row['schedule_time'][$key2]['disable_reason'] = null;
                            }

                            //cek validation avaibility time from current time
                            $nowTime = date("H:i:s");
                            $nowDate = date('d-m-Y');

                            if($post['time'] < $nowTime && strtotime($date) <= strtotime($nowDate)){
                                $row['schedule_time'][$key2]['status_session'] = "disable";
                                $row['schedule_time'][$key2]['disable_reason'] = "Waktu Sudah Terlewati";
                            } else {
                                $row['schedule_time'][$key2]['status_session'] = "available";
                                $row['schedule_time'][$key2]['disable_reason'] = null;
                            }
                        }

                        $schedule[] = $row;
                    }
                }
            }
        }

        return $schedule;
    }

    public function listOutletOption(Request $request)
    {
        $idsOutletDoctor = Doctor::onlyActive()->get()->pluck('id_outlet');

        $outlets = Outlet::whereIn('id_outlet', $idsOutletDoctor)->get();

        if(empty($outlets)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet not found']
            ]);
        }

        $outlets = $outlets->toArray();

        $result = [];

        foreach($outlets as $key => $outlet){
            $result[$key]['id_outlet'] = $outlet['id_outlet'];
            $result[$key]['outlet_name'] = $outlet['outlet_name'];
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Get token for RTC infobip
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getInfobipToken(Request $request)
    {
        $token = $request->user()->getActiveToken();
        if (!$token) {
            return [
                'status' => 'fail',
                'messages' => ['Failed request infobip token'],
            ];
        }
        return MyHelper::checkGet(['token' => $token]);
    }
}
