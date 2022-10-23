<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Models\UserDevice;
use App\Http\Models\UsersDeviceLogin;
use App\Lib\ValueFirst;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserLocation;
use App\Http\Models\Level;
use App\Http\Models\Doctor;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use Modules\Balance\Http\Controllers\BalanceController;
use Modules\SettingFraud\Entities\FraudSetting;
use Modules\Users\Entities\OldMember;
use Modules\Users\Entities\UserSocialLogin;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone;
use Modules\Users\Http\Requests\users_phone_pin;
use Modules\Users\Http\Requests\users_phone_pin_admin;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;

use App\Lib\MyHelper;
use Modules\Users\Http\Requests\users_profile;
use Validator;
use Hash;
use DB;
use Mail;
use Auth;

use Socialite;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Crypt;
use GuzzleHttp\Client as GuzzleHttpClient;
use Laravel\Passport\Client;

class ApiLoginRegisterV2 extends Controller
{
    function __construct()
    {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');
        $this->home     = "Modules\Users\Http\Controllers\ApiHome";
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->membership  = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->inbox  = "Modules\InboxGlobal\Http\Controllers\ApiInbox";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->deals = "Modules\Deals\Http\Controllers\ApiDeals";
        $this->welcome_subscription = "Modules\Subscription\Http\Controllers\ApiWelcomeSubscription";
    }

    function phoneCheck(users_phone $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::select('*',\DB::raw('0 as challenge_key'))->with('city')->where('phone', '=', $phone)->get()->toArray();

        if (isset($data[0]['is_suspended']) && $data[0]['is_suspended'] == '1') {
            $emailSender = Setting::where('key', 'email_sender')->first();
            return response()->json([
                'status' => 'fail',
                'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
            ]);
        }

        switch (env('OTP_TYPE', 'PHONE')) {
            case 'MISSCALL':
                $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_miscall', 'value_text', 'Kami akan mengirimkan kode OTP melalui Missed Call ke %phone%.<br/>Anda akan mendapatkan panggilan dari nomor 6 digit.<br/>Nomor panggilan tsb adalah Kode OTP Anda.'));
                break;

            case 'WA':
                $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_wa', 'value_text', 'Kami akan mengirimkan kode OTP melalui Whatsapp.<br/>Pastikan nomor %phone% terdaftar di Whatsapp.'));
                break;

            default:
                $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_sms', 'value_text', 'Kami akan mengirimkan kode OTP melalui SMS.<br/>Pastikan nomor %phone% aktif.'));
                break;
        }

        if($data){
            if ($data[0]['phone_verified'] == 0 && empty($data[0]['pin_changed'])) {
                $result['register'] = true;
                $result['forgot'] = false;
                $result['confirmation_message'] = $msg_check;
                return response()->json([
                    'status' => 'success',
                    'result' => $result
                ]);
            }else{
                $result['register'] = false;
                $result['forgot'] = false;
                $result['challenge_key'] = $data[0]['challenge_key'];
                $result['confirmation_message'] = $msg_check;
                return response()->json([
                    'status' => 'success',
                    'result' => $result
                ]);
            }

        }else{
            return response()->json([
                'status' => 'success',
                'result' => [
                    'register' => true,
                    'forgot' => false,
                    'confirmation_message' => $msg_check
                ]
            ]);
        }
    }

    function pinRequest(users_phone $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        //get setting rule otp
        $setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if($setting && isset($setting['value_text'])){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        if (!$data) {
            $pin = MyHelper::createRandomPIN(6, 'angka');
            // $pin = '777777';

            $provider = MyHelper::cariOperator($phone);
            $is_android     = null;
            $is_ios         = null;
            $device_id = $request->json('device_id');
            $device_token = $request->json('device_token');
            $device_type = $request->json('device_type');

            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) $useragent = 'IOS';
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) $useragent = 'Android';
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) $useragent = 'Browser';

            if(empty($device_type)){
                $device_type = $useragent;
            }

            if($device_type == "Android") {
                $is_android = 1;
            } elseif($device_type == "IOS"){
                $is_ios = 1;
            }

            if ($request->json('device_token') != "") {
                $device_token = $request->json('device_token');
            }

            //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
            $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
            if($getSettingTimeExpired){
                $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
            }else{
                $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
            }

            $create = User::create([
                'phone' => $phone,
                'provider'         => $provider,
                'password'        => bcrypt($pin),
                'android_device' => $is_android,
                'ios_device'     => $is_ios,
                'otp_valid_time' => $dateOtpTimeExpired
            ]);

            if ($create) {
                $checkRuleRequest = MyHelper::checkRuleForRequestOTP([$create]);
                if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                    return response()->json($checkRuleRequest);
                }

                if ($request->json('device_id') && $request->json('device_token') && $device_type) {
                    app($this->home)->updateDeviceUser($create, $request->json('device_id'), $request->json('device_token'), $device_type);
                }
            }


            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Create',
                    $phone,
                    []
                );
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Sent',
                    $phone,
                    [
                        'pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s'),
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                    ],
                    $useragent
                );
            }

            app($this->membership)->calculateMembership($phone);

            //create user location when register
            if ($request->json('latitude') && $request->json('longitude')) {
                $userLocation = UserLocation::create([
                    'id_user' => $create['id'],
                    'lat' => $request->json('latitude'),
                    'lng' => $request->json('longitude'),
                    'action' => 'Register'
                ]);
            }

            switch (env('OTP_TYPE', 'PHONE')) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WA':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }


            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $create->phone,
                        'autocrm'  =>    $autocrm,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $create->getChallengeKeyAttribute(),
                        'forget' => false
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $create->phone,
                        'autocrm'    =>    $autocrm,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $create->getChallengeKeyAttribute(),
                        'forget' => false
                    ]
                ];
            }
            return response()->json($result);
        } else {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                return response()->json($checkRuleRequest);
            }

            if($checkRuleRequest == true && !isset($checkRuleRequest['otp_timer'])){
                $pinnya = MyHelper::createRandomPIN(6, 'angka');
                $pin = bcrypt($pinnya);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if($getSettingTimeExpired){
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
                }else{
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                if(!empty($data[0]['pin_changed'])){
                    $update = User::where('phone', '=', $phone)->update(['otp_forgot' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);
                }else{
                    $update = User::where('phone', '=', $phone)->update(['password' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);
                }

                $useragent = $_SERVER['HTTP_USER_AGENT'];
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) $useragent = 'iOS';
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) $useragent = 'Android';
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) $useragent = 'Browser';

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Pin Create',
                        $phone,
                        []
                    );
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Pin Sent',
                        $phone,
                        [
                            'pin' => $pinnya,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s'),
                            'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                        ],
                        $useragent
                    );
                }
            }elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (env('OTP_TYPE', 'PHONE')) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WA':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $data[0]['phone'],
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => (empty($data[0]['email']) ? false : true)
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $data[0]['phone'],
                        'message' => $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => (empty($data[0]['email']) ? false : true)
                    ]
                ];
            }

            return response()->json($result);
        }
    }

    function changePin(users_phone_pin_new_v2 $request)
    {

        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if ($data) {
            if(!empty($data[0]['otp_forgot']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('pin_old'), $data[0]['otp_forgot'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Current PIN doesn\'t match']
                ]);
            }elseif(empty($data[0]['otp_forgot']) && !empty($data[0]['pin_changed']) && !empty($data[0]['phone_verified']) && !Auth::attempt(['phone' => $phone, 'password' => $request->json('pin_old')])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Current PIN doesn\'t match']
                ]);
            }

            $pin     = bcrypt($request->json('pin_new'));
            $update = User::where('id', '=', $data[0]['id'])->update(['password' => $pin, 'otp_forgot' => null, 'phone_verified' => '1', 'pin_changed' => '1']);
            if (\Module::collections()->has('Autocrm')) {
                if ($data[0]['first_pin_change'] < 1) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone);
                    $changepincount = $data[0]['first_pin_change'] + 1;
                    $update = User::where('id', '=', $data[0]['id'])->update(['first_pin_change' => $changepincount]);
                } else {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone);

                    $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $data[0]['id'])->where('oauth_access_token_providers.provider', 'users')->delete();
                }
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            $result = [
                'status'    => 'success',
                'result'    => [
                    'phone'    =>    $data[0]['phone'],
                    'challenge_key' => $user->challenge_key
                ]
            ];
        } else {
            $result = [
                'status'    => 'fail',
                'messages'    => ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function forgotPin(users_forgot $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        //get setting rule otp
        $setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if($setting && isset($setting['value_text'])){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
        }

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'otp_timer' => $holdTime,
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $user = User::where('phone', '=', $phone)->first();

        if (!$user) {
            $result = [
                'status'    => 'fail',
                'otp_timer' => $holdTime,
                'messages'    => ['User not found.']
            ];
            return response()->json($result);
        }

        $user->sms_increment = 0;
        $user->save();

        $data = User::select('*',\DB::raw('0 as challenge_key'))->where('phone', '=', $phone)
            ->get()
            ->toArray();

        if ($data) {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                return response()->json($checkRuleRequest);
            }

            if(!isset($checkRuleRequest['otp_timer']) && $checkRuleRequest == true){
                $pin = MyHelper::createRandomPIN(6, 'angka');
                $password = bcrypt($pin);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if($getSettingTimeExpired){
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
                }else{
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                $update = User::where('id', '=', $data[0]['id'])->update(['otp_forgot' => $password, 'otp_valid_time' => $dateOtpTimeExpired]);

                if (!empty($request->header('user-agent-view'))) {
                    $useragent = $request->header('user-agent-view');
                } else {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if (stristr($useragent, 'iOS')) $useragent = 'iOS';
                if (stristr($useragent, 'okhttp')) $useragent = 'Android';
                if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Forgot',
                    $phone,
                    [
                        'pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s'),
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                    ],
                    $useragent
                );
            }elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (env('OTP_TYPE', 'PHONE')) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WA':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $phone,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => true
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $phone,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => true
                    ]
                ];
            }
            return response()->json($result);
        } else {
            $result = [
                'status'    => 'fail',
                'messages'  => ['Email yang kamu masukkan kurang tepat']
            ];
            return response()->json($result);
        }
    }

    public function claimPoint(Request $request){
        $id = $request->user()->id;
        $user = User::where('id', $id)->first();

        if(empty($user)){
            return response()->json([[
                'status'    => 'fail',
                'messages'  => ['User tidak ditemukan']
            ]]);
        }

        $checkOldMember = OldMember::where('phone', $user['phone'])->where('claim_status', 0)->get()->toArray();
        $sumPoint = array_sum(array_column($checkOldMember, 'loyalty_point'));
        if(empty($sumPoint)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak berhasil klaim point']
            ]);
        }

        $balanceController = new BalanceController();
        $addLogBalance = $balanceController->addLogBalance($id, (int)$sumPoint, null, "Claim Point", 0);
        if (!$addLogBalance) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak berhasil klaim point']
            ]);
        }

        OldMember::where('phone', $user['phone'])->update(['claim_status' => 1]);
        User::where('id', $id)->update(['claim_point_status' => 1]);
        return response()->json([
            'status' => 'success',
            'result' => [
                'message' => 'Berhasil klaim point sebesar '. number_format((int)$sumPoint)
            ]
        ]);
    }

    function phoneCheckEmployee(Request $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::select('*',\DB::raw('0 as challenge_key'))->where('phone', '=', $phone)->get()->toArray();

        if($data){
            $result['challenge_key'] = $data[0]['challenge_key'];
            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);
        }else{
            return response()->json([
                'status' => 'fail',
                'messages' => ['Akun tidak ditemukan']]);
        }
    }

    function verifyPin(users_phone_pin $request)
    {

        $phone = $request->json('phone');
        $post = $request->json()->all();

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if ($data) {
            if(!empty($data[0]['pin_changed']) && !password_verify($request->json('pin'), $data[0]['otp_forgot'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['OTP yang kamu masukkan salah']
                ]);
            }elseif(empty($data[0]['pin_changed']) && !Auth::attempt(['phone' => $phone, 'password' => $request->json('pin')])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['OTP yang kamu masukkan salah']
                ]);
            }

            /*first if --> check if otp have expired and the current time exceeds the expiration time*/
            if(!is_null($data[0]['otp_valid_time']) && strtotime(date('Y-m-d H:i:s')) > strtotime($data[0]['otp_valid_time'])){
                return response()->json(['status' => 'fail', 'otp_check'=> 1, 'messages' => ['This OTP is expired, please re-request OTP from apps']]);
            }

            if (isset($post['device_id'])) {
                if (!isset($post['device_type'])) {
                    if (!empty($request->header('user-agent-view'))) {
                        $useragent = $request->header('user-agent-view');
                    } else {
                        $useragent = $_SERVER['HTTP_USER_AGENT'];
                    }

                    if (stristr($useragent, 'iOS')) $post['device_type'] = 'iOS';
                    if (stristr($useragent, 'okhttp')) $post['device_type'] = 'Android';
                    if (stristr($useragent, 'GuzzleHttp')) $post['device_type'] = 'Browser';
                }

                $device_id = $post['device_id'];
                $device_type = $post['device_type'];
                $fraud = FraudSetting::where('parameter', 'LIKE', '%device ID%')->where('fraud_settings_status', 'Active')->first();
                if ($fraud) {
                    app($this->setting_fraud)->createUpdateDeviceLogin($data[0], $device_id);

                    $deviceCus = UsersDeviceLogin::where('device_id', '=', $device_id)
                        ->where('status', 'Active')
                        ->select('id_user')
                        ->orderBy('created_at', 'asc')
                        ->groupBy('id_user')
                        ->get()->toArray('id_user');

                    $count = count($deviceCus);
                    $check = array_slice($deviceCus, (int) $fraud['parameter_detail']);
                    $check = array_column($check, 'id_user');

                    if ($deviceCus && count($deviceCus) > (int) $fraud['parameter_detail'] && array_search($data[0]['id'], $check) !== false) {
                        $emailSender = Setting::where('key', 'email_sender')->first();
                        $sendFraud = app($this->setting_fraud)->checkFraud($fraud, $data[0], ['device_id' => $device_id, 'device_type' => $useragent??null], 0, 0, null, 0);
                        $data = User::with('city')->where('phone', '=', $phone)->get()->toArray();

                        if ($data[0]['is_suspended'] == 1) {
                            return response()->json([
                                'status' => 'fail',
                                'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
                            ]);
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'messages' => ['Akun Anda tidak dapat di daftarkan karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
                            ]);
                        }
                    }
                }
            }

            $dtUpdate['otp_valid_time'] = NULL;
            if(!empty($data[0]['temporary_password'])){
                $dtUpdate['password'] = $data[0]['temporary_password'];
                $dtUpdate['temporary_password'] = NULL;
            }

            $update = User::where('id', '=', $data[0]['id'])->update($dtUpdate);
            if ($update) {
                $profile = User::select('phone', 'email', 'name', 'id_city', 'gender', 'phone_verified', 'email_verified')
                    ->where('phone', '=', $phone)
                    ->get()
                    ->toArray();
                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Verify', $phone);
                }
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'phone'    => $data[0]['phone'],
                        'register' => ($data[0]['phone_verified'] == 0 && empty($data[0]['pin_changed']) ? true : false),
                        'goto' => ($data[0]['phone_verified'] == 0 && empty($data[0]['pin_changed']) ? ($profile['email'] ? 'home' : 'register') : 'password_reset'),
                    ]
                ];
            }else{
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['Failed to Update Data']
                ];
            }
        } else {
            $result = [
                'status'    => 'fail',
                'messages'    => ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result??['status' => 'fail','messages' => ['No Process']]);
    }

    function checkPin(users_phone_pin $request)
    {
        $is_android     = 0;
        $is_ios         = 0;
        $device_id         = null;
        $device_token     = null;

        $ip = null;
        if (!empty($request->json('ip'))) {
            $ip = $request->json('ip');
        } else {
            if (!empty($request->header('ip-address-view'))) {
                $ip = $request->header('ip-address-view');
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        }

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (!empty($request->json('useragent'))) {
            $useragent = $request->json('useragent');
        } else {
            if (!empty($request->header('user-agent-view'))) {
                $useragent = $request->header('user-agent-view');
            } else {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }
        }

        $device = null;

        if ($useragent == "Opera/9.80 (Windows NT 6.1) Presto/2.12.388 Version/12.16")
            $device = 'Web Browser';
        if (stristr($useragent, 'iOS'))
            $device = 'perangkat iOS';
        if (stristr($useragent, 'okhttp'))
            $device = 'perangkat Android';
        if (stristr($useragent, 'Linux; U;')) {
            $sementara = preg_match('/\(Linux\; U\; (.+?)\; (.+?)\//', $useragent, $matches);
            $device = $matches[2];
        }
        if (empty($device))
            $device = $useragent;


        if ($request->json('device_type') == "Android") {
            $is_android = 1;
            $device_type = "Android";
        } elseif ($request->json('device_type') == "IOS") {
            $is_ios = 1;
            $device_type = "IOS";
        }

        if ($request->json('device_id') != "") {
            $device_id = $request->json('device_id');
        }

        if ($request->json('device_token') != "") {
            $device_token = $request->json('device_token');
        }

        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $datauser = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        $cekFraud = 0;
        if ($datauser) {
            User::where('phone', $phone)->update(['otp_forgot' => null, 'otp_valid_time' => null]);
            if (Auth::attempt(['phone' => $phone, 'password' => $request->json('pin')])) {
                /*first if --> check if otp have expired and the current time exceeds the expiration time*/
                if(!is_null($datauser[0]['otp_valid_time']) && strtotime(date('Y-m-d H:i:s')) > strtotime($datauser[0]['otp_valid_time'])){
                    return response()->json(['status' => 'fail', 'otp_check'=> 1, 'messages' => ['This OTP is expired, please re-request OTP from apps']]);
                }

                //untuk verifikasi admin panel
                if ($request->json('admin_panel')) {
                    return ['status' => 'success'];
                }
                //kalo login success
                if ($is_android != 0 || $is_ios != 0) {

                    //kalo dari device
                    $checkdevice = UserDevice::where('device_type', '=', $device_type)
                        ->where('device_id', '=', $device_id)
                        ->where('device_token', '=', $device_token)
                        ->where('id_user', '=', $datauser[0]['id'])
                        ->get()
                        ->toArray();
                    if (!$checkdevice) {
                        //not trusted device or new device
                        $createdevice = UserDevice::updateOrCreate(['device_id' => $device_id], [
                            'id_user'           => $datauser[0]['id'],
                            'device_token'        => $device_token,
                            'device_type'        => $device_type
                        ]);
                        if ($device_type == "Android")
                            $update = User::where('id', '=', $datauser[0]['id'])->update(['android_device' => $device_id, 'ios_device' => null]);
                        if ($device_type == "IOS")
                            $update = User::where('id', '=', $datauser[0]['id'])->update(['android_device' => null, 'ios_device' => $device_id]);

                        if (stristr($useragent, 'iOS')) $useragent = 'iOS';
                        if (stristr($useragent, 'okhttp')) $useragent = 'Android';
                        if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';
                    }
                }

                if (stristr($useragent, 'iOS')) $useragent = 'iOS';
                if (stristr($useragent, 'okhttp')) $useragent = 'Android';
                if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';

                //update count login failed
                if ($datauser[0]['count_login_failed'] > 0) {
                    $updateCountFailed = User::where('phone', $phone)->update(['count_login_failed' => 0]);
                }

                $result             = [];
                $result['status']     = 'success';
                $res['date']     = date('Y-m-d H:i:s');
                $res['device']     = $device;
                $res['ip']         = $ip;

                if ($request->json('latitude') && $request->json('longitude')) {
                    $userLocation = UserLocation::create([
                        'id_user' => $datauser[0]['id'],
                        'lat' => $request->json('latitude'),
                        'lng' => $request->json('longitude'),
                        'action' => 'Login'
                    ]);
                }

                if ($device_id) {
                    $fraud = FraudSetting::where('parameter', 'LIKE', '%device ID%')->where('fraud_settings_status', 'Active')->first();
                    if ($fraud) {
                        app($this->setting_fraud)->createUpdateDeviceLogin($datauser[0], $device_id);
                        $deviceCus = UsersDeviceLogin::where('device_id', '=', $device_id)
                            ->where('status', 'Active')
                            ->select('id_user')
                            ->orderBy('created_at', 'asc')
                            ->groupBy('id_user')
                            ->get()->toArray('id_user');

                        $count = count($deviceCus);
                        $check = array_slice($deviceCus, (int) $fraud['parameter_detail']);
                        $check = array_column($check, 'id_user');

                        if ($deviceCus && count($deviceCus) > (int) $fraud['parameter_detail'] && array_search($datauser[0]['id'], $check) !== false) {
                            $emailSender = Setting::where('key', 'email_sender')->first();
                            $sendFraud = app($this->setting_fraud)->checkFraud($fraud, $datauser[0], ['device_id' => $device_id, 'device_type' => $useragent??null], 0, 0, null, 0);
                            $data = User::with('city')->where('phone', '=', $datauser[0]['phone'])->get()->toArray();

                            if ($data[0]['is_suspended'] == 1) {
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Akun Anda tidak dapat login di device ini karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
                                ]);
                            }
                        }
                    }
                }

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Login Success',
                        $phone,
                        [
                            'ip' => $ip,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s')
                        ]
                    );
                }

                if ($datauser[0]['pin_changed'] == '0') {
                    $res['pin_changed'] = false;
                } else {
                    $res['pin_changed'] = true;
                }
                $res['email'] = (empty($datauser[0]['email']) ?"" :$datauser[0]['email']);
                $result['result'] = $res;
            } else {
                //kalo login gagal
                if ($datauser) {
                    //update count login failed
                    $updateCountFailed = User::where('phone', $phone)->update(['count_login_failed' => $datauser[0]['count_login_failed'] + 1]);

                    $failedLogin = $datauser[0]['count_login_failed'] + 1;
                    //get setting login failed
                    $getSet = Setting::where('key', 'count_login_failed')->first();
                    if ($getSet && $getSet->value) {
                        if ($failedLogin >= $getSet->value) {
                            $autocrm = app($this->autocrm)->SendAutoCRM(
                                'Login Failed',
                                $phone,
                                [
                                    'ip' => $ip,
                                    'useragent' => $useragent,
                                    'now' => date('Y-m-d H:i:s')
                                ]
                            );
                        }
                    }
                }

                $result             = [];
                $result['status']     = 'fail';
                $result['messages'] = ['Kata sandi yang kamu masukkan kurang tepat'];
            }
        } else {
            $result['status']     = 'fail';
            $result['messages'] = ['Nomor HP belum terdaftar'];
        }



        return response()->json($result);
    }

    function resendPin(users_phone $request)
    {
        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        //get setting rule otp
        $setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if($setting && isset($setting['value_text'])){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
        }

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'otp_timer' => $holdTime,
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::select('*', \DB::raw('0 as challenge_key'))->where('phone', '=', $phone)
            ->get()
            ->toArray();

        if ($data) {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                return response()->json($checkRuleRequest);
            }

            if($checkRuleRequest == true && !isset($checkRuleRequest['otp_timer'])){
                $pinnya = rand(100000, 999999);
                $pin = bcrypt($pinnya);
                /*if($data[0]['phone_verified'] == 0){*/

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if($getSettingTimeExpired){
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
                }else{
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                $update = User::where('phone', '=', $phone)->update(['password' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);

                $useragent = $_SERVER['HTTP_USER_AGENT'];
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) $useragent = 'iOS';
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) $useragent = 'Android';
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) $useragent = 'Browser';

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Pin Create',
                        $phone,
                        []
                    );
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Pin Sent',
                        $phone,
                        [
                            'pin' => $pinnya,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s'),
                            'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                        ],
                        $useragent
                    );
                }
            }elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (env('OTP_TYPE', 'PHONE')) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phone, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WA':
                    $msg_otp = str_replace('%phone%', $phone, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phone, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'otp_timer' => $holdTime,
                    'result'    => [
                        'phone'    =>    $data[0]['phone'],
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'otp_timer' => $holdTime,
                    'result'    => [
                        'phone'    =>    $data[0]['phone'],
                        'pin'    =>    '',
                        'message' => $msg_otp,
                        'challenge_key' => $user->challenge_key
                    ]
                ];
            }
            /*} else {
                $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['This phone number is already verified']
                    ];
            }*/
        } else {
            $result = [
                'status'    => 'fail',
                'otp_timer' => $holdTime,
                'messages'    => ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function profileUpdateRegister(users_profile $request)
    {
        $phone = preg_replace("/[^0-9]/", "", $request->json('phone'));

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }


        $data = User::where('phone', '=', $phone)
            ->first();

        if (!empty($data)) {
            $dataupdate = [];
            if ($request->json('name')) {
                $dataupdate['name'] = $request->json('name');
            }

            if ($request->json('email')) {
                $domain = substr($request->json('email'), strpos($request->json('email'), "@") + 1);
                if (!filter_var($request->json('email'), FILTER_VALIDATE_EMAIL) ||  checkdnsrr($domain, 'MX') === false) {
                    $result = [
                        'status'    => 'fail',
                        'messages'    => ['Format email tidak valid.']
                    ];
                    return response()->json($result);
                }

                $checkEmail = User::where('email', '=', $request->json('email'))->first();
                if ($checkEmail) {
                    if ($checkEmail['phone'] != $phone) {
                        $result = [
                            'status'    => 'fail',
                            'messages'    => ['Email ini telah didaftarkan ke akun lain. Silakan pilih email lain.']
                        ];
                        return response()->json($result);
                    }
                }

                $dataupdate['email'] = $request->json('email');
            }

            if(!Auth::attempt(['phone' => $phone, 'password' => $request->json('pin_old')])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Pin Anda tidak sama.']
                ]);
            }

            $pin     = bcrypt($request->json('pin_new'));
            $dataupdate['password'] = $pin;
            $dataupdate['otp_forgot'] = null;
            $dataupdate['phone_verified'] = '1';
            $dataupdate['pin_changed'] = '1';
            $update = User::where('id', '=', $data['id'])->update($dataupdate);

            if($update){
                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone);
                    User::where('id', '=', $data['id'])->update(['first_pin_change' => ($data['first_pin_change'] + 1)]);
                }

                $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'phone'    =>    $data['phone'],
                        'challenge_key' => $user->challenge_key
                    ]
                ];
            }else{
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['Gagal melengkapi data diri.']
                ];
            }

        } else {
            $result = [
                'status'    => 'fail',
                'messages'    => ['Data tidak ditemukan']
            ];
        }

        return response()->json($result);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->devices()->where('device_id', $request->device_id)->delete();
        return [
            'status' => 'success'
        ];
    }

    public function socialCheck(Request $request)
    {
        $validate = Validator::make($request->json()->all(), [
            'provider' => ['required'],
            'provider_id' => ['required'],
            'provider_name' => ['required'],
            'provider_email' => ['required', 'email'],
            'provider_token' => ['required'],
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validate->errors()
            ]);
        } else {
            $post = $validate->validated();
        }

        try {
            $verified = Socialite::driver($post['provider'])->userFromToken($post['provider_token']);
        } catch (BadResponseException $error) {
            return response()->json([
                'status' => 'fail',
                'message' => json_decode($error->getResponse()->getBody()->getContents(), true) ?? null
            ]);
        }

        if (
            $verified->id == $post['provider_id'] &&
            $verified->token == $post['provider_token'] &&
            $verified->name == $post['provider_name']
        ) {
            $check = User::where('email', $post['provider_email'])->first();

            // create if only user does not registered in DB
            if (empty($check)) {
                $new_user = User::create([
                    'email' => $post['provider_email'],
                    'phone' => ' '
                ]);

                $idUser = $new_user->id;
                $result['register'] = true;
            } elseif(!empty($check) && $check['phone'] == ' '){
                $idUser = $check['id'];
                $result['register'] = true;
            }else {
                $idUser = $check['id'];
                $result['register'] = false;
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('id', $idUser)->first();
            $result['challenge_key'] = $user->challenge_key;
            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);

        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'invalid credentials'
            ]);
        }
    }

    public function socialCreate(Request $request){

        $validate = Validator::make($request->json()->all(), [
            'provider' => ['required'],
            'provider_id' => ['required'],
            'provider_token' => ['required'],
            'user_name' => ['required'],
            'user_email' => ['required', 'email'],
            'user_password' => ['required'],
            'user_phone' => ['required']
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validate->errors()
            ]);
        } else {
            $post = $validate->validated();
        }

        $phone = preg_replace("/[^0-9]/", "", $request->json('user_phone'));

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        try {
            $verified = Socialite::driver($post['provider'])->userFromToken($post['provider_token']);
        } catch (BadResponseException $error) {
            return response()->json([
                'status' => 'fail',
                'message' => json_decode($error->getResponse()->getBody()->getContents(), true) ?? null
            ]);
        }

        if (
            $verified->id == $post['provider_id'] &&
            $verified->token == $post['provider_token'] &&
            $verified->name == $post['user_name']
        ) {
            $check = User::where('email', $post['user_email'])->first();

            // create if only user does not registered in DB
            if (empty($check)) {
                $checkPhone = User::where('phone', $phone)->first();
                if(!empty($checkPhone)){
                    return response()->json([
                        'status' => 'fail',
                        'message' => ['Nomor telepon sudah digunakan']
                    ]);
                }
                $new_user = User::create([
                    'name' => $post['user_name'],
                    'email' => $post['user_email'],
                    'phone' => $phone,
                    'temporary_password' => bcrypt($post['user_password']),
                    'email_verified' => 1
                ]);

                $idUser = $new_user->id;
            } else {
                $checkPhone = User::where('phone', $phone)->whereNotIn('email', [$post['user_email']])->first();
                if(!empty($checkPhone)){
                    return response()->json([
                        'status' => 'fail',
                        'message' => ['Nomor telepon sudah digunakan']
                    ]);
                }

                User::where('id', $check['id'])->update([
                    'name' => $post['user_name'],
                    'phone' => $phone,
                    'temporary_password' => bcrypt($post['user_password']),
                    'email_verified' => 1
                ]);

                $idUser = $check['id'];
            }

            UserSocialLogin::updateOrCreate([
                'id_user' => $idUser,
                'provider' => $post['provider'],
                'provider_user_id' => $post['provider_id'],
            ],[
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return response()->json(['status' => 'success']);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'invalid credentials'
            ]);
        }
    }

    public function socialGetBearer(Request  $request){
        $validate = Validator::make($request->json()->all(), [
            'provider' => ['required'],
            'provider_id' => ['required'],
            'provider_name' => ['required'],
            'provider_email' => ['required', 'email'],
            'provider_token' => ['required'],
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validate->errors()
            ]);
        } else {
            $post = $validate->validated();
        }

        try {
            $verified = Socialite::driver($post['provider'])->userFromToken($post['provider_token']);
        } catch (BadResponseException $error) {
            return response()->json([
                'status' => 'fail',
                'message' => json_decode($error->getResponse()->getBody()->getContents(), true) ?? null
            ]);
        }

        if (
            $verified->id == $post['provider_id'] &&
            $verified->token == $post['provider_token'] &&
            $verified->name == $post['provider_name']
        ) {
            $getUser = User::where('email', $post['provider_email'])->first();
            if(empty($getUser)){
                return response()->json([
                    'status' => 'fail',
                    'message' => ['User tidak ditemukan']
                ]);
            }

            $user_social = UserSocialLogin::where('id_user', $getUser['id'])->where('provider_user_id', $post['provider_id'])->first();

            if(empty($user_social)){
                return response()->json([
                    'status' => 'fail',
                    'message' => ['User tidak ditemukan']
                ]);
            }

            return response()->json([
                'status' => 'success',
                'result' => $this->passwordLoginSocialMedia($getUser['phone'])
            ]);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'invalid credentials'
            ]);
        }
    }

    private function passwordLoginSocialMedia($phone = null)
    {
        $oclient = Client::where('password_client', 1)->first();

        $http = new GuzzleHttpClient();

        $response = $http->request('POST', url('oauth/token'), [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $oclient->id,
                'client_secret' => $oclient->secret,
                'username' => $phone,
                'password' => Crypt::encryptString(MyHelper::createRandomPIN(8, 'kecil')),
                'scope' => 'apps'
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    public function generateToken(){
        $log = MyHelper::logCron('Generate Token Valuefirst');
        $response = [];
        try {
            $currentDate = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
            $checkSetting = Setting::where('key', 'valuefirst_token')->first();
            $statusUpdate = 0;
            $date = (empty($checkSetting['value']) ? null : date('Y-m-d', strtotime($checkSetting['value'])));

            if(empty($checkSetting['value_text']) || (!empty($checkSetting['value']) && strtotime($currentDate) >= strtotime($date))){
                $valueFirst = new ValueFirst();
                $res = $valueFirst->generateToken($checkSetting['value_text']);
                $response = $res;
                $statusUpdate = 1;

                if(!empty($response['token'])){
                    Setting::updateOrCreate(['key' => 'valuefirst_token'], ['value' => $response['expiryDate'], 'value_text' => $response['token']]);
                }
            }

            $log->success(['status_update' => $statusUpdate, 'result' => $response]);
            return 'success';
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }
}
