<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

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
    public function checkPhoneNumber(Request $request)
    {
        $post = $request->json()->all();

        //cek phone format
        $phone = preg_replace("/[^0-9]/", "", $post['phone']);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($post['phone']);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        //cek account
        $check = Doctor::where('doctor_phone', $post['phone'])->OnlyVerified()->first();

        if(isset($check)) {
            $check = $check->toArray();

            return response()->json(['status'  => 'success', 'result' => ['phone_status' => 'registered', 'phone_number' => $check['doctor_phone']]]);    
        }

        //save phone to database
        DB::beginTransaction(); 
        try {
            //create new doctor account
            $newDoctor = Doctor::create(['doctor_phone' => $post['phone']]);
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Account Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        $request['doctor'] = $newDoctor;

        $sendOtp = $this->sendOtp($request, $phone);

        if($sendOtp['status'] == 'fail') {
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'OTP failed to send'
            ]);
        }

        $result = [
            'phone_status' => 'not registered',
            'messages' => 'OTP Has been sent',
            'phone_number' => $request['phone']
        ];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function forgotPassword(Request $request)
    {
        $post = $request->json()->all();
        $check = Doctor::where('doctor_phone', $post['phone'])->first();

        if(!isset($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'Account Not Found'
            ]);    
        }

        $sendOtp = $this->sendOtp($request);

        if($sendOtp['status'] == 'fail') {
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'OTP failed to send'
            ]);
        }

        $result = [
            'messages' => 'OTP Has been sent',
            'phone_number' => $request['phone']
        ];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function changePassword(Request $request)
    {
        $post = $request->json()->all();
        $check = Doctor::where('id_doctor', $post['id_doctor'])->first();

        if(!isset($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'Account Not Found'
            ]);    
        }

        DB::beginTransaction(); 
        try {
            //bcrypt post pin
            $post['password'] = bcrypt($post['pin']);

            //set expired to oldOtp
            $update = Doctor::where('id_doctor', $post['id_doctor'])->update(['password' => $post['password']]);
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Update Password Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        return response()->json(['status'  => 'success', 'result' => 'Password has been successfully updated']);
    }
    
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function sendOtp(Request $request, $phone = null)
    {
        $post = $request->json()->all();

        $now = new DateTime();
        $expired = $now->modify('+5 minutes');

        $post['otp'] = rand(100000, 999999);
        $post['phone_number'] = $post['phone'];
        $post['expired_at'] =  $expired;
        $post['purpose'] = $post['purpose'];

        DB::beginTransaction(); 
        try {
            //set expired to oldOtp
            $oldOtp = OTP::where('phone_number', $post['phone'])->where('purpose', $post['purpose'])->onlyNotExpired();
            $oldOtp->update(['is_expired' => 1]);

            //create new OTP
            unset($post['phone']);
            $otp = OTP::create($post);

            $send 	= app($this->autocrm)->SendAutoCRM('Doctor Pin Sent', $phone, null, null, false, false, 'doctor');
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Send Token Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        $result = [
            'status'  => 'success',
            'message' => 'OTP Has been sent'
        ];
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function otpVerification(Request $request)
    {
        $post = $request->json()->all();

        $check = OTP::where('phone_number', $post['phone'])->where('purpose', $post['purpose'])->onlyNotExpired()->first();

        if(!isset($check)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'OTP Not Found'
            ]);
        }

        //check if OTP expired
        $otp = $check->toArray();
        $expired_at = new DateTime($otp['expired_at']);
        $now = new DateTime();

        if($expired_at < $now) {
            $expiredOTP = OTP::where('id_otp', $otp['id_otp'])->update(['is_expired' => 1]);

            return response()->json([
                'status'    => 'fail',
                'messages'  => 'OTP is Expired'
            ]);
        }

        switch ($post['purpose']) {
            case "registration":
                //verified phone
                DB::beginTransaction(); 
                try {
                    //create new doctor account
                    $updateDoctor = Doctor::where('doctor_phone', $post['phone'])->update(['phone_verified' => true]);

                    $expiredOTP = OTP::where('id_otp', $otp['id_otp'])->update(['is_expired' => 1]);
                } catch (\Exception $e) {
                    $result = [
                        'status'  => 'fail',
                        'message' => 'Create Account Failed'
                    ];
                    DB::rollBack();
                    return response()->json($result);
                }
                DB::commit();

                return response()->json(['status'    => 'success', 'messages'  => 'Account Created Successfully', 'phone' => $post['phone']]);

            case "forgot-password":
                //verified OTP
                DB::beginTransaction(); 
                try {
                    //get related Doctor
                    $doctor = Doctor::where(['doctor_phone' => $post['phone']])->first();

                    $expiredOTP = OTP::where('id_otp', $otp['id_otp'])->update(['is_expired' => 1]);
                } catch (\Exception $e) {
                    $result = [
                        'status'  => 'fail',
                        'message' => 'Create Account Failed'
                    ];
                    DB::rollBack();
                    return response()->json($result);
                }
                DB::commit();

                return response()->json(['status'    => 'fail', 'messages'  => 'OTP successfully verified', 'doctor' => $doctor]);

            default:
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => 'OTP Purpose Not Found'
                ]);
        }

        return response()->json([
            'status'    => 'fail',
            'messages'  => 'OTP Not Found'
        ]);
    }
}