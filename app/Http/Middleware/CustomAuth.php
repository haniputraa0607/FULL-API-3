<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;
use App\Lib\MyHelper;
use App\Http\Models\Outlet;

use Illuminate\Http\Request;

class CustomAuth extends AddCustomProvider
{
    
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if($request->get('outlet-app')){
            $checkDeviceLocation = env('CHECK_DEVICE_LOCATION', false);
            if($checkDeviceLocation){
                $error_message_list = [];
                if(!$request->has('username'))
                    array_push($error_message_list, 'please provide username');
                if(!$request->has('device_longitude'))
                    array_push($error_message_list, 'please provide device_longitude');
                if(!$request->has('device_latitude'))
                    array_push($error_message_list, 'please provide device_latitude');
                if(!empty($error_message_list)){
                    $error_message = implode(', ', $error_message_list);
                    return response()->json(['error' => 'Unauthenticated: '.$error_message.'.'], 401);
                }
                $username = $request->get('username');
                $device_longitude = $request->get('device_longitude');
                $device_latitude = $request->get('device_latitude');
    
                $outlet = Outlet::where('outlet_code', $username)->first();
                if(empty($outlet))
                    return response()->json(['error' => 'Unauthenticated: outlet not found.'], 401);
                else
                    $outlet = $outlet->toArray();
    
                $distance = MyHelper::getDistance((double)$device_latitude, (double)$device_longitude, (double)$outlet['outlet_latitude'], (double)$outlet['outlet_longitude']);
                if($distance > 100)
                    return response()->json(['error' => 'Unauthenticated: the device is too far from the outlet.'], 401);   
            }
            
            $request->merge(['provider' => env('','outlet-app')]);
        }elseif($request->get('user-franchise')){
            $request->merge(['provider' => 'user-franchise']);
        }elseif($request->get('quinos')){
            $request->merge(['provider' => 'quinos']);
        }else{
            $request->merge(['provider' => 'users']);
        }
        return parent::handle($request, $next);
    }

}
