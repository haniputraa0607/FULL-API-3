<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Models\LogActivitiesOutletApps;
use App\Lib\MyHelper;
use Auth;

class LogActivitiesOutletAppsMiddleware
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
                
                $outletCode = null;
                if(isset($user['outlet_code'])){
                    $outletCode = $user['outlet_code'];
                }
                
                if($requestnya == '[]') $requestnya = null;
                $urlexp = explode('/',$url);

                if(isset($urlexp[6])){
                    $module = $urlexp[6];
                }elseif(isset($urlexp[4])){
                    $module = $urlexp[4];
                }
                
                
                if(stristr($url, 'outletapp')) $module = 'Outlet App';
                
                if(stristr($url, 'outletapp')) $subject = 'Outlet App';
                if(stristr($url, 'outletapp/order')) $subject = 'Outlet Order';
                if(stristr($url, 'outletapp/order/detail')) $subject = 'Outlet Order Detail';
                if(stristr($url, 'outletapp/order/accept')) $subject = 'Outlet Order Accept';
                if(stristr($url, 'outletapp/order/ready')) $subject = 'Outlet Order Ready';
                if(stristr($url, 'outletapp/order/taken')) $subject = 'Outlet Order Taken';
                if(stristr($url, 'outletapp/order/reject')) $subject = 'Outlet Order Reject';
                if(stristr($url, 'outletapp/profile')) $subject = 'Outlet Profile';
                if(stristr($url, 'outletapp/product')) $subject = 'Outlet Product';
                if(stristr($url, 'outletapp/product/sold-out')) $subject = 'Outlet Product Sold-out';
                if(stristr($url, 'outletapp/delete-token')) $subject = 'Delete Device Token';
                if(stristr($url, 'outletapp/delete-token')) $subject = 'Delete Device Token';
                if(stristr($url, 'outletapp/order/detail/view')) $subject = 'Outlet Product Detail View';
                
                if(!empty($request->header('ip-address-view'))){
                    $ip = $request->header('ip-address-view');
                }else{
                    $ip = $request->ip();
                }

                $userAgent = $request->header('user-agent');
                
                $dtUser = null;
                
                if(!empty($user) && $user != ""){
                    $dtUser = json_encode($request->user());
                }
                
                $data = [
                    'url' 		=> $url,
                    'subject' 		=> $subject,
                    'outlet_code' 	=> $outletCode,
                    'user' 		=> $dtUser,
                    'request' 		=> $requestnya,
                    'response_status'   => $status,
                    'response' 		=> json_encode($response),
                    'ip' 		=> $ip,
                    'useragent' 	=> $userAgent
                ];

                $log = LogActivitiesOutletApps::create($data);
            }

        }
        return $response;
    }
}
