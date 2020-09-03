<?php

namespace Modules\Shift\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Outlet;
use Modules\Shift\Entities\Shift;
use Modules\Shift\Entities\UserOutletApp;

class ShiftController extends Controller
{
    public function index(Request $request){
        $post = $request->json()->all();

        $data_shift = Shift::with([
                'outlet' => function($query){
                    $query->select('id_outlet','outlet_code', 'outlet_name');
                },
                'user_outletapp'
            ]);
  
        if(!empty($post)){
            /* Jika user melakukan filter data Shift */

            if (isset($post['shift_start_date'])  && isset($post['shift_end_date'])) {
                $data_shift = $data_shift->whereBetween('created_at', [ date($post['shift_start_date']), date($post['shift_end_date']) ]);
            }

            if (isset($post['cash_start']['range_start']) && isset($post['cash_start']['range_end'])) {
                $data_shift = $data_shift->where('cash_start', '>=', $post['cash_start']['range_start'])->where('cash_start', '<=', $post['cash_start']['range_end']);
            }

            if (isset($post['cash_end']['range_start']) && isset($post['cash_end']['range_end'])) {
                $data_shift = $data_shift->where('cash_end', '>=', $post['cash_end']['range_start'])->where('cash_end', '<=', $post['cash_end']['range_end']);
            }

            if (isset($post['cash_difference']['range_start']) && isset($post['cash_difference']['range_end'])) {
                $data_shift = $data_shift->where('cash_difference', '>=', $post['cash_difference']['range_start'])->where('cash_difference', '<=', $post['cash_difference']['range_end']);
            }

            if (isset($post['id_outlet'])) {
                $data_shift = $data_shift->where('id_outlet', '=', $post['id_outlet']);
            }

            if (isset($post['id_user_outletapp'])) {
                $data_shift = $data_shift->where('id_user_outletapp', '=', $post['id_user_outletapp']);
            }


            if(isset($post['difference_status'])){
                if($post['difference_status'] == 0){
                    $data_shift = $data_shift->whereRaw('cash_end - cash_start = 0');
                }else{
                    $data_shift = $data_shift->whereRaw('cash_end - cash_start > 0')->orWhereRaw('cash_end - cash_start < 0');
                }
            }
        }

        return response()->json(MyHelper::checkGet($data_shift->get()));
    }



}
