<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

class ApiOutletWebview extends Controller
{
    public function detailWebview(Request $request, $id)
    {
    	$bearer = $request->header('Authorization');
        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }

        // if ($request->isMethod('get')) {
        //     return view('error', ['msg' => 'Url method is POST']);
        // }
        
        $list = MyHelper::postCURLWithBearer('api/outlet/list?log_save=0', ['id_outlet' => $id], $bearer);
        // return $list;
        return view('outlet::webview.list', ['data' => $list['result']]);
    }

    public function detailOutlet(Request $request)
    {
    	$bearer = $request->header('Authorization');
        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }
        
        // $list = MyHelper::postCURLWithBearer('api/outlet/list?log_save=0', [
        //     'id_outlet' => $request->id_outlet,
        //     'latitude' => $request->latitude,
        //     'longitude' => $request->longitude
        // ], $bearer);

        
        $outlet = Outlet::with(['outlet_schedules'])
        ->where('id_outlet', $request->id_outlet)->get()->toArray()[0];

        $outlet['distance']=number_format((float)$this->distance($request->latitude, $request->longitude, $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K"), 2, '.', '').' km';

        foreach ($outlet['outlet_schedules'] as $key => $value) {
            switch ($value['day']) {
                case 'Minggu':
                    $day = 'Sun';
                    break;
                case 'Senin':
                    $day = 'Mon';
                    break;
                case 'Selasa':
                    $day = 'Tue';
                    break;
                case 'Rabu':
                    $day = 'Wed';
                    break;
                case 'Kamis':
                    $day = 'Thu';
                    break;
                case 'Jumat':
                    $day = 'Fri';
                    break;
                case 'Sabtu':
                    $day = 'Sat';
                    break;
            }
            if (date('D') == $day) {
                $outlet['outlet_schedules'][$key] = [
                    'is_today'  => 1,
                    'day'       => $value['day'],
                    'time'      => $value['open'] . ' - ' . $value['close']
                ];
            } else {
                $outlet['outlet_schedules'][$key] = [
                    'day'       => $value['day'],
                    'time'      => $value['open'] . ' - ' . $value['close']
                ];
            }
        }
        
        unset($outlet['url']);
        unset($outlet['detail']);
        unset($outlet['created_at']);
        unset($outlet['updated_at']);

        return response()->json(MyHelper::checkGet($outlet));
    }

    function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        $theta = $lon1 - $lon2;
        $lat1=floatval($lat1);
        $lat2=floatval($lat2);
        $lon1=floatval($lon1);
        $lon2=floatval($lon2);
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit  = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    public function listOutletGofood(Request $request)
    {
    	$bearer = $request->header('Authorization');
        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }

        // if ($request->isMethod('get')) {
        //     return view('error', ['msg' => 'Url method is POST']);
        // }

    	$post = $request->all();
    	// $post['latitude'] = '-7.803761';
     //    $post['longitude'] = '110.383058';
        $post['sort'] = 'Nearest';
        $post['type'] = 'transaction';
        $post['search'] = '';
    	$post['gofood'] = 1;
        // return $post;
        // $list = MyHelper::post('outlet/filter', $post);
        $list = MyHelper::postCURLWithBearer('api/outlet/filter/gofood', $post, $bearer);
        // return $list;
        if (isset($list['status']) && $list['status'] == 'success') {
            return view('outlet::webview.outlet_gofood_v2', ['outlet' => $list['result']]);
        } elseif (isset($list['status']) && $list['status'] == 'fail') {
            return view('error', ['msg' => 'Data failed']);
        } else {
            return view('error', ['msg' => 'Something went wrong, try again']);
        }
    }
}
