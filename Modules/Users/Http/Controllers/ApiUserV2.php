<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\User;
use App\Http\Models\UserLocation;
use App\Http\Models\Level;
use App\Http\Models\Doctor;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use Modules\Users\Http\Requests\UsersForgot;
use Modules\Users\Http\Requests\UsersPhone;
use Modules\Users\Http\Requests\UsersPhonePin_admin;
use Modules\Users\Http\Requests\UsersPhonePinNewV2;
use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Auth;

class ApiUserV2 extends Controller
{
    public function __construct()
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

    public function phoneCheck(UsersPhone $request)
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

        $data = User::select('*', \DB::raw('0 as challenge_key'))->with('city')->where('phone', '=', $phone)->get()->toArray();

        if (isset($data[0]['is_suspended']) && $data[0]['is_suspended'] == '1') {
            $emailSender = Setting::where('key', 'email_sender')->first();
            return response()->json([
                'status' => 'fail',
                'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di ' . $emailSender['value'] ?? '']
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

        if ($data) {
            if ($data[0]['phone_verified'] == 0 && empty($data[0]['pin_changed'])) {
                $result['register'] = true;
                $result['forgot'] = false;
                $result['confirmation_message'] = $msg_check;
                $result['is_suspended'] = $data[0]['is_suspended'];
                return response()->json([
                    'status' => 'success',
                    'result' => $result
                ]);
            } else {
                $result['register'] = false;
                $result['forgot'] = false;
                $result['challenge_key'] = $data[0]['challenge_key'];
                $result['is_suspended'] = $data[0]['is_suspended'];
                $result['confirmation_message'] = $msg_check;
                return response()->json([
                    'status' => 'success',
                    'result' => $result
                ]);
            }
        } else {
            return response()->json([
                'status' => 'success',
                'result' => [
                    'register' => true,
                    'is_suspended' => 0,
                    'forgot' => false,
                    'confirmation_message' => $msg_check
                ]
            ]);
        }
    }

    public function pinRequest(UsersPhone $request)
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
        if ($setting && isset($setting['value_text'])) {
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
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) {
                $useragent = 'IOS';
            }
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) {
                $useragent = 'Android';
            }
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) {
                $useragent = 'Browser';
            }

            if (empty($device_type)) {
                $device_type = $useragent;
            }

            if ($device_type == "Android") {
                $is_android = 1;
            } elseif ($device_type == "IOS") {
                $is_ios = 1;
            }

            if ($request->json('device_token') != "") {
                $device_token = $request->json('device_token');
            }

            //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
            $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
            if ($getSettingTimeExpired) {
                $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+" . $getSettingTimeExpired['value'] . " minutes"));
            } else {
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
                if (isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail') {
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
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp', 'value', 30),
                    ],
                    $useragent,
                    false,
                    false,
                    null,
                    null,
                    true,
                    $request->request_type
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

            switch (strtoupper($request->request_type)) {
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
            if (isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail') {
                return response()->json($checkRuleRequest);
            }

            if ($checkRuleRequest == true && !isset($checkRuleRequest['otp_timer'])) {
                $pinnya = MyHelper::createRandomPIN(6, 'angka');
                $pin = bcrypt($pinnya);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if ($getSettingTimeExpired) {
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+" . $getSettingTimeExpired['value'] . " minutes"));
                } else {
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                if (!empty($data[0]['pin_changed'])) {
                    $update = User::where('phone', '=', $phone)->update(['otp_forgot' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);
                } else {
                    $update = User::where('phone', '=', $phone)->update(['password' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);
                }

                $useragent = $_SERVER['HTTP_USER_AGENT'];
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) {
                    $useragent = 'iOS';
                }
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) {
                    $useragent = 'Android';
                }
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) {
                    $useragent = 'Browser';
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
                            'pin' => $pinnya,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s'),
                            'expired_time' => (string) MyHelper::setting('setting_expired_otp', 'value', 30),
                        ],
                        $useragent,
                        false,
                        false,
                        null,
                        null,
                        true,
                        $request->request_type
                    );
                }
            } elseif (isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false) {
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (strtoupper($request->request_type)) {
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

            $user = User::select('password', \DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

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

    public function changePin(UsersPhonePinNewV2 $request)
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
            if (!empty($data[0]['otp_forgot']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('pin_old'), $data[0]['otp_forgot'])) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Current PIN doesn\'t match']
                ]);
            } elseif (empty($data[0]['otp_forgot']) && !empty($data[0]['pin_changed']) && !empty($data[0]['phone_verified']) && !Auth::attempt(['phone' => $phone, 'password' => $request->json('pin_old')])) {
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

            $user = User::select('password', \DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

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

    public function forgotPin(UsersForgot $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        //get setting rule otp
        $setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if ($setting && isset($setting['value_text'])) {
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

        $data = User::select('*', \DB::raw('0 as challenge_key'))->where('phone', '=', $phone)
            ->get()
            ->toArray();

        if ($data) {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if (isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail') {
                return response()->json($checkRuleRequest);
            }

            if (!isset($checkRuleRequest['otp_timer']) && $checkRuleRequest == true) {
                $pin = MyHelper::createRandomPIN(6, 'angka');
                $password = bcrypt($pin);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if ($getSettingTimeExpired) {
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+" . $getSettingTimeExpired['value'] . " minutes"));
                } else {
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                $update = User::where('id', '=', $data[0]['id'])->update(['otp_forgot' => $password, 'otp_valid_time' => $dateOtpTimeExpired]);

                if (!empty($request->header('user-agent-view'))) {
                    $useragent = $request->header('user-agent-view');
                } else {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if (stristr($useragent, 'iOS')) {
                    $useragent = 'iOS';
                }
                if (stristr($useragent, 'okhttp')) {
                    $useragent = 'Android';
                }
                if (stristr($useragent, 'GuzzleHttp')) {
                    $useragent = 'Browser';
                }

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Forgot',
                    $phone,
                    [
                        'pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s'),
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp', 'value', 30),
                    ],
                    $useragent,
                    false,
                    false,
                    null,
                    null,
                    true,
                    $request->request_type
                );
            } elseif (isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false) {
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (strtoupper($request->request_type)) {
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

            $user = User::select('password', \DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

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
}
