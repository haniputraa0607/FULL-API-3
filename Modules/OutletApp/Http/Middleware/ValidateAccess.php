<?php

namespace Modules\OutletApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Lib\MyHelper;
use Modules\OutletApp\Entities\OutletAppOtp;
use App\Http\Models\UserOutlet;

class ValidateAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $feature)
    {
        if($user = $this->getUserOutlet($feature,$request->pin,$request->user())){
            $request->merge(['user_outlet'=>$user]);
            return $next($request);
        }
        return MyHelper::checkGet([],'PIN tidak sesuai');
    }
    public function getUserOutlet($feature,$pin,$outlet) {
        $otps = OutletAppOtp::where(['id_outlet'=>$outlet->id,'feature'=>$feature])->get();
        $user =null;
        foreach ($otps as $otp) {
            $verify = password_verify($pin, $otp->pin);
            if($verify){
                $user = UserOutlet::where('id_user_outlet',$otp->id_user_outlet)->first();
            }
        }
        return $user;
    }
}
