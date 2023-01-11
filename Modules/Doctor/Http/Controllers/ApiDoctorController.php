<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorSpecialist;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\TimeSchedule;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionConsultation;
use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use Modules\Doctor\Entities\SubmissionChangeDoctorData;
use Modules\Doctor\Http\Requests\DoctorCreate;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\UserRatingPhoto;
use Modules\UserRating\Entities\UserRatingSummary;
use Modules\Doctor\Http\Requests\DoctorPinNewAdmin;
use Validator;
use Image;
use DB;
use Carbon\Carbon;

class ApiDoctorController extends Controller
{
    public function __construct()
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

            switch ($post['order'][0]['column']) {
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

        if (isset($post['id_outlet'])) {
            $doctor->where('id_outlet', $post['id_outlet']);
        }

        if (isset($post['doctor_recomendation_status'])) {
            $doctor->where('doctor_recomendation_status', $post['doctor_recomendation_status']);
        }

        if ($request['page']) {
            $doctor = $doctor->paginate($post['length'] ?: 10);
        } else {
            $doctor = $doctor->get()->toArray();
        }

        return response()->json(['status'  => 'success', 'result' => $doctor]);
    }

    public function filterList($query, $rules, $operator = 'and')
    {
        $newRule = [];
        foreach ($rules as $var) {
            $rule = [$var['operator'] ?? '=',$var['parameter']];
            if ($rule[0] == 'like') {
                $rule[1] = '%' . $rule[1] . '%';
            }
            $newRule[$var['subject']][] = $rule;
        }

        $where = $operator == 'and' ? 'where' : 'orWhere';
        $subjects = ['doctor_name', 'doctor_phone', 'doctor_session_price'];
        foreach ($subjects as $subject) {
            if ($rules2 = $newRule[$subject] ?? false) {
                foreach ($rules2 as $rule) {
                    $query->$where($subject, $rule[0], $rule[1]);
                }
            }
        }

        if ($rules2 = $newRule['outlet'] ?? false) {
            foreach ($rules2 as $rule) {
                $query->{$where . 'Has'}('outlet', function ($query2) use ($rule) {
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

        $doctors = Doctor::where('is_active', 1)->with('outlet')->with('specialists')->orderBy('created_at', 'DESC');

        // get filter by id_doctor_specialist_category
        // if(isset($post['id_doctor_specialist_category'])){
        //     $doctor->whereHas('specialists', function($query) use ($post) {
        //         $query->whereHas('category', function($query2) use ($post) {
        //             $query2->where('id_doctor_specialist_category', $post['id_doctor_specialist_category']);
        //         });
        //      });
        // }

        if (isset($post['id_outlet'])) {
            $doctors->where('id_outlet', $post['id_outlet']);
        }

        if (isset($post['search'])) {
            $doctors->where(function ($query) use ($post) {
                $query->WhereHas('specialists', function ($query) use ($post) {
                            $query->where('doctor_specialist_name', 'LIKE', '%' . $post['search'] . '%');
                })
                        ->orWhere('doctor_name', 'LIKE', '%' . $post['search'] . '%');
            });
        }

        if ($request['page']) {
            $doctors = $doctors->paginate($post['length'] ?: 10);
        } else {
            $doctors = $doctors->get()->toArray();
        }

        //add ratings to doctor
        foreach ($doctors as $key => $doctor) {
            $ratings = [];
            $getRatings = UserRating::join('users', 'users.id', 'user_ratings.id_user')
                        ->select('user_ratings.*', 'users.name', 'users.photo')
                        ->where('id_doctor', $doctor['id_doctor'])->orderBy('user_ratings.created_at', 'desc')->limit(5)->get()->toArray();
            foreach ($getRatings as $rating) {
                $getPhotos = UserRatingPhoto::where('id_user_rating', $rating['id_user_rating'])->get()->toArray();
                $photos = [];
                foreach ($getPhotos as $dt) {
                    $photos[] = $dt['url_user_rating_photo'];
                }
                $currentOption = explode(',', $rating['option_value']);
                $ratings[] = [
                    "date" => MyHelper::dateFormatInd($rating['created_at'], false, false, false),
                    "user_name" => $rating['name'],
                    "user_photo" => config('url.storage_url_api') . (!empty($rating['photo']) ? $rating['photo'] : 'img/user_photo_default.png'),
                    "rating_value" => $rating['rating_value'],
                    "suggestion" => $rating['suggestion'],
                    "option_value" => $currentOption,
                    "photos" => $photos
                ];
            }
            $doctors[$key]['practice_experience'] = str_replace(['years', 'tahun', 'year'], 'tahun', $doctor['practice_experience'] ?? '');
            $doctors[$key]['ratings'] = $ratings;
            $doctors[$key]['count_rating'] = UserRating::where('id_doctor', $doctor['id_doctor'])->count();

            //get ratings product
            $doctors[$key]['total_rating'] = round(UserRating::where('id_doctor', $doctor['id_doctor'])->average('rating_value') ?? 0, 1);
        }

        return response()->json(['status'  => 'success', 'result' => $doctors]);
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

        //check phone format
        $post['doctor_phone'] = preg_replace("/[^0-9]/", "", $post['doctor_phone']);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($post['doctor_phone']);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $post['doctor_phone'] = $checkPhoneFormat['phone'];
        }

        //doctor session price
        $post['doctor_session_price'] = str_replace(".", '', $post['doctor_session_price']);

        //set password
        if (!isset($post['id_doctor'])) {
            if ($post['pin'] == null) {
                $pin = MyHelper::createRandomPIN(8, 'kecil');
                if (env('APP_ENV') != "production") {
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
            if ($post['is_active'] == "on") {
                $post['is_active'] = 1;
            } else {
                $post['is_active'] = 0;
            }
        }

        //get specialist id
        $specialist_id = $post['doctor_specialist'];
        unset($post['doctor_specialist']);

        if (!isset($post['id_doctor'])) {
            $post['id_doctor'] = null;
        }

        $save = Doctor::updateOrCreate(['id_doctor' => $post['id_doctor']], $post);

        //save specialists
        if ($post['id_doctor'] != null) {
            $oldSpecialist = Doctor::find($post['id_doctor'])->specialists()->detach();
            $specialist = $save->specialists()->attach($specialist_id);
        } else {
            $specialist = $save->specialists()->attach($specialist_id);
        }

        //save schedule day
        if ($post['id_doctor'] != null) {
            $createSchedule = $save->createScheduleDay($post['id_doctor']);
        } else {
            $doctor = Doctor::where('doctor_phone', $post['doctor_phone'])->first();
            $createSchedule = $save->createScheduleDay($doctor['id_doctor']);
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

                if (stristr($useragent, 'iOS')) {
                    $useragent = 'iOS';
                }
                if (stristr($useragent, 'okhttp')) {
                    $useragent = 'Android';
                }
                if (stristr($useragent, 'GuzzleHttp')) {
                    $useragent = 'Browser';
                }

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Doctor Pin Sent',
                        $post['doctor_phone'],
                        [
                            'otp' => $pin,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s'),
                            'expired_time' => (string) MyHelper::setting('setting_expired_otp', 'value', 30),
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
        return response()->json(['status'  => 'success', 'result' => ['id_doctor' => $post['id_doctor'], 'crm' => $autocrm ?? true, 'save' => $save]]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function changePassword(DoctorPinNewAdmin $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        $password = bcrypt($post['password_new']);

        $update_password = Doctor::where('id_doctor', $post['id_doctor'])->update(['password' => $password]);

        return response()->json(['status'  => 'success', 'result' => ['id_doctor' => $post['id_doctor'], 'crm' => $autocrm ?? true]]);
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
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function showAdmin($id)
    {
        $doctor = Doctor::where('id_doctor', $id)->with('outlet')->with('specialists')->first();
        unset($doctor['password']);

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

            //check transaction consultation
            $transanctionConsultation = TransactionConsultation::where('id_doctor', $id_doctor)->count();
            if ($transanctionConsultation > 0) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Doctors who already have a consultation cannot be deleted.']
                ]);
            }

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

    public function updateMySettings(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        DB::beginTransaction();
        try {
            //initialize value
            $value = 1; //on
            if ($post['value'] == "off") {
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
        if ($post['value'] == "off") {
            return response()->json(['status'  => 'success', 'result' => $post['action'] . " Successfully Deactivated"]);
        }

        //default for on value case
        return response()->json(['status'  => 'success', 'result' => $post['action'] . " Has Been Activated Successfully"]);
    }

    public function getMySettings(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        $result['schedule_toogle'] = $user->schedule_toogle;
        $result['notification_toogle'] = $user->notification_toogle;

        foreach ($result as $key => $value) {
            if ($value == 1) {
                $result[$key] = 'on';
            } else {
                $result[$key] = 'off';
            }
        }

        //default for on value case
        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function myProfile(Request $request)
    {
        $user = $request->user();

        $doctor = Doctor::where('id_doctor', $user['id_doctor'])->with('outlet')->with('specialists')->orderBy('created_at', 'DESC');

        if (empty($doctor)) {
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
        $ratingDc = Doctor::where('doctors.id_doctor', $user->id_doctor)
        ->leftJoin('user_ratings', 'user_ratings.id_doctor', 'doctors.id_doctor')
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

        $summary = UserRatingSummary::where('id_doctor', $user->id_doctor)->get();
        $summaryRating = [];
        $summaryOption = [];
        foreach ($summary as $val) {
            if ($val['summary_type'] == 'rating_value') {
                $summaryRating[$val['key']] = $val['value'];
            } else {
                $summaryOption[$val['key']] = $val['value'];
            }
        }

        $settingOptions = RatingOption::select('star', 'question', 'options')->where('rating_target', 'doctor')->get();
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
            'total_rating' => TransactionConsultation::where('id_doctor', $user->id_doctor)->whereIn('consultation_status', ['done','completed'])->count(),
            'rating_value' => [
                ['rating' => '5', 'progress' => (int) ($summaryRating['5'] ?? 0)],
                ['rating' => '4', 'progress' => (int) ($summaryRating['4'] ?? 0)],
                ['rating' => '3', 'progress' => (int) ($summaryRating['3'] ?? 0)],
                ['rating' => '2', 'progress' => (int) ($summaryRating['2'] ?? 0)],
                ['rating' => '1', 'progress' => (int) ($summaryRating['1'] ?? 0)],
            ],
            'rating_option' => $resOption
        ];

        return MyHelper::checkGet($res);
    }

    public function ratingComment(Request $request)
    {
        $user = $request->user();
        $comment = UserRating::where('user_ratings.id_doctor', $user->id_doctor)
        ->join('users', 'users.id', 'user_ratings.id_user')
        ->leftJoin('transaction_consultations', 'user_ratings.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
        ->leftJoin('transactions', 'transactions.id_transaction', 'transaction_consultations.id_transaction')
        ->whereNotNull('suggestion')
        ->where('suggestion', '!=', "")
        ->select(
            'users.name',
            'transactions.transaction_receipt_number as order_id',
            'user_ratings.id_user_rating',
            'user_ratings.suggestion',
            'user_ratings.created_at',
            'user_ratings.is_anonymous',
        )
        ->paginate($request->per_page ?? 10)
        ->toArray();

        $resData = [];
        foreach ($comment['data'] ?? [] as $val) {
            $val['created_at_indo'] = MyHelper::dateFormatInd($val['created_at'], true, false);
            if ($val['is_anonymous']) {
                $val['name'] = substr($val['name'], 0, 1) . '*****';
            }
            unset($val['is_anonymous']);
            $resData[] = $val;
        }

        $comment['data'] = $resData;

        return MyHelper::checkGet($comment);
    }

    public function listAllDoctor(Request $request)
    {
        $post = $request->json()->all();

        $doctor = Doctor::with('outlet')->orderBy('created_at', 'DESC');

        if (!empty($post['id_outlet'])) {
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
            DB::table('doctors')->update([ 'doctor_recomendation_status' => 0]);

            //new doctor recomendation
            if (!empty($ids_doctor)) {
                Doctor::whereIn('id_doctor', $ids_doctor)->update(['doctor_recomendation_status' => 1]);
            }
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
        $recomendationDoctorHistory = array();

        //1. Doctor From Consultation History
        $historyConsultation = Transaction::where('id_user', $user->id)->where('trasaction_type', 'consultation')->get();
        if (!empty($historyConsultation)) {
            foreach ($historyConsultation as $hc) {
                $doctorId = TransactionConsultation::where('id_transaction', $hc->id_transaction)->pluck('id_doctor');
                $doctor = Doctor::where('is_active', 1)->whereIn('id_doctor', $doctorId)->with('outlet')->with('specialists')->first();

                if (in_array($doctor, $recomendationDoctorHistory) == false && count($recomendationDoctorHistory) < 3 && $doctor) {
                    $recomendationDoctorHistory[] = $doctor;
                }
            }
        }

        //2. Doctor From Outlet Related Transaction Product History
        $historyTransaction = Transaction::where('id_user', $user->id)->where('trasaction_type', 'Delivery')->get();
        if (!empty($historyTransaction)) {
            foreach ($historyTransaction as $ht) {
                $doctor = Doctor::with('outlet')->with('specialists')->where('id_outlet', $ht->id_outlet)->first();

                if (in_array($doctor, $recomendationDoctorHistory) == false && count($recomendationDoctorHistory) < 3 && $doctor) {
                    $recomendationDoctorHistory[] = $doctor;
                }
            }
        }

        $recomendationDoctorHistory = array_map("unserialize", array_unique(array_map("serialize", $recomendationDoctorHistory)));

        //3. From Setting
        $doctorRecomendationDefault = Doctor::where('is_active', 1)->with('outlet')->with('specialists')->where('doctor_recomendation_status', 1)->orderBy('created_at', 'desc')->get();

        if (empty($doctorRecomendationDefault)) {
            $recomendationDoctor = $recomendationDoctorHistory;
        } else {
            $recomendationDoctor = $doctorRecomendationDefault;
        }

        return response()->json(['status'  => 'success', 'result' => $recomendationDoctor]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function getScheduleDoctor($id_doctor, $type = null)
    {
        if ($type == 'admin' | $type == 'doctor') {
            $doctor_schedule = DoctorSchedule::where('id_doctor', $id_doctor)->with('schedule_time')->orderBy('order', 'ASC');
        } else {
            $doctor_schedule = DoctorSchedule::where('id_doctor', $id_doctor)->with('schedule_time')->onlyActive();
        }

        $doctor_schedule = $doctor_schedule->get()->toArray();

        //problems hereee
        $schedule = array();
        if (!empty($doctor_schedule)) {
            $i = 0;
            while (count($schedule) < 4) {
                if ($i > 0) {
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

                foreach ($doctor_schedule as $row) {
                    if (strtolower($row['day']) == $day) {
                        $row['date'] = $date;
                        $row['day'] = $dayId;

                        foreach ($row['schedule_time'] as $key2 => $time) {
                            $post['time'] = date("H:i:s", strtotime($time['start_time']));

                            //cek validation avaibility time from current time
                            $nowTime = date("H:i:s");
                            $nowDate = date('d-m-Y');

                            //cek validation avaibility time from consultation
                            $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $post['date'])
                            ->whereNotIn('consultation_status', ['canceled', 'done'])
                            ->where('schedule_start_time', $post['time'])->count();
                            $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
                            $quota = $getSetting['value'];

                            if ($post['time'] < $nowTime && strtotime($date) <= strtotime($nowDate)) {
                                $row['schedule_time'][$key2]['status_session'] = "disable";
                                $row['schedule_time'][$key2]['disable_reason'] = "Waktu Sudah Terlewati";
                            } else {
                                if ($quota <= $doctor_constultation && $quota != null) {
                                    $row['schedule_time'][$key2]['status_session'] = "disable";
                                    $row['schedule_time'][$key2]['disable_reason'] = "Kuota Sudah Penuh";
                                    $row['schedule_time'][$key2]['quota'] = $quota;
                                    $row['schedule_time'][$key2]['count'] = $doctor_constultation;
                                } else {
                                    $row['schedule_time'][$key2]['status_session'] = "available";
                                    $row['schedule_time'][$key2]['disable_reason'] = null;
                                    $row['schedule_time'][$key2]['quota'] = $quota;
                                    $row['schedule_time'][$key2]['count'] = $doctor_constultation;
                                }
                            }
                        }

                        $schedule[] = $row;
                    }
                }
            }
        }

        return $schedule;
    }

    public function getAvailableScheduleDoctor($id_doctor)
    {
        $doctor_schedule = DoctorSchedule::where('id_doctor', $id_doctor)->with('schedule_time')->onlyActive();

        $doctor_schedule = $doctor_schedule->get()->toArray();

        //problems hereee
        $schedule = array();
        if (!empty($doctor_schedule)) {
            $i = 0;
            while (count($schedule) < 4) {
                if ($i > 0) {
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

                foreach ($doctor_schedule as $key => $row) {
                    if (strtolower($row['day']) == $day) {
                        $row['date'] = $date;
                        $row['day'] = $dayId;

                        $is_avaible = false;
                        foreach ($row['schedule_time'] as $key2 => $time) {
                            $post['time'] = date("H:i:s", strtotime($time['start_time']));

                            //cek validation avaibility time from current time
                            $nowTime = date("H:i:s");
                            $nowDate = date('d-m-Y');

                            //cek validation avaibility time from consultation
                            $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $post['date'])
                            ->where('schedule_start_time', $post['time'])->count();
                            $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
                            $quota = $getSetting['value'];


                            if ($post['time'] < $nowTime && strtotime($date) <= strtotime($nowDate)) {
                                //
                            } else {
                                if ($quota <= $doctor_constultation && $quota != null) {
                                    //
                                } else {
                                    $is_avaible = true;
                                }
                            }
                        }

                        if ($is_avaible == true) {
                            $schedule[] = $row;
                        }
                    }
                }
            }
        }

        return $schedule;
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function getScheduleTime($id_doctor_schedule)
    {
        $timeSchedule = TimeSchedule::where('id_doctor_schedule', $id_doctor_schedule)->get()->toArray();

        return $timeSchedule;
    }

    public function getAvailableScheduleTime($id_doctor_schedule, $date)
    {
        $timeSchedule = TimeSchedule::where('id_doctor_schedule', $id_doctor_schedule)->get()->toArray();
        foreach ($timeSchedule as $key => $time) {
            $post['time'] = date("H:i:s", strtotime($time['start_time']));

            //cek validation avaibility time from current time
            $nowTime = date("H:i:s");
            $nowDate = date('d-m-Y');

            //cek validation avaibility time from consultation
            $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor_schedule)->where('schedule_date', $date)
            ->where('schedule_start_time', $post['time'])->count();
            $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
            $quota = $getSetting['value'];

            if ($post['time'] < $nowTime && strtotime($date) <= strtotime($nowDate)) {
                unset($timeSchedule[$key]);
            } else {
                if ($quota <= $doctor_constultation && $quota != null) {
                    unset($timeSchedule[$key]);
                }
            }
        }

        return $timeSchedule;
    }

    public function listOutletOption(Request $request)
    {
        $idsOutletDoctor = Doctor::onlyActive()->get()->pluck('id_outlet');

        $outlets = Outlet::whereIn('id_outlet', $idsOutletDoctor)->get();

        if (empty($outlets)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet not found']
            ]);
        }

        $outlets = $outlets->toArray();

        $result = [];

        foreach ($outlets as $key => $outlet) {
            $result[$key]['id_outlet'] = $outlet['id_outlet'];
            $result[$key]['outlet_name'] = $outlet['outlet_name'];
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function listAllOutletWithDoctor(Request $request)
    {
        $outlets = Outlet::with(['doctors' => function ($query) {
            $query->where('is_active', 1);
        }])->where('outlet_status', 'active')->get();

        if (empty($outlets)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet not found']
            ]);
        }

        $result = [];

        foreach ($outlets as $key => $outlet) {
            if (!$outlet->doctors->count()) {
                continue;
            }
            $outletData = [
                'id_outlet' => $outlet->id_outlet,
                'outlet_name' => $outlet->outlet_name,
                'outlet_address' => $outlet->outlet_full_address,
                'url_outlet_image_logo_landscape' => $outlet->url_outlet_image_logo_landscape,
                'url_outlet_image_logo_portrait' => $outlet->url_outlet_image_logo_portrait,
                'url_outlet_image_cover' => $outlet->url_outlet_image_cover,
                'doctor_count' => $outlet->doctors->count()
            ];

            $result[] = $outletData;
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

    public function cronUpdateDoctorStatus()
    {
        $log = MyHelper::logCron('Update Doctor Status');
        try {
            $earlyEnter = (Setting::where('key', 'consultation_starts_early')->value('value') ?? 0) * 60;
            $lateEnter = (Setting::where('key', 'consultation_starts_late')->value('value')  ?? 0) * 60;
            $maxQuota = (Setting::where('key', 'max_consultation_quota')->value('value') ?? 1);
            $day = date('l');
            $now = date('H:i:s');

            //update doctor status to online
            $doctorOnline = Doctor::join('doctor_schedules', 'doctors.id_doctor', 'doctor_schedules.id_doctor')
                ->join('time_schedules', 'doctor_schedules.id_doctor_schedule', 'time_schedules.id_doctor_schedule')
                ->where('doctor_schedules.is_active', 1)
                ->where('doctor_schedules.day', '=', $day)
                ->whereTime('start_time', '<=', date('H:i:s', time() + $earlyEnter))
                ->whereTime('end_time', '>=', $now)->pluck('doctors.id_doctor')->toArray();

            $doctorOnline = array_unique($doctorOnline);

            Doctor::whereIn('id_doctor', $doctorOnline)->update(['doctor_status' => 'Online']);
            Doctor::whereNotIn('id_doctor', $doctorOnline)->update(['doctor_status' => 'Offline']);

            // SELECT id_doctor, count(*) FROM konsulin_db.transaction_consultations where consultation_status = 'ongoing' group by id_doctor;
            $idsDoctorBusy = TransactionConsultation::selectRaw('id_doctor, count(*) as total')->where('consultation_status', 'ongoing')->groupBy('id_doctor')->having('total', '>=', $maxQuota)->pluck('id_doctor');


            $doctorBusy = Doctor::whereIn('id_doctor', $idsDoctorBusy)->update(['doctor_status' => 'busy']);

            // //update doctor status to offline
            // $doctorDoesntHaveDay = Doctor::whereDoesntHave('schedules', function($query) use ($day, $now) {
            //     $query->where('day', '=' , $day);
            // })->update(['doctor_status' => 'offline']);

            // $doctorDoesntHaveTime = Doctor::whereHas('schedules', function($query) use ($day, $now) {
            //     $query->where('day', '=' , $day);
            //     $query->whereDoesntHave('schedule_time', function($query2) use ($now){
            //         $query2->whereTime('start_time', '<=' , date('H:i:s', time() + $earlyEnter));
            //         $query2->whereTime('end_time', '>=' , $now);
            //     });
            // })->update(['doctor_status' => 'offline']);

            // //check if doctor have ongoing transaction
            // $idsDoctorBusy = TransactionConsultation::whereIn('consultation_status', ['ongoing', 'done'])->pluck('id_doctor')->toArray();

            // $doctorBusy = Doctor::whereIn('id_doctor', $idsDoctorBusy)->update(['doctor_status' => 'busy']);

            $log->success(['status_update' => 'success']);
            return 'success';
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }
}
