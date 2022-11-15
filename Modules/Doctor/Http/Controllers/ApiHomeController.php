<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\DoctorDevice;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use DateTime;

class ApiHomeController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->doctor = "Modules\Doctor\Http\Controllers\ApiDoctorController";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function home(Request $request)
    {
        $user = $request->user();

        //update Device Token
        if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')) {
            $device = $this->updateDeviceUserGuest($request->json('device_id'), $request->json('device_token'), $request->json('device_type'), $user);
        }

        //get detail doctor
        $doctor = Doctor::with('specialists')->with('clinic')->where('id_doctor', $user['id_doctor'])->first();

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
        foreach ($doctor['specialists'] as $key => $specialists) {
            if ($key == 0) {
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
            "total_rating" => $doctor['total_rating']
        ];

        //get doctor consultation
        $id = $doctor['id_doctor'];
        $transaction = Transaction::with('consultation')->where('transaction_payment_status', 'Completed')->whereHas('consultation', function ($query) use ($id) {
            $query->where('id_doctor', $id)->onlySoon();
        })->get()->toArray();

        $now = new DateTime();

        $data_consultation = array();
        foreach ($transaction as $key => $value) {
            $user_selected = User::where('id', $value['consultation']['id_user'])->first()->toArray();

            //get diff datetime
            $now = new DateTime();
            $schedule_date_start_time = $value['consultation']['schedule_date'] . ' ' . $value['consultation']['schedule_start_time'];
            $schedule_date_start_time = new DateTime($schedule_date_start_time);
            $schedule_date_end_time = $value['consultation']['schedule_date'] . ' ' . $value['consultation']['schedule_end_time'];
            $schedule_date_end_time = new DateTime($schedule_date_end_time);
            $diff_date = null;

            //logic schedule diff date
            if ($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
                $diff = $now->diff($schedule_date_start_time);
                if ($diff->d == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
                } elseif ($diff->d == 0 && $diff->h == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
                } elseif ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
                } else {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
                }
            } elseif ($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } else {
                $diff_date = "missed";
            }

            $data_consultation[$key]['id_transaction'] = $value['id_transaction'];
            $data_consultation[$key]['id_user'] = $value['consultation']['id_user'];
            $data_consultation[$key]['user_name'] = $user_selected['name'];
            $data_consultation[$key]['user_photo'] = $user_selected['photo'];
            $data_consultation[$key]['url_user_photo'] = $user_selected['url_photo'];
            $data_consultation[$key]['schedule_date'] = $value['consultation']['schedule_date_human_formatted'];
            $data_consultation[$key]['start_time'] = $value['consultation']['schedule_start_time_formatted'];
            $data_consultation[$key]['diff_date'] = $diff_date;
        }

        //get doctor schedule
        $data_schedule = app($this->doctor)->getScheduleDoctor($user['id_doctor']);

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

    public function updateDeviceUserGuest($device_id, $device_token, $device_type, $user)
    {
        $dataUpdate = [
            'device_id'    => $device_id,
            'device_token' => $device_token,
            'device_type' => $device_type
        ];

        $checkDevice = DoctorDevice::where('device_id', $device_id)
                                ->where('device_token', $device_token)
                                ->where('device_type', $device_type)
                                ->count();
        if ($checkDevice == 0) {
            $update                = DoctorDevice::updateOrCreate(['device_id' => $device_id], [
                'device_token'      => $device_token,
                'device_type'       => $device_type,
                'id_doctor'         => $user->id_doctor
            ]);
            $result = [
                'status' => 'updated'
            ];
        } else {
            $result = [
                'status' => 'success'
            ];
        }

        $checkDevice = DoctorDevice::where('device_id', $device_id)
                                ->where('device_token', $device_token)
                                ->where('device_type', $device_type)
                                ->first();

        $result['check_device'] = $checkDevice;

        return $result;
    }
}
