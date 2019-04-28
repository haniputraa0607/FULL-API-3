<?php

namespace Modules\SettingFraud\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\FraudSetting;
use App\Http\Models\FraudDetectionLog;
use App\Http\Models\Setting;

use App\Lib\MyHelper;
use App\Lib\classMaskingJson;
use App\Lib\apiwha;

use Mailgun;

class ApiSettingFraud extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		$this->rajasms = new classMaskingJson();
		$this->apiwha = new apiwha();
    }

    function listSettingFraud(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_fraud_setting'])){
            $data = FraudSetting::find($post['id_fraud_setting']);
        }else{
            $data = FraudSetting::get();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function updateSettingFraud(Request $request){
        $post = $request->json()->all();

        $update = FraudSetting::where('id_fraud_setting', $post['id_fraud_setting'])->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    function SendFraudDetection($id_fraud_setting, $user, $idTransaction = null, $deviceUser = null){
        $fraudSetting = FraudSetting::find($id_fraud_setting);
        if(!$fraudSetting){
            return false;
        }
        //send notif to admin
        if($fraudSetting['email_toogle'] == '1'){
            $recipient_email = explode(',', str_replace(' ', ',', str_replace(';', ',', $fraudSetting['email_recipient'])));
            foreach($recipient_email as $key => $recipient){
                if($recipient != ' ' && $recipient != ""){
                    $to		 = $recipient;
                    $subject = app($this->autocrm)->TextReplace($fraudSetting['email_subject'], $user['phone'], ['transaction_count_day' => (string)$user['count_transaction_day'], 'transaction_count_week' => (string)$user['count_transaction_week'], 'last_device_id' => $deviceUser['device_id'], 'last_device_token' => $deviceUser['device_token'], 'last_device_type' => $deviceUser['device_type']]);
                    $content = app($this->autocrm)->TextReplace($fraudSetting['email_content'], $user['phone'], ['transaction_count_day' => (string)$user['count_transaction_day'], 'transaction_count_week' => (string)$user['count_transaction_week'], 'last_device_id' => $deviceUser['device_id'], 'last_device_token' => $deviceUser['device_token'], 'last_device_type' => $deviceUser['device_type']]);
                    
                    //get setting email
                    $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
                    $setting = array();
                    foreach ($getSetting as $key => $value) {
                        $setting[$value['key']] = $value['value']; 
                    }
                    
                    $em_arr = explode('@',$recipient);
                    $name = ucwords(str_replace("_"," ", str_replace("-"," ", str_replace("."," ", $em_arr[0]))));
    
                    $data = array(
                        'customer' => $name,
                        'html_message' => $content,
                        'setting' => $setting
                    );
                    
                    Mailgun::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
                    {
                        $message->to($to, $name)->subject($subject)
                                        ->trackClicks(true)
                                        ->trackOpens(true);
                        if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                            $message->from($setting['email_from'], $setting['email_sender']);
                        }else if(!empty($setting['email_from'])){
                            $message->from($setting['email_from']);
                        }
    
                        if(!empty($setting['email_reply_to'])){
                            $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                        }
    
                        if(!empty($setting['email_cc']) && !empty($setting['email_cc_name'])){
                            $message->cc($setting['email_cc'], $setting['email_cc_name']);
                        }
    
                        if(!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])){
                            $message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
                        }
                    });
                }
            }
        }
			
        if($fraudSetting['sms_toogle'] == '1'){
            $recipient_sms = explode(',', str_replace(' ', ',', str_replace(';', ',', $fraudSetting['sms_recipient'])));
            foreach($recipient_sms as $key => $recipient){
                if($recipient != ' ' && $recipient != ""){
                    $senddata = array(
                        'apikey' => env('SMS_KEY'),  
                        'callbackurl' => env('APP_URL'), 
                        'datapacket'=>array()
                    );
                    
                    $content 	= app($this->autocrm)->TextReplace($fraudSetting['sms_content'], $user['phone'], ['transaction_count_day' => $user['count_transaction_day'], 'transaction_count_week' => $user['count_transaction_week'], 'last_device_id' => $deviceUser['device_id'], 'last_device_token' => $deviceUser['device_token'], 'last_device_type' => $deviceUser['device_type']]);
                    array_push($senddata['datapacket'],array(
                            'number' => trim($recipient),
                            'message' => urlencode(stripslashes(utf8_encode($content))),
                            'sendingdatetime' => ""));
                            
                    $this->rajasms->setData($senddata);
                    $send = $this->rajasms->send();
                }
            }
        }

        if($fraudSetting['whatsapp_toogle'] == '1'){
            $recipient_whatsapp = explode(',', str_replace(' ', ',', str_replace(';', ',', $fraudSetting['whatsapp_recipient'])));
            foreach($recipient_whatsapp as $key => $recipient){
                //cek api key whatsapp
                $api_key = Setting::where('key', 'api_key_whatsapp')->first();
                if($api_key){
                    if($api_key->value){
                        if($recipient != ' ' && $recipient != ""){
                            $content = $this->TextReplace($fraudSetting['whatsapp_content'], $user['phone'], ['transaction_count_day' => $user['count_transaction_day'], 'transaction_count_week' => $user['count_transaction_week'], 'last_device_id' => $deviceUser['device_id'], 'last_device_token' => $deviceUser['device_token'], 'last_device_type' => $deviceUser['device_type']]);
                                
                            // add country code in number
                            $ptn = "/^0/";  
                            $rpltxt = "62"; 
                            $phone = preg_replace($ptn, $rpltxt, $recipient);

                            $send = $this->apiwha->send($api_key->value, $phone, $content);

                            //api key whatsapp not valid
                            if(isset($send['result_code']) && $send['result_code'] == -1){
                                break 1;
                            }
                        }
                    }
                }
            }
        }

        $log['id_user'] = $user['id'];
        $log['id_fraud_setting'] = $id_fraud_setting;

        if($idTransaction != null){
            $log['count_transaction_day'] = $user['count_transaction_day'];
            $log['count_transaction_week'] = $user['count_transaction_week'];
            $log['id_transaction'] = $idTransaction;
        }

        if($deviceUser){
            $log['id_device_user'] = $deviceUser['id_device_user'];
        }
        
        $insertLog = FraudDetectionLog::create($log);
        if(!$insertLog){
             return false;   
        }
        
        return true;
	}
}
