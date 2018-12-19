<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletReservation;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\OutletPhoto;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;


use Modules\Outlet\Http\Requests\Outlet\Reservation;

class ApiOutletReservationController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    function checkInput($post) {
        $data = [];

        if (isset($post['day'])) {
            $data['day'] = $post['day'];
        }

        if (isset($post['hour_start'])) {
            $data['hour_start'] = $post['hour_start'];
        }

        if (isset($post['hour_end'])) {
            $data['hour_end'] = $post['hour_end'];
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }
        
        if (isset($post['limit'])) {
            $data['limit'] = $post['limit'];
        }

        return $data;
    }

    function checkCreate($post) {
        $check = OutletReservation::where('id_store', $post['id_store'])->where('day', $post['day'])->count();

        if ($check > 0) {
            return false;
        }
        else {
            return true;
        }
    }

    /* CREATE */
    function create(Reservation $request) {
        $data = $this->checkInput($request->json()->all());

        // save
        $save = OutletReservation::updateOrCreate(['id_outlet' => $data['id_outlet'], 'day' => $data['day']], $data);
        return response()->json(MyHelper::checkCreate($save));
    }

    /* DELETE */
    function delete(Request $request) {

        if (is_array($request->json('id_outlet_reservation'))) {
            $delete = OutletReservation::whereIn('id_outlet_reservation', $request->json('id_outlet_reservation'))->delete();
        }
        else {
            $delete = OutletReservation::where('id_outlet_reservation', $request->json('id_outlet_reservation'))->delete();
        }

        return response()->json(MyHelper::checkDelete($delete));

    }

}
