<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorSchedule;
use App\Http\Models\Transaction;

use DateTime;

class ApiHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function home(Request $request)
    {
        $user = $request->user();

        //get detail doctpr
        $doctor = Doctor::with('specialists')->with('clinic')->with('schedules')->where('id_doctor', $user['id_doctor'])->first();

        if (empty($doctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Account Not Found']
            ]);
        }

        //convert to array
        $doctor = $doctor->toArray();

        //get detail doctor
        $specialist_name = null;
        foreach($doctor['specialists'] as $key => $specialists){
            if($key == 0) {
                $specialist_name .= $specialists['doctor_specialist_name'];
            } else {
                $specialist_name .= ", ";
                $specialist_name .= $specialists['doctor_specialist_name'];
            }
        }

        $data_doctor = [
            "name" => $doctor['doctor_name'],
            "specialist" => $specialist_name,
            "status" => $doctor['doctor_status'],
            "statisfaction_rating" => $doctor['satisfaction_rating']
        ];

        //get doctor consultation
        $id = $doctor['id_doctor'];
        $transaction = Transaction::with('consultation')->whereHas('consultation', function($query) use ($id){
            $query->where('id_doctor', $id)->onlySoon();
        })->get()->toArray();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak ada transaksi yang akan datang']
            ]);
        }

        $now = new DateTime();

        $data_consultation = array();
        foreach($transaction as $key => $value) {
            $schedule_date_time = $value['consultation']['schedule_date'] .' '. $value['consultation']['schedule_start_time'];
            $schedule_date_time =new DateTime($schedule_date_time);
            $diff_date = "missed";
            if($schedule_date_time > $now) {
                $diff_date = $now->diff($schedule_date_time)->format("%d days, %h hours and %i minuts");
            }

            $data_consultation[$key]['id_transaction'] = $value['id_transaction'];
            $data_consultation[$key]['id_doctor'] = $value['consultation']['id_doctor'];
            $data_consultation[$key]['doctor_name'] = $doctor['doctor_name'];
            $data_consultation[$key]['doctor_photo'] = $doctor['doctor_photo'];
            $data_consultation[$key]['schedule_date'] = $value['consultation']['schedule_date'];
            $data_consultation[$key]['diff_date'] = $diff_date;
        }

        //get doctor schedule
        $doctor_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->with('schedule_time');

        $doctor_schedule = $doctor_schedule->get()->toArray();

        $data_schedule = array();
        $i = 0;
        while(count($data_schedule) < 4){
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
                    $data_schedule[] = $row;
                }
            }
        }

        $result = [
            "data_doctor" => $data_doctor, 
            "data_consultation" => $data_consultation, 
            "data_schedule" => $data_schedule
        ];

        return response()->json([
            'status'  => 'success', 
            'result' => $result 
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('doctor::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('doctor::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('doctor::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
