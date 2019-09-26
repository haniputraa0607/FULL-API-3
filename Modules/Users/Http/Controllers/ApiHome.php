<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserFeature;
use App\Http\Models\UserDevice;
use App\Http\Models\Level;
use App\Http\Models\LogRequest;
use App\Http\Models\UserInbox;
use App\Http\Models\Setting;
use App\Http\Models\Greeting;
use App\Http\Models\HomeBackground;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\Banner;
use App\Http\Models\FraudSetting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\FeaturedDeal;

use DB;
use App\Lib\MyHelper;

use Modules\Users\Http\Requests\Home;
use Modules\Queue\Http\Controllers\ApiQueue;

class ApiHome extends Controller
{
    public $getMyVoucher;
    public $endPoint;

    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
		$this->point  = "Modules\Deals\Http\Controllers\ApiDealsClaim";
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiSettingFraud";
		$this->endPoint  = env('AWS_URL');
    }

	public function homeNotLoggedIn(Request $request) {

		if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')) {
           $this->updateDeviceUserGuest($request->json('device_id'), $request->json('device_token'), $request->json('device_type'));
		}
		$key = array_pluck(Setting::where('key', 'LIKE', '%default_home%')->get()->toArray(), 'key');
		$value = array_pluck(Setting::where('key', 'LIKE', '%default_home%')->get()->toArray(), 'value');
		$defaultHome = array_combine($key, $value);

		if(isset($defaultHome['default_home_image'])){
			$defaultHome['default_home_image_url'] = $this->endPoint.$defaultHome['default_home_image'];
		}

		if(isset($defaultHome['default_home_splash_screen'])){
			$defaultHome['splash_screen_url'] = $this->endPoint.$defaultHome['default_home_splash_screen']."?";
		}

        // banner
        $banners = $this->getBanner();
        $defaultHome['banners'] = $banners;

       return response()->json(MyHelper::checkGet($defaultHome));
	}

    public function getBanner()
    {
        // banner
        $banners = Banner::orderBy('position')->get();
        $gofood = 0;
        $setting = Setting::where('key', 'banner-gofood')->first();
        if (!empty($setting)) {
            $gofood = $setting->value;
        }

        if (empty($banners)) {
            return $banners;
        }
        $array = [];

        foreach ($banners as $key => $value) {

            $item['image_url']  = env('AWS_URL').$value->image;
            $item['id_news']    = $value->id_news;
            $item['news_title'] = "";
            $item['url']        = $value->url;

            if ($value->id_news != "") {
                $item['news_title'] = $value->news->news_title;
                // if news, generate webview news detail url
                $item['url']        = env('APP_URL') .'news/webview/'. $value->id_news;
            }

            if ($value->type == 'gofood') {
                $item['id_news'] = 99999999;
                $item['news_title'] = "GO-FOOD";
                $item['url']     = env('APP_URL').'outlet/webview/gofood/list';
            }

            array_push($array, $item);
        }

        return $array;
    }

    public function refreshPointBalance(Request $request) {
		$user = $request->user();
		if($user){
			// $point      = app($this->point)->getPoint($user->id);
			// $balance      = app($this->balance)->balanceNow($user->id);
			$balance      = $user->balance;

			 /* QR CODE */
            $expired = Setting::where('key', 'qrcode_expired')->first();
            if(!$expired || ($expired && $expired->value == null)){
                $expired = '10';
            }else{
                $expired = $expired->value;
            }

            $timestamp = strtotime('+'.$expired.' minutes');

            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($useragent,'iOS')) $useragent = 'iOS';
            if(stristr($useragent,'okhttp')) $useragent = 'Android';
            else $useragent = null;

            $qr = MyHelper::createQR($timestamp, $user->phone, $useragent);

            // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
            $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

			$result = [
					'status' => 'success',
					'result' => [
						// 'total_point'   => (int) $point,
						// 'total_kopi_point' => (int) $balance,
						'total_point' => (int) $balance,
						'qr_code'        => $qrCode,
                        'expired_qr'    => $expired
					]
				];
		}else {
			$result = [
                'status' => 'fail'
            ];
		}
		return response()->json($result);
	}

    public function home(Home $request) {
        try {
            $user = $request->user();

            /**
             * update device token
             */

            if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')) {
                $this->updateDeviceUser($user, $request->json('device_id'), $request->json('device_token'), $request->json('device_type'));
            }

            if($request->user()->email == null || $request->user()->name == null){
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['User email or user name is empty.', 'Please complete name and email first']
                ]);
            }

            if($request->user()->is_suspended == '1'){
                //delete token
                $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                ->where('oauth_access_tokens.user_id', $request->user()->id)->where('oauth_access_token_providers.provider', 'users')->delete();

                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            if ($request->json('time')) {
                $time = $request->json('time');
            }
            else {
                $time = date('H:i:s');
            }

            $time = strtotime($time);

            // ambil dari DB
            $timeDB = Setting::select('key', 'value')->whereIn('key', ['greetings_morning', 'greetings_afternoon', 'greetings_evening', 'greetings_latenight'])->get()->toArray();

            if (empty($timeDB)) {
                $greetings = "Hello";
                $background = "";
            }
            else {
                $dbTime = [];

                /**
                 * replace key supaya gamapang dibaca
                 */
                foreach ($timeDB as $key => $value) {
                    $dbTime[str_replace("greetings_", "", $value['key'])] = $value['value'];
                }

                /**
                 * search greetings from DB
                 */
                if($time >= strtotime($dbTime['afternoon']) && $time < strtotime($dbTime['evening'])){
                    // salamnya dari DB
                    $greetings  = Greeting::where('when', '=', 'afternoon')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'afternoon')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['evening']) && $time <= strtotime($dbTime['latenight'])){
                    $greetings  = Greeting::where('when', '=', 'evening')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'evening')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['latenight'])){
                    $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'latenight')->get()->toArray();
                }
                elseif($time <= strtotime("04:00:00")){
                    $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'latenight')->get()->toArray();
                }
                else{
                    $greetings  = Greeting::where('when', '=', 'morning')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'morning')->get()->toArray();
                }

                /**
                 * kesimpulannya
                 */
                if (empty($greetings)) {
                    $greetingss = "Hello";
                    $greetingss2 = "Nice to meet You";
                    $background = "";
                }
                else {
                    $greetingKey   = array_rand($greetings, 1);
					// return $greetings[$greetingKey]['greeting2'];
                    $greetingss     = app($this->autocrm)->TextReplace($greetings[$greetingKey]['greeting'], $user['phone']);
                    $greetingss2     = app($this->autocrm)->TextReplace($greetings[$greetingKey]['greeting2'], $user['phone']);
                    if (!empty($background)) {
						$backgroundKey = array_rand($background, 1);
						$background    = env('AWS_URL').$background[$backgroundKey]['picture'];
					}
                }
            }

            $expired = Setting::where('key', 'qrcode_expired')->first();
            if(!$expired || ($expired && $expired->value == null)){
                $expired = '10';
            }else{
                $expired = $expired->value;
            }

            $timestamp = strtotime('+'.$expired.' minutes');

            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($useragent,'iOS')) $useragent = 'iOS';
            if(stristr($useragent,'okhttp')) $useragent = 'Android';
            else $useragent = null;

            $qr = MyHelper::createQR($timestamp, $user->phone, $useragent);

            // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
            $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

			// $point      = app($this->point)->getPoint($user->id);
			// $balance      = app($this->balance)->balanceNow($user->id);

			$membership = UsersMembership::select('memberships.*')
										->Join('memberships','memberships.id_membership','=','users_memberships.id_membership')
										->where('id_user','=',$user->id)
										->orderBy('id_log_membership','desc')
										->first();

			if(isset($membership) && $membership != ""){
                $dataEncode = [
                    'id_user' => $user->id,
                ];

                $encode = json_encode($dataEncode);
                $base = base64_encode($encode);

                $membership['webview_detail_membership'] = env('VIEW_URL').'/membership/web/view?data='.$base;
				if(isset($membership['membership_image']))
					$membership['membership_image'] = env('AWS_URL').$membership['membership_image'];
			} else {
				$membership = null;
			}

			$splash = Setting::where('key', '=', 'default_home_splash_screen')->first();

			if(!empty($splash)){
				$splash = $this->endPoint.$splash['value'];
			} else {
				$splash = null;
            }

            $countUnread = UserInbox::where('id_user','=',$user['id'])->where('read', '0')->count();
            $transactionPending = Transaction::where('id_user','=',$user['id'])->where('transaction_payment_status', 'Pending')->count();

            // banner
            $banners = $this->getBanner();

            // webview: user profile form
            $webview_url = "";
            $popup_text = "";
            $webview_link = env('APP_URL') . 'webview/complete-profile';

            // check user profile completeness (if there is null data)
            if ($user->id_city=="" || $user->gender=="" || $user->birthday=="") {
                // get setting user profile value
                $complete_profile_interval = 0;
                $complete_profile_count = 0;
                $setting_profile_point = Setting::where('key', 'complete_profile_interval')->first();
                $setting_profile_cashback = Setting::where('key', 'complete_profile_count')->first();
                if (isset($setting_profile_point->value)) {
                    $complete_profile_interval = $setting_profile_point->value;
                }
                if (isset($setting_profile_cashback->value)) {
                    $complete_profile_count = $setting_profile_cashback->value;
                }

                // check interval and counter
                // if $webview_url == "", app won't pop up the form
                if ($user->last_complete_profile != null) {
                    $now = date('Y-m-d H:i:s');
                    // count date difference (in minutes)
                    $date_start = strtotime($user->last_complete_profile);
                    $date_end   = strtotime($now);
                    $date_diff  = $date_end - $date_start;
                    $minutes_diff = $date_diff / 60;

                    if ($user->count_complete_profile < $complete_profile_count && $complete_profile_interval < $minutes_diff ) {
                        $webview_url = $webview_link;

                        $setting_profile_popup = Setting::where('key', 'complete_profile_popup')->first();
                        if (isset($setting_profile_popup->value)) {
                            $popup_text = $setting_profile_popup->value;
                        }else{
                            $popup_text = "Lengkapi data dan dapatkan Kopi Points";
                        }
                    }
                }
                else {  // never pop up before
                    $webview_url = $webview_link;

                    $setting_profile_popup = Setting::where('key', 'complete_profile_popup')->first();
                    if (isset($setting_profile_popup->value)) {
                        $popup_text = $setting_profile_popup->value;
                    }else{
                        $popup_text = "Lengkapi data dan dapatkan Kopi Points";
                    }
                }
            }

            $updateUserLogin = User::where('phone', $user->phone)->update(['new_login' => '0']);

            $birthday = "";
            if ($user->birthday != "") {
                $birthday = date("d F Y", strtotime($user->birthday));
            }

            $result = [
                'status' => 'success',
                'result' => [
                    // 'greetings'     => $greetingss,
                    // 'greetings2'    => $greetingss2,
                    // 'background'    => $background,
                    'banners'       => $banners,
                    'splash_screen_url' => $splash."?update=".time(),
                    // 'total_point'   => (int) $point,
                    // 'total_kopi_point' => (int) $user->balance,
                    'total_point' => (int) $user->balance,
                    // 'notification'  =>[
                    //     'total' => $countUnread + $transactionPending,
                    //     'count_unread_inbox' => $countUnread,
                    //     'count_transaction_pending' => $transactionPending,
                    // ],
                    'user_info'     => [
                        'name'  => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'birthday' => $birthday,
                        'gender' => $user->gender,
                        'relationship'  => $user->relationship,
                        'city'  => $user->city,
                        'membership'  => $membership,
                    ],
                    'qr_code'       => $qrCode,
                    'uid'           => $qr,
                    'webview_complete_profile_url'   => $webview_url,
                    'popup_complete_profile'   => $popup_text,
                ]
            ];

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(MyHelper::throwError($e));
        }
    }

    public function background(Request $request) {
        try {

            if ($request->json('time')) {
                $time = $request->json('time');
            }
            else {
                $time = date('H:i:s');
            }

            $time = strtotime($time);

            // ambil dari DB
            $timeDB = Setting::select('key', 'value')->whereIn('key', ['greetings_morning', 'greetings_afternoon', 'greetings_evening', 'greetings_late_night'])->get()->toArray();

            // print_r($timeDB); exit();

            if (empty($timeDB)) {
                $background = "";
            }
            else {
                $dbTime = [];

                /**
                 * replace key supaya gamapang dibaca
                 */
                foreach ($timeDB as $key => $value) {
                    $dbTime[str_replace("greetings_", "", $value['key'])] = $value['value'];
                }

                /**
                 * search greetings from DB
                 */
                if($time >= strtotime($dbTime['afternoon']) && $time < strtotime($dbTime['evening'])){
                    // salamnya dari DB
                    $background  = HomeBackground::where('when', '=', 'afternoon')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['evening']) && $time <= strtotime($dbTime['late_night'])){
                    $background  = HomeBackground::where('when', '=', 'evening')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['late_night'])){
                    $background  = HomeBackground::where('when', '=', 'late_night')->get()->toArray();
                }
                elseif($time <= strtotime("04:00:00")){
                    $background  = HomeBackground::where('when', '=', 'late_night')->get()->toArray();
                }
                else{
                    $background  = HomeBackground::where('when', '=', 'morning')->get()->toArray();
                }

                /**
                 * kesimpulannya
                 */
                if (empty($background)) {
                    $background = "";
                }
                else {
                    $backgroundKey = array_rand($background, 1);
                    $background    = env('AWS_URL').$background[$backgroundKey]['picture'];
                }
            }

            $result = [
                'status' => 'success',
                'result' => [
                    'background'     => $background,
                ]
            ];

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(MyHelper::throwError($e));
        }
    }

	public function updateDeviceUserGuest($device_id, $device_token, $device_type) {
        $dataUpdate = [
            'device_id'    => $device_id,
            'device_token' => $device_token,
            'device_type' => $device_type
        ];

        $checkDevice = UserDevice::where('device_id', $device_id)
								->where('device_token', $device_token)
								->where('device_type', $device_type)
								->count();
        if ($checkDevice == 0) {
            $update                = UserDevice::create($dataUpdate);
            $result = [
                'status' => 'updated'
            ];
        }
        else {
            $result = [
                'status' => 'success'
            ];
        }

        return $result;
    }

    public function updateDeviceUser($user, $device_id, $device_token, $device_type) {
        $dataUpdate = [
            'device_id'    => $device_id,
            'device_token' => $device_token,
            'device_type' => $device_type
        ];

        $checkDevice = UserDevice::where('id_user', $user->id)
								->where('device_id', $device_id)
								->where('device_type', $device_type)
								->count();

        if ($checkDevice == 0) {
            $dataUpdate['id_user'] = $user->id;
            $update                = UserDevice::create($dataUpdate);
        }
        else {
            $update = UserDevice::where('id_user','=',$user->id)->update($dataUpdate);
        }

        if ($update) {
			if($device_type == 'Android')
            $query                 = User::where('id','=',$user->id)->update(['android_device' => $device_id]);

			if($device_type == 'IOS')
            $query                 = User::where('id','=',$user->id)->update(['ios_device' => $device_id]);

            $result = [
                'status' => 'updated'
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        //check fraud
        if($user->new_login == '1'){
            $deviceCus = UserDevice::where('device_type','=',$device_type)
            ->where('device_id','=',$device_id)
            // ->where('device_token','=',$device_token)
            ->orderBy('id_device_user', 'ASC')
            ->first();

            $lastDevice = UserDevice::where('id_user','=',$user->id)->orderBy('id_device_user', 'desc')->first();
            if($deviceCus && $deviceCus['id_user'] != $user->id){
                // send notif fraud detection
                $fraud = FraudSetting::where('parameter', 'LIKE', '%device ID%')->first();
                if($fraud){
                    $sendFraud = app($this->setting_fraud)->SendFraudDetection($fraud['id_fraud_setting'], $user, null, $lastDevice);
                }
            }
        }

        return $result;
    }

    public function membership(Request $request){   
        $user = $request->user();
        $user->load(['city','city.province']);
        $birthday = "";
        if ($user->birthday != "") {
            $birthday = date("d F Y", strtotime($user->birthday));
        }

        if ($request->json('time')) {
            $time = $request->json('time');
        }
        else {
            $time = date('H:i:s');
        }

        $time = strtotime($time);

        // ambil dari DB
        $timeDB = Setting::select('key', 'value')->whereIn('key', ['greetings_morning', 'greetings_afternoon', 'greetings_evening', 'greetings_latenight'])->get()->toArray();

        if (empty($timeDB)) {
            $greetings = "Hello";
        }
        else {
            $dbTime = [];

            /**
             * replace key supaya gamapang dibaca
             */
            foreach ($timeDB as $key => $value) {
                $dbTime[str_replace("greetings_", "", $value['key'])] = $value['value'];
            }

            /**
             * search greetings from DB
             */
            if($time >= strtotime($dbTime['afternoon']) && $time < strtotime($dbTime['evening'])){
                // salamnya dari DB
                $greetings  = Greeting::where('when', '=', 'afternoon')->get()->toArray();
            }
            elseif($time >= strtotime($dbTime['evening']) && $time <= strtotime($dbTime['latenight'])){
                $greetings  = Greeting::where('when', '=', 'evening')->get()->toArray();
            }
            elseif($time >= strtotime($dbTime['latenight'])){
                $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
            }
            elseif($time <= strtotime("04:00:00")){
                $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
            }
            else{
                $greetings  = Greeting::where('when', '=', 'morning')->get()->toArray();
            }

            /**
             * kesimpulannya
             */
            if (empty($greetings)) {
                $greetingss = "Hello";
            }
            else {
                $greetingKey   = array_rand($greetings, 1);
                // return $greetings[$greetingKey]['greeting2'];
                $greetingss     = app($this->autocrm)->TextReplace($greetings[$greetingKey]['greeting'], $user['phone']);
            }
        }

        $expired = Setting::where('key', 'qrcode_expired')->first();
        if(!$expired || ($expired && $expired->value == null)){
            $expired = '10';
        }else{
            $expired = $expired->value;
        }

        $timestamp = strtotime('+'.$expired.' minutes');

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($useragent,'iOS')) $useragent = 'iOS';
        if(stristr($useragent,'okhttp')) $useragent = 'Android';
        else $useragent = null;

        $qr = MyHelper::createQR($timestamp, $user->phone, $useragent);

        $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
        $qrCode = html_entity_decode($qrCode);

        $membership = UsersMembership::select('memberships.membership_name')
                                    ->Join('memberships','memberships.id_membership','=','users_memberships.id_membership')
                                    ->where('id_user','=',$user->id)
                                    ->orderBy('id_log_membership','desc')
                                    ->first();

        if(isset($membership) && $membership != ""){
            $dataEncode = [
                'id_user' => $user->id,
            ];

            $encode = json_encode($dataEncode);
            $base = base64_encode($encode);

            $membership['webview_detail_membership'] = env('VIEW_URL').'/membership/web/view?data='.$base;
        } else {
            $membership = null;
        }

        $retUser=$user->toArray();

        if($retUser['birthday']??false){
            $retUser['birthday']=date("d F Y", strtotime($retUser['birthday']));
        }
        array_walk_recursive($retUser, function(&$it){
            if($it==null){
                $it="";
            }
        });
        $hidden=['password_k','created_at','updated_at','provider','phone_verified','email_verified','email_unsubscribed','level','points','rank','android_device','ios_device','is_suspended','balance','complete_profile','subtotal_transaction','count_transaction','id_membership','relationship'];
        foreach ($hidden as $hide) {
            unset($retUser[$hide]);
        }

        $retUser['membership']=$membership;
        $result = [
            'status' => 'success',
            'result' => [
                'total_point' => (int) $user->balance??0,
                'user_info'     => $retUser,
                'qr_code'       => $qrCode??'',
                'greeting'      => $greetingss??'',
                'expired_qr'    => $expired??''
            ]
        ];

        return response()->json($result);
    }

    public function splash(Request $request){
        $splash = Setting::where('key', '=', 'default_home_splash_screen')->first();

        if(!empty($splash)){
            $splash = $this->endPoint.$splash['value'];
        } else {
            $splash = null;
        }

        $result = [
            'status' => 'success',
            'result' => [
                'splash_screen_url' => $splash."?update=".time(),
            ]
        ];
        return $result;
    }

    public function banner(Request $request){
        $banners = $this->getBanner();
        $result = [
            'status' => 'success',
            'result' => $banners,
        ];
        return $result;
    }

    public function featuredDeals(Request $request){
        $now=date('Y-m-d H-i-s');
        $deals=FeaturedDeal::select('id_featured_deals','id_deals')->with(['deals'=>function($query){
            $query->select('deals_title','deals_image','deals_total_voucher','deals_total_claimed','deals_publish_end','deals_start','deals_end','id_deals','deals_voucher_price_point','deals_voucher_price_cash');
        }])
            ->whereHas('deals',function($query){
                $query->where('deals_publish_end','>=',DB::raw('CURRENT_TIMESTAMP()'));
                $query->where('deals_publish_start','<=',DB::raw('CURRENT_TIMESTAMP()'));
            })
            ->orderBy('order')
            ->where('start_date','<=',$now)
            ->where('end_date','>=',$now)
            ->get();
        if($deals){
            $deals=array_map(function($value){
                $calc = $value['deals']['deals_total_voucher'] - $value['deals']['deals_total_claimed'];
                $value['deals']['available_voucher'] = $calc;
                if($calc&&is_numeric($calc)){
                    $value['deals']['percent_voucher'] = $calc*100/$value['deals']['deals_total_voucher'];
                }else{
                    $value['deals']['percent_voucher'] = 100;
                }
                $value['deals']['time_to_end']=strtotime($value['deals']['deals_end'])-time();
                return $value;
            },$deals->toArray());
            return [
                'status'=>'success',
                'result'=>$deals
            ];
        }else{
            return [
                'status' => 'fail',
                'messages' => ['Something went wrong']
            ];
        }
    }
}
