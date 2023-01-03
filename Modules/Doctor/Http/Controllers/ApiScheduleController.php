<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\TimeSchedule;
use Validator;
use DB;

class ApiScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        if (empty($post['id_doctor'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id doctor can not be empty']
            ]);
        }

        $doctorSchedule = DoctorSchedule::where('id_doctor', $post['id_doctor'])->with('schedule_time')->orderBy('order', 'ASC');

        $doctorSchedule = $doctorSchedule->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $doctorSchedule]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        //collect id_doctor_schedules
        $ids_doctor_schedule = [];
        foreach ($post['schedules'] as $sec) {
            if (isset($sec['id_doctor_schedule'])) {
                $ids_doctor_schedule[] = $sec['id_doctor_schedule'];
            }
        }

        //get Deleted Schedule
        $idDeletedSchedule = DoctorSchedule::where('id_doctor', $post['id_doctor'])->whereNotIn('id_doctor_schedule', $ids_doctor_schedule)->pluck('id_doctor_schedule')->toArray();

        if (!empty($idDeletedSchedule)) {
            $deleteTime = TimeSchedule::whereIn('id_doctor_schedule', $idDeletedSchedule)->delete();
            $deletedSchedule = DoctorSchedule::whereIn('id_doctor_schedule', $idDeletedSchedule)->delete();
        }

        DB::beginTransaction();
        foreach ($post['schedules'] as $key => $schedule) {
            if (!empty($schedule['id_doctor_schedule'])) {
                //try update schedule
                $postSchedule = [
                    'id_doctor' => $post['id_doctor'],
                    'day' => $schedule['day'],
                    'is_active' => (empty($schedule['session_time']) ? 0 : $schedule['is_active'])
                ];
                $updateSchedule = DoctorSchedule::where('id_doctor_schedule', $schedule['id_doctor_schedule'])->update($postSchedule);

                $getSchedule = DoctorSchedule::where('id_doctor_schedule', $schedule['id_doctor_schedule'])->first();
                //drop and save schedule time
                $oldTime = TimeSchedule::where('id_doctor_schedule', $getSchedule['id_doctor_schedule'])->delete();
                if (isset($schedule['session_time'])) {
                    $getSchedule->schedule_time()->createMany($schedule['session_time']);
                }
                try {
                } catch (\Exception $e) {
                    $result = [
                        'status'  => 'fail',
                        'message' => 'Update Doctor Schedule Failed'
                    ];
                    DB::rollBack();
                    return response()->json($result);
                }
            } else {
                //try create schedule
                $postSchedule = [
                    'id_doctor' => $post['id_doctor'],
                    'day' => $schedule['day'],
                    'is_active' => (empty($schedule['session_time']) ? 0 : $schedule['is_active']),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $saveSchedule = DoctorSchedule::updateOrCreate(['id_doctor' => $post['id_doctor'], 'day' => $schedule['day']], $postSchedule);

                $getSchedule = DoctorSchedule::where('id_doctor_schedule', $saveSchedule['id_doctor_schedule'])->first();

                //try create schedule time
                if (isset($schedule['session_time'])) {
                    $getSchedule->schedule_time()->createMany($schedule['session_time']);
                }

                $schedule = $saveSchedule;
                try {
                } catch (\Exception $e) {
                    $result = [
                        'status'  => 'fail',
                        'message' => 'Create Doctor Schedule Failed'
                    ];
                    DB::rollBack();
                    return response()->json($result);
                }
            }
        }
        DB::commit();

        $result = DoctorSchedule::where('id_doctor', $post['id_doctor'])->get();

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            //TO DO add valdation when exists in doctor schedule time
            $id_doctor_schedule = $request->json('id_doctor_schedule');

            $doctorSchedule = DoctorSchedule::where('id_doctor_schedule', $id_doctor_schedule)->first();

            //delete data child table
            $doctorSchedule->schedule_time()->delete();

            //delete data table
            $delete = $doctorSchedule->delete();
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

        if (empty($post['id_doctor'])) {
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
        while (count($schedule) < 4) {
            if ($i > 0) {
                $date = date("Y-m-d", strtotime("+$i day"));
                $day = strtolower(date("l", strtotime($date)));
                $test[] = $date;
            } else {
                $date = date("Y-m-d");
                $day = strtolower(date("l", strtotime($date)));
                $test[] = $date;
            }
            $i += 1;

            foreach ($doctor_schedule as $row) {
                if ($row['day'] == $day) {
                    $row['date'] = $date;
                    $schedule[] = $row;
                }
            }
        }

        return response()->json(['status'  => 'success', 'result' => $schedule]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function getMySchedule(Request $request)
    {
        $user = $request->user();

        $doctor_schedule = DoctorSchedule::where('id_doctor', $user['id_doctor'])->with('schedule_time')->orderBy('order', 'ASC');

        $doctor_schedule = $doctor_schedule->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $doctor_schedule]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function storeMySchedule(Request $request)
    {
        $posts = $request->json()->all();

        $user = $request->user();

        //translate day to english day
        foreach ($posts as $key => $value) {
            $date = date('l', strtotime($value['day']));

            $posts[$key]['day'] = $date;

            //sorting session time
            if (isset($value['session_time'])) {
                usort($value['session_time'], function ($a, $b) {
                    return strtotime($a['start_time']) <=> strtotime($b['start_time']);
                });

                $posts[$key]['session_time'] = $value['session_time'];
            }

            //check same session time
            if (isset($value['session_time'])) {
                $endTime = null;
                foreach ($posts[$key]['session_time'] as $key => $time) {
                    if ($time['start_time'] > $time['end_time']) {
                        return response()->json(['status'  => 'fail', 'messages' => ['End time should be greater more than start time']]);
                    }

                    if ($time['start_time'] < $endTime) {
                        return response()->json(['status'  => 'fail', 'messages' => ['Session time can not be the same in one day']]);
                    }
                    $endTime = $time['end_time'];
                }
            }
        }

        // dd($posts);

        DB::beginTransaction();
        foreach ($posts as $key => $post) {
            if (isset($post['id_doctor_schedule'])) {
                try {
                    //try update schedule
                    $postSchedule = [
                        'id_doctor' => $user['id_doctor'],
                        'day' => $post['day'],
                        'is_active' => $post['is_active']
                    ];
                    $updateSchedule = DoctorSchedule::where('id_doctor_schedule', $post['id_doctor_schedule'])->update($postSchedule);

                    $schedule = DoctorSchedule::where('id_doctor_schedule', $post['id_doctor_schedule'])->first();
                    //drop and save schedule time
                    if (isset($post['session_time'])) {
                        $oldTime = TimeSchedule::where('id_doctor_schedule', $post['id_doctor_schedule'])->delete();
                        $schedule->schedule_time()->createMany($post['session_time']);
                    }
                } catch (\Exception $e) {
                    $result = [
                        'status'  => 'fail',
                        'message' => 'Update Doctor Schedule Failed'
                    ];
                    DB::rollBack();
                    return response()->json($result);
                }
            } else {
                try {
                    //try create schedule
                    $postSchedule = [
                        'id_doctor' => $user['id_doctor'],
                        'day' => $post['day'],
                        'is_active' => $post['is_active'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $saveSchedule = DoctorSchedule::updateOrCreate(['id_doctor' => $user['id_doctor'], 'day' => $post['day']], $postSchedule);

                    //try create schedule time
                    if (isset($post['session_time'])) {
                        $saveSchedule->schedule_time()->createMany($post['session_time']);
                    }

                    $schedule = $saveSchedule;
                } catch (\Exception $e) {
                    $result = [
                        'status'  => 'fail',
                        'message' => 'Create Doctor Schedule Failed'
                    ];
                    DB::rollBack();
                    return response()->json($result);
                }
            }
        }
        DB::commit();
        return response()->json(['status'  => 'success', 'result' => $schedule]);
    }
}
