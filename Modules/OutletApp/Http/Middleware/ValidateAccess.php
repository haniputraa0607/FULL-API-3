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
    public function handle(Request $request, Closure $next, $feature, $validator = null)
    {
        $errors = ['Something went wrong'];
        $validator = $validator ?: MyHelper::setting('outlet_apps_access_feature','value','otp');
        if ($validator == 'off') {
            $request->merge(['validator' => null,'user_outlet' => ['id_user' => null, 'user_type' => null, 'name' => null, 'email' => null]]);
            return $next($request);
        } elseif ($validator == 'seeds') {
            $validate = $this->getUserSeed($feature,$request,$request->user(),$errors);
        } else {
            $validate = $this->getUserOutlet($feature,$request->otp,$request->user());
            if (!$validate) {
                $errors = ['OTP tidak sesuai'];
            }
        }
        if($validate){
            $request->merge(['validator'=>$validator, 'user_outlet'=>$validate['user'], 'outlet_app_otps'=>$validate['otp']]);
            return $next($request);
        }
        return [
            'status' => 'fail',
            'messages' => $errors
        ];
    }
    public function getUserOutlet($feature,$pin,$outlet) {
        $otps = OutletAppOtp::where(['id_outlet'=>$outlet->id_outlet,'feature'=>$feature,'used'=>0])
        ->whereRaw('UNIX_TIMESTAMP(created_at) >= ?',[time()-(60*5)])
        ->get();
        $user =null;
        foreach ($otps as $otp) {
            $verify = password_verify($pin, $otp->pin);
            if($verify){
                $otp->update(['used'=>1]);
                $user = UserOutlet::where('id_user_outlet',$otp->id_user_outlet)->first()->toArray();
                $user['user_type'] = 'user_outlets';
                $user['id_user'] = $user['id_user_outlet'];
                break;
            }
        }
        return $user?['user'=>$user,'otp'=>$otp]:$user;
    }
    public function getUserSeed($feature,$request,$outlet,&$errors) {
        $url = env('POS_URL').'auth';
        $bearer = MyHelper::post($url,null,[
            'key'       => env('POS_KEY'),
            'secret'    => env('POS_SECRET')
        ])['result']['access_token']??'';
        $verify_endpoint = env('POS_URL').'user-verification';
        $user = null;

        if ($request->json('auth')) {
            $verify = MyHelper::post($verify_endpoint,'Bearer '.$bearer,[
                'email'     => $request->auth['email']??'',
                'pin'       => $request->auth['pin']??'',
                'id_outlet' => $outlet->id_outlet_seed
            ]);
            if($verify['status'] == 'success') {
                $user = [
                    'id_user' => null,
                    'user_type' => 'seeds',
                    'name' => $verify['result']['name']??'',
                    'email' => $verify['result']['email']??''
                ];
            }else{
                $msg = $verify['messages'][0]??'Something went wrong';
                if(strrpos($msg, '#UV404')){
                    $msg = 'Kombinasi email dan pin salah';
                }elseif(strrpos($msg, '#UV114')){
                    $msg = 'User tidak memiliki akses ke Outlet ini';
                }
                $errors = [$msg];
            }
        }

        return $user?['user'=>$user,'otp'=>null]:$user;
    }
}
