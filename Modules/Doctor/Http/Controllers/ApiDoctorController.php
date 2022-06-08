<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorSpecialist;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\SubmissionChangeDoctorData;
use Modules\Doctor\Http\Requests\DoctorCreate;
use Validator;
use Image;
use DB;

class ApiDoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $countTotal = null;

        $doctor = Doctor::with('clinic')->with('specialists')->orderBy('created_at', 'DESC');

        if ($post['rule']) {
            $countTotal = $doctor->count();
            $this->filterList($doctor, $post['rule'], $post['operator'] ?: 'and');
        }

        if(isset($post['id_doctor_specialist_category'])){
            $doctor->whereHas('specialists', function($query) use ($post) {
                $query->whereHas('category', function($query2) use ($post) {
                    $query2->where('id_doctor_specialist_category', $post['id_doctor_specialist_category']);
                });
             });
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

        if($rules2=$newRule['doctor_clinic_name']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('clinic', function($query2) use ($rule) {
                    $query2->where('doctor_clinic_name', $rule[0], $rule[1]);
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

        $doctor = Doctor::with('clinic')->with('specialists')->orderBy('created_at', 'DESC');

        if(isset($post['id_doctor_specialist_category'])){
            $doctor->whereHas('specialists', function($query) use ($post) {
                $query->whereHas('category', function($query2) use ($post) {
                    $query2->where('id_doctor_specialist_category', $post['id_doctor_specialist_category']);
                });
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

        DB::beginTransaction();
        if (isset($post['id_doctor'])) {    
            try {
                //upload photo id
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
    
                //set password
                if ($post['pin'] == null) {
                    $pin = MyHelper::createRandomPIN(6, 'angka');
                    if(env('APP_ENV') != "production"){
                        $pin = '777777';
                    }
                } else {
                    $pin = $post['pin'];
                }
                unset($post['pin']);
        
                $post['password'] = bcrypt($pin);

                //get specialist id
                $specialist_id = $post['doctor_specialist'];

                unset($post['doctor_specialist']);
    
                Doctor::where('id_doctor', $post['id_doctor'])->update($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_doctor' => $post['id_doctor']]]);
        } else {
            try {
                //upload photo id
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

                //set password
                if ($post['pin'] == null) {
                    $pin = MyHelper::createRandomPIN(6, 'angka');
                    if(env('APP_ENV') != "production"){
                        $pin = '777777';
                    }
                } else {
                    $pin = $post['pin'];
                }
                unset($post['pin']);
        
                $post['password'] = bcrypt($pin);

                //get specialist id
                $specialist_id = $post['doctor_specialist'];

                unset($post['doctor_specialist']);
                
                $save = Doctor::create($post);
                $specialist = $save->specialists()->attach($specialist_id);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['doctor_name' => $post['doctor_name'], 'created_at' => $save->doctor_service]]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $doctor = Doctor::where('id_doctor', $id)->with('specialists')->first();

        $doctor_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->with('schedule_time');

        $doctor_schedule = $doctor_schedule->get()->toArray();

        $schedule = array();
        $i = 0;
        while(count($schedule) < 4){
            if($i > 0) {
                $date = date("Y-m-d", strtotime("+$i day"));
                $day = strtolower(date("l", strtotime($date)));
            } else {
                $date = date("Y-m-d");
                $day = strtolower(date("l", strtotime($date)));
            }
            $i += 1;

            foreach($doctor_schedule as $row) {
                if($row['day'] == $day) {
                    $row['date'] = $date;
                    $schedule[] = $row;
                }
            }
        }

        $doctor['schedules'] = $schedule;

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

        $doctor = Doctor::where('id_doctor', $user['id_doctor'])->with('clinic')->with('specialists')->orderBy('created_at', 'DESC');

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
}
