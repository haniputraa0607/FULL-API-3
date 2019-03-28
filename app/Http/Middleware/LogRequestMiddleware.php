<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Models\LogRequest;
use App\Lib\MyHelper;

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

		$phone = null;
		if(isset($requeste['phone']))
			$phone = $requeste['phone'];
			
        if(isset($user['phone']))
		    $phone = $user['phone'];
		
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
		if(stristr($url, 'pos/outlet')) $subject = 'POS Outlet Sync';
		if(stristr($url, 'pos/outlet/menu')) $subject = 'POS Menu Sync';
		if(stristr($url, 'pos/transaction')) $subject = 'POS Transaction Sync';
		if(stristr($url, 'pos/transaction/detail')) $subject = 'Fetch Pre Order';
		if(stristr($url, 'pos/transaction/refund')) $subject = 'POS Transaction Refund';
		if(stristr($url, 'product/delete')) { $subject = 'Delete Product'; $module = $urlexp[4]; }
		

		//autocrm 
		if(stristr($url, 'autocrm')) $subject = 'Autocrm';

		//balance
		if(stristr($url, 'balance')) $subject = 'Balance';

		//campaign
		if(stristr($url, 'campaign')) $subject = 'Campaign';

		//deals
		if(stristr($url, 'deals')) $subject = 'Deals';

		//enquiriy
		if(stristr($url, 'enquiry')) $subject = 'Contact Us';

		//inbox
		if(stristr($url, 'inbox')) $subject = 'Inbox';

		//membership
		if(stristr($url, 'membership')) $subject = 'Membership';

		//news
		if(stristr($url, 'news')) $subject = 'News';

		//outlet
		if(stristr($url, 'outlet')) $subject = 'Outlet';
		if(stristr($url, 'outlet/nearme')) $subject = 'Outlet Near Me';

		//outletApp
		if(stristr($url, 'outletapp')) $subject = 'Outlet App';

		//product
		if(stristr($url, 'product')) $subject = 'Product';

		//promotion
		if(stristr($url, 'promotion')) $subject = 'Promotion';

		//report
		if(stristr($url, 'report')) $subject = 'Report';

		//reward
		if(stristr($url, 'reward')) $subject = 'Reward';

		//setting
		if(stristr($url, 'setting')) $subject = 'Setting';
		if(stristr($url, 'faq')) $subject = 'FAQ';

		//fraud
		if(stristr($url, 'fraud')) $subject = 'Fraud';

		//spin the wheel
		if(stristr($url, 'spinthewheel')) $subject = 'Spin The Wheel';

		//transaction
		if(stristr($url, 'transaction')) $subject = 'Transaction';
		
		//user
		if(stristr($url, 'user')) $subject = 'User';

		//home
		if(stristr($url, 'home')) $subject = 'Home';
		if(stristr($url, 'home/refresh-point-balance')) $subject = 'Refresh Home';

		//profile
		if(stristr($url, 'profile')) $subject = 'Profile';
		
		//voucher
		if(stristr($url, 'voucher')) $subject = 'Voucher';
		
		//CRUD
		if(stristr($url, 'create')) {
			if($subject){
				$subject = 'New '.$subject;
			}
		}
		if(stristr($url, 'update')) {
			if($subject){
				$subject = 'Update '.$subject;
			}
		}
		if(stristr($url, 'delete')) {
			if($subject){
				$subject = 'Delete '.$subject;
			}
		}
		
		if(stristr($url, 'list')) {
			if(stristr($url, 'webview')) {
				if($subject){
					$subject = 'Webview '.$subject;
				}
			}else{
				if($subject){
					$subject = $subject.' List';
				}
			}

		}
		if(stristr($url, 'filter')) {
			if($subject){
				$subject = $subject.' Filter';
			}
		}

		if(stristr($url, 'history-balance')) $subject = 'History Point';
		if(stristr($url, 'history-trx')) $subject = 'History Transaction';

		if(stristr($url, 'detail')) {
			if($subject){
				$subject = $subject.' Detail';
			}
		}

		if(!empty($request->header('ip-address-view'))){
			$ip = $request->header('ip-address-view');
		}else{
			$ip = $request->ip();
		}

		if(!empty($request->header('user-agent-view'))){
			$userAgent = $request->header('user-agent-view');
			$subject = $subject.' BE';
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
				'request' 			=> MyHelper::encrypt2019($requestnya),
				'response_status' 	=> $status,
				'response' 			=> MyHelper::encrypt2019($response),
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
				'request' 			=> MyHelper::encrypt2019($requestnya),
				'response_status' 	=> $status,
				'response' 			=> MyHelper::encrypt2019($response),
				'ip' 				=> $ip,
				'useragent' 		=> $userAgent
			  ];
		}
		
		$log = LogRequest::create($data);
		return $response;
    }
}
