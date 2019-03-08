<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Models\LogRequest;

class LogRequestMiddleware
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

		$phone = $user['phone'];
		$id_store = null;
		if(isset($requeste['phone']))
			$phone = $requeste['phone'];
		
		if($requestnya == '[]') $requestnya = null;
		$urlexp = explode('/',$url);
		
		$module = $urlexp[6];
		if(stristr($url, 'v1/pos')) $module = 'POS';
		
		$subject = "Unknown";
		if(stristr($url, 'users/pin/create')) $subject = 'User Register';
		if(stristr($url, 'users/pin/check')) $subject = 'User Login Attempt';
		if(stristr($url, 'users/phone/check')) $subject = 'Phone Check';
		if(stristr($url, 'users/pin/resend')) $subject = 'Resend PIN';
		if(stristr($url, 'users/pin/forgot')) $subject = 'Forgot PIN';
		if(stristr($url, 'pos/check/member')) { $subject = 'POS Check Member'; }
		if(stristr($url, 'pos/check/voucher')) { $subject = 'POS Check Voucher'; }
		if(stristr($url, 'pos/menu')) $subject = 'POS Menu Sync';
		if(stristr($url, 'pos/outlet/menu')) $subject = 'POS Menu Sync';
		if(stristr($url, 'pos/transaction')) $subject = 'POS Transaction Sync';
		if(stristr($url, 'pos/transaction/detail')) $subject = 'Fetch Pre Order';
		if(stristr($url, 'pos/transaction/refund')) $subject = 'POS Transaction Refund';
		if(stristr($url, 'product/delete')) { $subject = 'Delete Product'; $module = $urlexp[4]; }
		
		if(!empty($request->header('ip-address-view'))){
			$ip = $request->header('ip-address-view');
		}else{
			$ip = $request->ip();
		}

		if(!empty($request->header('user-agent-view'))){
			$userAgent = $request->header('user-agent-view');
		}else{
			$userAgent = $request->header('user-agent');
		}

		if(!empty($user) && $user != ""){
			$data = [
				'module' 			=> $module,
				'url' 				=> $url,
				'subject' 			=> $subject,
				'phone' 			=> $phone,
				'user' 				=> json_encode($request->user()),
				'request' 			=> $requestnya,
				'response_status' 	=> $status,
				'response' 			=> json_encode($response),
				'ip' 				=> $ip,
				'useragent' 		=> $userAgent
			  ];
		} else{
			$data = [
				'module' 			=> $module,
				'url' 				=> $url,
				'subject' 			=> $subject,
				'phone' 			=> $phone,
				'user' 				=> null,
				'request' 			=> $requestnya,
				'response_status' 	=> $status,
				'response' 			=> json_encode($response),
				'ip' 				=> $ip,
				'useragent' 		=> $userAgent
			  ];
		}
		
		$log = LogRequest::create($data);
		return $response;
    }
}
