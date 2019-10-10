<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Models\LogActivitiesPosTransaction;
use App\Lib\MyHelper;
use Auth;

class LogActivitiesPOSTransactionMiddleware
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
        $response = $next($request);

        $arrReq = $request->except('_token');
        if(!isset($arrReq['log_save'])){

            if(!isset($arrReq['page']) || (int)$arrReq['page'] <= 1){

                $user = json_encode($request->user());
                $url = $request->url();
                $user = json_decode(json_encode($request->user()), true);
                $st = stristr(json_encode($response),'success');
                $status = 'fail';
                if($st) $status = 'success';
                $reqnya = $request->json()->all();
                if(isset($reqnya['pin'])) $reqnya['pin'] = "******";
                if(isset($reqnya['pin_old'])) $reqnya['pin'] = "******";
                if(isset($reqnya['pin_new'])) $reqnya['pin'] = "******";
                $requestnya = json_encode($reqnya);
                $requeste = json_decode($requestnya, true);

                $phone = null;
                if(isset($requeste['phone']))
                    $phone = $requeste['phone'];

                if(isset($user['phone']))
                    $phone = $user['phone'];

                if(Auth::user()){
                    $datauser = Auth::user();
                    if(isset($datauser['phone']))
                        $phone = $datauser['phone'];
                }
                
                $outletCode = null;
                if(isset($request['store_code'])){
                    $outletCode = $request['store_code'];
                }
                
                if($requestnya == '[]') $requestnya = null;
                $urlexp = explode('/',$url);

                if(isset($urlexp[6])){
                    $module = $urlexp[6];
                }elseif(isset($urlexp[4])){
                    $module = $urlexp[4];
                }
                
                if(stristr($url, 'v1/pos')) $module = 'POS';

                $subject = "Unknown";
                
                if(!empty($request->header('ip-address-view'))){
                    $ip = $request->header('ip-address-view');
                }else{
                    $ip = $request->ip();
                }

                if(!empty($request->header('user-agent-view'))){
                    $userAgent = $request->header('user-agent-view');
                    if(stristr($userAgent,'iOS') || stristr($userAgent,'okhttp')){
                    }else{
                        if(!stristr($url, 'complete-profile')){
                            $subject = "BE ".$subject;
                        }
                    }
                }else{
                    $userAgent = $request->header('user-agent');
                }
                
                $dtUser = null;
                
                if(!empty($user) && $user != ""){
                    $dtUser = json_encode($request->user());
                }
                
                $data = [
                    'url' 		=> $url,
                    'outlet_code' 	=> $outletCode,
                    'user' 		=> $dtUser,
                    'request' 		=> $requestnya,
                    'response_status'   => $status,
                    'response' 		=> json_encode($response),
                    'ip' 		=> $ip,
                    'useragent' 	=> $userAgent
                ];

                $log = LogActivitiesPosTransaction::create($data);
            }

        }
        return $response;
    }
}
