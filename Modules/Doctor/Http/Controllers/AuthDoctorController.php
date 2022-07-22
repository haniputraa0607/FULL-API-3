<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;

use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\Otp;
use DateTime;
use DB;

class AuthDoctorController extends Controller
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function phoneCheck(Request $request)
    {
        $phone = $request->json('phone');

        //cek phone format
        $phoneOld = $phone;
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

        $data = Doctor::select('*',\DB::raw('0 as challenge_key'))->where('doctor_phone', '=', $phone)->first();

        if($data){
            if (isset($data['is_active']) && $data['is_active'] == '0') {
                $emailSender = Setting::where('key', 'email_sender')->first();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Akun Anda sudah tidak aktif. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
                ]);
            }

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

    // /**
    //  * Display a listing of the resource.
    //  * @return Response
    //  */
    // public function forgotPassword(Request $request)
    // {
    //     $post = $request->json()->all();
    //     $check = Doctor::where('doctor_phone', $post['phone'])->first();

    //     if(!isset($check)) {
    //         return response()->json([
    //             'status'    => 'fail',
    //             'messages'  => 'Account Not Found'
    //         ]);    
    //     }

    //     $sendOtp = $this->sendOtp($request);

    //     if($sendOtp['status'] == 'fail') {
    //         return response()->json([
    //             'status'    => 'fail',
    //             'messages'  => 'OTP failed to send'
    //         ]);
    //     }

    //     $result = [
    //         'messages' => 'OTP Has been sent',
    //         'phone_number' => $request['phone']
    //     ];

    //     return response()->json(['status'  => 'success', 'result' => $result]);
    // }

    // /**
    //  * Display a listing of the resource.
    //  * @return Response
    //  */
    // public function changePassword(Request $request)
    // {
    //     $post = $request->json()->all();
    //     $check = Doctor::where('id_doctor', $post['id_doctor'])->first();

    //     if(!isset($check)) {
    //         return response()->json([
    //             'status'    => 'fail',
    //             'messages'  => 'Account Not Found'
    //         ]);    
    //     }

    //     DB::beginTransaction(); 
    //     try {
    //         //bcrypt post pin
    //         $post['password'] = bcrypt($post['pin']);

    //         //set expired to oldOtp
    //         $update = Doctor::where('id_doctor', $post['id_doctor'])->update(['password' => $post['password']]);
    //     } catch (\Exception $e) {
    //         $result = [
    //             'status'  => 'fail',
    //             'message' => 'Update Password Failed'
    //         ];
    //         DB::rollBack();
    //         return response()->json($result);
    //     }
    //     DB::commit();

    //     return response()->json(['status'  => 'success', 'result' => 'Password has been successfully updated']);
    // }
    
    // /**
    //  * Display a listing of the resource.
    //  * @return Response
    //  */
    // public function sendOtp(Request $request, $phone = null)
    // {
    //     $post = $request->json()->all();

    //     $now = new DateTime();
    //     $expired = $now->modify('+5 minutes');

    //     $post['otp'] = rand(100000, 999999);
    //     $post['phone_number'] = $post['phone'];
    //     $post['expired_at'] =  $expired;
    //     $post['purpose'] = $post['purpose'];

    //     DB::beginTransaction(); 
    //     try {
    //         //set expired to oldOtp
    //         $oldOtp = OTP::where('phone_number', $post['phone'])->where('purpose', $post['purpose'])->onlyNotExpired();
    //         $oldOtp->update(['is_expired' => 1]);

    //         //create new OTP
    //         unset($post['phone']);
    //         $otp = OTP::create($post);

    //         //TO DO changes autocrm function name
    //         $send 	= app($this->autocrm)->SendAutoCRM('Doctor Pin Sent', $phone, null, null, false, false, 'doctor');
    //     } catch (\Exception $e) {
    //         $result = [
    //             'status'  => 'fail',
    //             'message' => 'Send Token Failed'
    //         ];
    //         DB::rollBack();
    //         return response()->json($result);
    //     }
    //     DB::commit();

    //     $result = [
    //         'status'  => 'success',
    //         'message' => 'OTP Has been sent'
    //     ];
    // }

    // /**
    //  * Display a listing of the resource.
    //  * @return Response
    //  */
    // public function otpVerification(Request $request)
    // {
    //     $post = $request->json()->all();

    //     $check = OTP::where('phone_number', $post['phone'])->where('purpose', $post['purpose'])->onlyNotExpired()->first();

    //     if(!isset($check)){
    //         return response()->json([
    //             'status'    => 'fail',
    //             'messages'  => 'OTP Not Found'
    //         ]);
    //     }

    //     //check if OTP expired
    //     $otp = $check->toArray();
    //     $expired_at = new DateTime($otp['expired_at']);
    //     $now = new DateTime();

    //     if($expired_at < $now) {
    //         $expiredOTP = OTP::where('id_otp', $otp['id_otp'])->update(['is_expired' => 1]);

    //         return response()->json([
    //             'status'    => 'fail',
    //             'messages'  => 'OTP is Expired'
    //         ]);
    //     }

    //     switch ($post['purpose']) {
    //         /*case "registration":
    //             //verified phone
    //             DB::beginTransaction(); 
    //             try {
    //                 //create new doctor account
    //                 $updateDoctor = Doctor::where('doctor_phone', $post['phone'])->update(['phone_verified' => true]);

    //                 $expiredOTP = OTP::where('id_otp', $otp['id_otp'])->update(['is_expired' => 1]);
    //             } catch (\Exception $e) {
    //                 $result = [
    //                     'status'  => 'fail',
    //                     'message' => 'Create Account Failed'
    //                 ];
    //                 DB::rollBack();
    //                 return response()->json($result);
    //             }
    //             DB::commit();

    //             return response()->json(['status'    => 'success', 'messages'  => 'Account Created Successfully', 'phone' => $post['phone']]); */

    //         case "forgot-password":
    //             //verified OTP
    //             DB::beginTransaction(); 
    //             try {
    //                 //get related Doctor
    //                 $doctor = Doctor::where(['doctor_phone' => $post['phone']])->first();

    //                 $expiredOTP = OTP::where('id_otp', $otp['id_otp'])->update(['is_expired' => 1]);
    //             } catch (\Exception $e) {
    //                 $result = [
    //                     'status'  => 'fail',
    //                     'message' => 'Create Account Failed'
    //                 ];
    //                 DB::rollBack();
    //                 return response()->json($result);
    //             }
    //             DB::commit();

    //             return response()->json(['status'    => 'fail', 'messages'  => 'OTP successfully verified', 'doctor' => $doctor]);

    //         default:
    //             return response()->json([
    //                 'status'    => 'fail',
    //                 'messages'  => 'OTP Purpose Not Found'
    //             ]);
    //     }

    //     return response()->json([
    //         'status'    => 'fail',
    //         'messages'  => 'OTP Not Found'
    //     ]);
    // }

    function forgotPin(Request $request)
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

        $doctor = Doctor::where('doctor_phone', '=', $phone)->first();

        if (!$doctor) {
            $result = [
                'status'    => 'fail',
                'otp_timer' => $holdTime,
                'messages'    => ['Doctor not found.']
            ];
            return response()->json($result);
        }

        $doctor->sms_increment = 0;
        $doctor->save();

        $data = Doctor::select('*',\DB::raw('0 as challenge_key'))->where('doctor_phone', '=', $phone)
            ->get()
            ->toArray();

        if ($data) {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                return response()->json($checkRuleRequest);
            }

            if(!isset($checkRuleRequest['otp_timer']) && $checkRuleRequest == true){
                // $pin = MyHelper::createRandomPIN(6, 'angka');
                $pin = "777777";
                $password = bcrypt($pin);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if($getSettingTimeExpired){
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
                }else{
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                $update = Doctor::where('id_doctor', '=', $data[0]['id_doctor'])->update(['otp_forgot' => $password, 'otp_valid_time' => $dateOtpTimeExpired]);

                if (!empty($request->header('user-agent-view'))) {
                    $useragent = $request->header('user-agent-view');
                } else {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if (stristr($useragent, 'iOS')) $useragent = 'iOS';
                if (stristr($useragent, 'okhttp')) $useragent = 'Android';
                if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Doctor Pin Forgot', 
                    $phone,
                    [
                        'pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s'),
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                    ],
                    $useragent,
                    false, false, 'doctor', null, true, $request->request_type
                );

                dd($autocrm);
            }elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
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

            $doctor = Doctor::select('password',\DB::raw('0 as challenge_key'))->where('doctor_phone', $phone)->first();

            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $phone,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $doctor->challenge_key,
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
                        'challenge_key' => $doctor->challenge_key,
                        'forget' => true
                    ]
                ];
            }
            return response()->json($result);
        } else {
            $result = [
                'status'    => 'fail',
                'messages'  => ['Nomor yang kamu masukkan kurang tepat']
            ];
            return response()->json($result);
        }
    }

    function verifyPin(Request $request)
    {
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

    	$data = Doctor::where('doctor_phone', '=', $phone)->get()->toArray();

    	if ($data) {
    		if(!password_verify($request->json('pin'), $data[0]['otp_forgot'])){
    			return response()->json([
    				'status'    => 'fail',
    				'messages'    => ['OTP yang kamu masukkan salah']
    			]);
    		}

    		/*first if --> check if otp have expired and the current time exceeds the expiration time*/
    		if(!is_null($data[0]['otp_valid_time']) && strtotime(date('Y-m-d H:i:s')) > strtotime($data[0]['otp_valid_time'])){
    			return response()->json(['status' => 'fail', 'otp_check'=> 1, 'messages' => ['This OTP is expired, please re-request OTP from apps']]);
    		}

    		$update = Doctor::where('id_doctor', '=', $data[0]['id_doctor'])->update(['otp_valid_time' => NULL]);
    		if ($update) {
    			if (\Module::collections()->has('Autocrm')) {
    				$autocrm = app($this->autocrm)->SendAutoCRM('Pin Verify', $phone, null, null, false, false, 'doctor');
    			}
    			$result = [
    				'status'    => 'success',
    				'result'    => [
    					'phone'    =>    $data[0]['doctor_phone']
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

    function changePin(Request $request)
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

    	$data = Doctor::where('doctor_phone', '=', $phone)->first();

    	if ($data) {
    		if(!empty($data['otp_forgot']) && !password_verify($request->json('pin_old'), $data['otp_forgot'])){
    			return response()->json([
    				'status'    => 'fail',
    				'messages'    => ['Current PIN doesn\'t match']
    			]);
    		}elseif(empty($data['otp_forgot']) && !password_verify($request->json('pin_old'), $data['password'])){
    			return response()->json([
    				'status'    => 'fail',
    				'messages'    => ['Current PIN doesn\'t match']
    			]);
    		}

    		$pin     = bcrypt($request->json('pin_new'));
    		$update = Doctor::where('id_doctor', '=', $data['id_doctor'])->update(['password' => $pin, 'otp_forgot' => null]);
    		if (\Module::collections()->has('Autocrm')) {
    			if ($data['first_update_password'] < 1) {
    				$autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone, null, null, false, false, 'doctor');
    				$changepincount = $data['first_update_password'] + 1;
    				$update = Doctor::where('id_doctor', '=', $data['id_doctor'])->update(['first_update_password' => $changepincount]);
    			} else {
    				$autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone, null, null, false, false, 'doctor');

    				$del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
    				->where('oauth_access_tokens.user_id', $data['id_doctor'])->where('oauth_access_token_providers.provider', 'doctor')->delete();
    			}
    		}

    		$user = Doctor::select('password',\DB::raw('0 as challenge_key'))->where('doctor_phone', $phone)->first();

    		$result = [
    			'status'    => 'success',
    			'result'    => [
    				'phone'    =>    $data['doctor_phone'],
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
}