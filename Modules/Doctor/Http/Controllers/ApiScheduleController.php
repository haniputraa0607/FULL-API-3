<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use Modules\Doctor\Entities\DoctorSchedule;
use Validator;
use DB;

class ApiScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $scheduleTime = ScheduleTime::orderBy('created_at', 'ASC');

        $scheduleTime = $scheduleTime->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $scheduleTime]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(ScheduleTimeCreate $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);
 
        DB::beginTransaction();
        if (isset($post['id_schedule_time'])) {
            try {
                ScheduleTime::where('id_schedule_time', $post['id_schedule_time'])->update($post); 
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Schedule Time Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_schedule_time' => $post['id_schedule_time']]]);
        } else {
            try {
                $save = ScheduleTime::create($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Clinic Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            //dd($save);
            return response()->json(['status'  => 'success', 'result' => ['time' => $post['time'], 'created_at' => $save->created_at]]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        try {
            //TO DO add valdation when exists in doctor schedule time
            $id_schedule_time = $request->json('id_schedule_time');
            $scheduleTime = DoctorClinic::where('id_schedule_time', $id_schedule_time)->first();
            $delete = $scheduleTime->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['ScheduleTime has been used.']
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function getSchedule(Request $request)
    {
        $post = $request->json()->all();

        if(empty($post['id_doctor'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id doctor can not be empty']
            ]);
        }

        $doctor_schedule = DoctorSchedule::where('id_doctor', $post['id_doctor'])->with('schedule_time');

        $doctor_schedule = $doctor_schedule->get()->toArray();

        $schedule = array();
        $i = 0;
        $test = array();
        while(count($schedule) < 4){
            if($i > 0) {
                $date = date("Y-m-d", strtotime("+$i day"));
                $day = strtolower(date("l", strtotime($date)));
                $test[] = $date;
            } else {
                $date = date("Y-m-d");
                $day = strtolower(date("l", strtotime($date)));
                $test[] = $date;
            }
            $i += 1;

            foreach($doctor_schedule as $row) {
                if($row['day'] == $day) {
                    $row['date'] = $date;
                    $schedule[] = $row;
                }
            }
        }

        return response()->json(['status'  => 'success', 'result' => $schedule]);
    }
}