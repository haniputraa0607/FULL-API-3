<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Models\OutletToken;
use App\Http\Models\Outlet;
use App\Lib\MyHelper;

class VerifyOutletDeviceLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // initiate parameters
        $error_message_list = [];
        if(!$request->has('device_id'))
            array_push($error_message_list, 'please provide device_id');
        if(!$request->has('device_longitude'))
            array_push($error_message_list, 'please provide device_longitude');
        if(!$request->has('device_latitude'))
            array_push($error_message_list, 'please provide device_latitude');
        if(!empty($error_message_list)){
            $error_message = implode(', ', $error_message_list);
            return response()->json(['error' => 'Unauthenticated: '.$error_message.'.'], 401);
        }
        $device_id = $request->get('device_id');
        $device_longitude = $request->get('device_longitude');
        $device_latitude = $request->get('device_latitude');

        // check for token
        $token = $request->bearerToken();
        $outletToken = OutletToken::where('token', $token)->first();
        if(empty($outletToken))
            return response()->json(['error' => 'Unauthenticated: invalid token.'], 401);
        else
            $outletToken = $outletToken->toArray();

        // check if device_id match the token
        if($device_id != $outletToken['device_id'])
            return response()->json(['error' => 'Unauthenticated: invalid device_id.'], 401);
        
        // check for the distance
        $outlet = Outlet::where('id_outlet', $outletToken['id_outlet'])->first()->toArray();
        $distance = MyHelper::getDistance((double)$device_latitude, (double)$device_longitude, (double)$outlet['outlet_latitude'], (double)$outlet['outlet_longitude']);
        if($distance > 100)
            return response()->json(['error' => 'Unauthenticated: the device is too far from the outlet.'], 401);
        
        return $next($request);
    }
}
