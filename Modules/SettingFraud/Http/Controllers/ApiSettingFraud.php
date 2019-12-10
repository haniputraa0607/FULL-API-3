<?php

namespace Modules\SettingFraud\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Http\Models\UserDevice;
use App\Http\Models\UsersDeviceLogin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\FraudSetting;
use App\Http\Models\FraudDetectionLog;
use App\Http\Models\FraudDetectionLogDevice;
use App\Http\Models\FraudDetectionLogTransactionDay;
use App\Http\Models\FraudDetectionLogTransactionWeek;
use App\Http\Models\Setting;
use Illuminate\Support\Facades\DB;

use App\Lib\MyHelper;
use App\Lib\classMaskingJson;
use App\Lib\apiwha;
use DateTime;
use Mailgun;
use function GuzzleHttp\Psr7\str;

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
        unset($post['type']);
        unset($post['fraud_settings_status']);

        if(isset($post['auto_suspend_status'])){
            $post['auto_suspend_status'] = 1;
        }else{
            $post['auto_suspend_status'] = 0;
        }

        if(isset($post['forward_admin_status'])){
            $post['forward_admin_status'] = 1;
        }else{
            $post['forward_admin_status'] = 0;
        }

        $update = FraudSetting::where('id_fraud_setting', $post['id_fraud_setting'])->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    function createUpdateDeviceLogin($user, $deviceID = null){
        $getDeviceLogin = UsersDeviceLogin::where('id_user',$user['id'])->where('device_id', '=' ,$deviceID)->first();

        if($getDeviceLogin){
            if($getDeviceLogin['status'] == 'Inactive'){
                $dt = [
                    'status' => 'Active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }else{
                $dt = [
                    'last_login' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            $update = UsersDeviceLogin::where('id_user',$user['id'])->where('device_id', '=' ,$deviceID)
                ->update($dt);
        }else{
            $update = UsersDeviceLogin::create([
                'id_user' => $user['id'],
                'device_id' => $deviceID,
                'last_login' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        if(!$update){
            return false;
        }
        return true;
    }

    function checkFraud($fraudSetting, $user, $device = null, $countTrxDay, $countTrxWeek, $dateTime, $deleteToken, $trxId = null){

        if(strpos($fraudSetting['parameter'], 'device') !== false){

            $deviceCus = UsersDeviceLogin::where('device_id', '=' ,$device['device_id'])
                ->where('status','Active')
                ->orderBy('created_at','desc')
                ->groupBy('id_user')
                ->get()->toArray();

            if($deviceCus && $deviceCus[0]['id_user'] ==  $user['id'] && count($deviceCus) > (int)$fraudSetting['parameter_detail']){
                $sendFraud = $this->SendFraudDetection($fraudSetting['id_fraud_setting'], $user, null, $device, $deleteToken, $dateTime, $countTrxDay, $countTrxWeek);
            }
        }elseif(strpos($fraudSetting['parameter'], 'transactions in 1 day') !== false){

            if($countTrxDay > (int)$fraudSetting['parameter_detail']){
                $sendFraud = $this->SendFraudDetection($fraudSetting['id_fraud_setting'], $user, null, null, 0, $dateTime, $countTrxDay, $countTrxWeek, $trxId);
            }
        }elseif(strpos($fraudSetting['parameter'], 'transactions in 1 week') !== false){
            if($countTrxWeek > (int)$fraudSetting['parameter_detail']){
                $sendFraud = $this->SendFraudDetection($fraudSetting['id_fraud_setting'], $user, null, null, 0, $dateTime, $countTrxDay, $countTrxWeek, $trxId);
            }
        }

        if(isset($sendFraud))
            return $sendFraud;
        else
            return false;
    }

    function SendFraudDetection($id_fraud_setting, $user, $idTransaction = null, $deviceUser = null, $deleteToken, $dateTime, $countTrxDay, $countTrxWeek, $trxId = null){
        $fraudSetting = FraudSetting::find($id_fraud_setting);
        $autoSuspend = 0;
        $forwardAdmin = 0;
        $countUser = 0;
        $stringUserList = '';
        $stringTransactionDay = '';
        $stringTransactionWeek = '';
        $areaOutlet = '';
        if(!$fraudSetting){
            return false;
        }

        if(strpos($fraudSetting['parameter'], 'device') !== false){

            $checkLog = FraudDetectionLogDevice::where('id_user',$user['id'])->where('device_id',$deviceUser['device_id'])->first();

            if(!empty($checkLog)){
                $dt = [
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $updateLog = FraudDetectionLogDevice::where('id_user',$user['id'])->where('device_id',$deviceUser['device_id'])->update($dt);
                if(!$updateLog){
                    return false;
                }
            }else{
                $dt = [
                    'id_user' => $user['id'],
                    'device_id' => $deviceUser['device_id'],
                    'device_type' => $deviceUser['device_type'],
                    'fraud_setting_parameter_detail' => $fraudSetting['parameter_detail'],
                    'fraud_setting_forward_admin_status' => $fraudSetting['forward_admin_status'],
                    'fraud_setting_auto_suspend_status' => $fraudSetting['auto_suspend_status'],
                    'fraud_setting_auto_suspend_value' => $fraudSetting['auto_suspend_value'],
                    'fraud_setting_auto_suspend_time_period' => $fraudSetting['suspend_time_period']
                ];
                $insertLog = FraudDetectionLogDevice::create($dt);
                if(!$insertLog){
                    return false;
                }
            }

            $getLogUserDevice = FraudDetectionLogDevice::where('device_id',$deviceUser['device_id'])->where('status','Active')->get()->toArray();

            if($getLogUserDevice){
                if($fraudSetting['auto_suspend_status'] == '1'){
                    $autoSuspend = 1;
                }

                if($fraudSetting['forward_admin_status'] == '1'){
                    $forwardAdmin = 1;
                    $list_user = UsersDeviceLogin::join('users','users.id','users_device_login.id_user')
                        ->where('users_device_login.device_id',$deviceUser['device_id'])
                        ->where('users_device_login.status','Active')
                        ->orderBy('users_device_login.created_at','asc')
                        ->get()->toArray();
                    $countUser = count($list_user);

                    $stringUserList = '';

                    if(count($list_user) > 0){
                        $no = 0;
                        $stringUserList .= '<table id="table-fraud-list">';
                        $stringUserList .= '<tr>';
                        $stringUserList .= '<td>Nomor</td>';
                        $stringUserList .= '<td>Name</td>';
                        $stringUserList .= '<td>Phone</td>';
                        $stringUserList .= '<td>Email</td>';
                        $stringUserList .= '<td>Last Login</td>';
                        $stringUserList .= '<tr>';
                        foreach ($list_user as $val){
                            $no = $no + 1;
                            $stringUserList .= '<tr>';
                            $stringUserList .= '<td>Akun'.$no.'</td>';
                            $stringUserList .= '<td>'.$val['name'].'</td>';
                            $stringUserList .= '<td>'.$val['phone'].'</td>';
                            $stringUserList .= '<td>'.$val['email'].'</td>';
                            $stringUserList .= '<td>'.date('d F Y H:i',strtotime($val['last_login'])).'</td>';
                            $stringUserList .= '<tr>';
                        }
                        $stringUserList .= '</table>';
                    }
                }
            }
        }elseif(strpos($fraudSetting['parameter'], 'transactions in 1 day') !== false){
            $getFraudLogDay = FraudDetectionLogTransactionDay::whereRaw("DATE(fraud_detection_date) = '".date('Y-m-d', strtotime($dateTime))."'")->where('id_user', $user['id'])
                ->where('status', 'Active')->first();

            if($getFraudLogDay){
                FraudDetectionLogTransactionDay::where('id_user',$user['id'])->where('id_fraud_detection_log_transaction_day',$getFraudLogDay['id_fraud_detection_log_transaction_day'])->update([
                    'count_transaction_day' => $countTrxDay,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }else{
                $createLog = FraudDetectionLogTransactionDay::create([
                    'id_user' => $user['id'],
                    'count_transaction_day' => $countTrxDay,
                    'fraud_detection_date'=> date('Y-m-d H:i:s', strtotime($dateTime)),
                    'fraud_setting_parameter_detail' => $fraudSetting['parameter_detail'],
                    'fraud_setting_forward_admin_status' => $fraudSetting['forward_admin_status'],
                    'fraud_setting_auto_suspend_status' => $fraudSetting['auto_suspend_status'],
                    'fraud_setting_auto_suspend_value' => $fraudSetting['auto_suspend_value'],
                    'fraud_setting_auto_suspend_time_period' => $fraudSetting['suspend_time_period']
                ]);
            }

            if($fraudSetting['forward_admin_status'] == '1'){
                $forwardAdmin = 1;

                $detailTransaction = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->whereRaw('Date(transaction_date) = "'.date('Y-m-d', strtotime($dateTime)).'"')
                    ->where('id_user',$user['id'])
                    ->orderBy('transactions.created_at','desc')
                    ->select('transactions.*')
                    ->with(['outlet_city','user'])->get()->toArray();

                $stringTransactionDay = '';

                if(count($detailTransaction) > 0){
                    $areaOutlet = $detailTransaction[0]['outlet_city']['city_name'];
                    $stringTransactionDay .= '<table id="table-fraud-list">';
                    $stringTransactionDay .= '<tr>';
                    $stringTransactionDay .= '<td>Status Fraud</td>';
                    $stringTransactionDay .= '<td>Receipt Number</td>';
                    $stringTransactionDay .= '<td>Nama</td>';
                    $stringTransactionDay .= '<td>Nomor</td>';
                    $stringTransactionDay .= '<td>Outlet</td>';
                    $stringTransactionDay .= '<td>Tanggal Transaksi</td>';
                    $stringTransactionDay .= '<tr>';
                    foreach ($detailTransaction as $val){
                        if($val['fraud_flag'] != null){
                            if($val['fraud_flag'] == 'transaction day'){
                                $status = '<span style="color: red">Fraud <strong>Harian</strong></span>';
                            }else{
                                $status = '<span style="color: red">Fraud <strong>Mingguan</strong></span>';
                            }
                        }else{
                            $status = '<span style="color: green">Tidak kena Fraud</span>';
                        }
                        $stringTransactionDay .= '<tr>';
                        $stringTransactionDay .= '<td>'.$status.'</td>';
                        $stringTransactionDay .= '<td>'.$val['transaction_receipt_number'].'</td>';
                        $stringTransactionDay .= '<td>'.$val['user']['name'].'</td>';
                        $stringTransactionDay .= '<td>'.$val['user']['phone'].'</td>';
                        $stringTransactionDay .= '<td>'.$val['outlet_city']['outlet_name'].'</td>';
                        $stringTransactionDay .= '<td>'.date('d F Y H:i',strtotime($val['transaction_date'])).'</td>';
                        $stringTransactionDay .= '<tr>';
                    }
                    $stringTransactionDay .= '</table>';
                }
            }

            $timeperiod = $fraudSetting['auto_suspend_time_period'] - 1;
            $getLogWithTimePeriod = FraudDetectionLogTransactionDay::whereRaw("DATE(fraud_detection_date) BETWEEN '".date('Y-m-d',strtotime('-'.$timeperiod.' days',strtotime($dateTime)))."' AND '".date('Y-m-d', strtotime($dateTime))."'")
                ->where('id_user', $user['id'])
                ->where('status', 'Active')->get();
            $countLog = count($getLogWithTimePeriod);

            if($countLog > (int)$fraudSetting['auto_suspend_value']){

                if($fraudSetting['auto_suspend_status'] == '1'){
                    $autoSuspend = 1;
                }
            }
        }elseif(strpos($fraudSetting['parameter'], 'transactions in 1 week') !== false){
            $WeekNumber = date('W', strtotime($dateTime));
            $year = date('Y', strtotime($dateTime));
            $getFraudLogWeek = FraudDetectionLogTransactionWeek::where('fraud_detection_week',$WeekNumber)
                ->where('fraud_detection_year',$year)
                ->where('id_user', $user['id'])
                ->where('status', 'Active')->first();

            if($getFraudLogWeek){
                FraudDetectionLogTransactionWeek::where('id_user',$user['id'])->where('id_fraud_detection_log_transaction_week',$getFraudLogWeek['id_fraud_detection_log_transaction_week'])->update([
                    'count_transaction_week' => $countTrxWeek,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            }else{
                FraudDetectionLogTransactionWeek::create([
                    'id_user' => $user['id'],
                    'fraud_detection_year' => $year,
                    'fraud_detection_week' => $WeekNumber,
                    'count_transaction_week' => $countTrxWeek,
                    'fraud_setting_parameter_detail' => $fraudSetting['parameter_detail'],
                    'fraud_setting_forward_admin_status' => $fraudSetting['forward_admin_status'],
                    'fraud_setting_auto_suspend_status' => $fraudSetting['auto_suspend_status'],
                    'fraud_setting_auto_suspend_value' => $fraudSetting['auto_suspend_value'],
                    'fraud_setting_auto_suspend_time_period' => $fraudSetting['suspend_time_period']
                ]);
            }

            if($fraudSetting['forward_admin_status'] == '1'){
                $forwardAdmin = 1;

                $year = $year;
                $week = $WeekNumber;
                $dto = new DateTime();
                $dto->setISODate($year, $week);
                $start = $dto->format('Y-m-d');
                $dto->modify('+6 days');
                $end = $dto->format('Y-m-d');
                $detailTransaction = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->where('id_user',$user['id'])
                    ->whereRaw('Date(transaction_date) BETWEEN "'.$start.'" AND "'.$end.'"')
                    ->orderBy('transactions.created_at','desc')
                    ->select('transactions.*')
                    ->with(['outlet_city','user'])->get();

                $stringTransactionWeek = '';

                if(count($detailTransaction) > 0){
                    $areaOutlet = $detailTransaction[0]['outlet_city']['city_name'];
                    $stringTransactionWeek .= '<table id="table-fraud-list">';
                    $stringTransactionWeek .= '<tr>';
                    $stringTransactionWeek .= '<td>Status Fraud</td>';
                    $stringTransactionWeek .= '<td>Receipt Number</td>';
                    $stringTransactionWeek .= '<td>Nama</td>';
                    $stringTransactionWeek .= '<td>Nomor</td>';
                    $stringTransactionWeek .= '<td>Outlet</td>';
                    $stringTransactionWeek .= '<td>Tanggal Transaksi</td>';
                    $stringTransactionWeek .= '<tr>';
                    foreach ($detailTransaction as $val){
                        if($val['fraud_flag'] != null){
                            if($val['fraud_flag'] == 'transaction day'){
                                $status = '<span style="color: red">Fraud <strong>Harian</strong></span>';
                            }else{
                                $status = '<span style="color: red">Fraud <strong>Mingguan</strong></span>';
                            }
                        }else{
                            $status = '<span style="color: green">Tidak kena Fraud</span>';
                        }
                        $stringTransactionWeek .= '<tr>';
                        $stringTransactionWeek .= '<td>'.$status.'</td>';
                        $stringTransactionWeek .= '<td>'.$val['transaction_receipt_number'].'</td>';
                        $stringTransactionWeek .= '<td>'.$val['user']['name'].'</td>';
                        $stringTransactionWeek .= '<td>'.$val['user']['phone'].'</td>';
                        $stringTransactionWeek .= '<td>'.$val['outlet']['outlet_name'].'</td>';
                        $stringTransactionWeek .= '<td>'.date('d F Y H:i',strtotime($val['transaction_date'])).'</td>';
                        $stringTransactionWeek .= '<tr>';
                    }
                    $stringTransactionWeek .= '</table>';
                }
            }

            $totalWeekPeriod = $fraudSetting['auto_suspend_time_period']/7;
            if((int)$totalWeekPeriod == 0){
                $weekStart = $WeekNumber - 1;
            }else{
                $weekStart = $WeekNumber - $totalWeekPeriod;
            }

            $getLogWithTimePeriod = FraudDetectionLogTransactionWeek::whereRaw("fraud_detection_week BETWEEN ".(int)$weekStart.' AND '.$WeekNumber)
                ->where('fraud_detection_year',$year)
                ->where('id_user', $user['id'])
                ->where('status', 'Active')->get();
            $countLog = count($getLogWithTimePeriod);
            if($countLog > (int)$fraudSetting['auto_suspend_value']){
                if($fraudSetting['auto_suspend_status'] == '1'){
                    $autoSuspend = 1;
                }
            }
        }

        if($autoSuspend == 1){
            if($fraudSetting['auto_suspend_status'] == '1'){
                $getAllUser = UsersDeviceLogin::where('device_id',$deviceUser['device_id'])
                    ->orderBy('last_login','desc')->get()->pluck('id_user');
                if($fraudSetting['auto_suspend_value'] == 'all_account'){
                    $delToken = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->whereIn('oauth_access_tokens.user_id', $getAllUser)->where('oauth_access_token_providers.provider', 'users')->delete();
                    $updateUser = User::whereIn('id',$getAllUser)->whereRaw('level != "Super Admin"')->update(['is_suspended' => 1]);
                }elseif($fraudSetting['auto_suspend_value'] == 'last_account'){
                    $delToken = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $getAllUser[0]['id_user'])->where('oauth_access_token_providers.provider', 'users')->delete();
                    $updateUser = User::where('id',$getAllUser[0]['id_user'])->whereRaw('level != "Super Admin"')->update(['is_suspended' => 1]);
                }else{
                    $delToken = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $user['id'])->where('oauth_access_token_providers.provider', 'users')->delete();
                    $updateUser = User::where('id',$user['id'])->whereRaw('level != "Super Admin"')->update(['is_suspended' => 1]);
                }
            }
        }

        if($forwardAdmin == 1){
            if($fraudSetting['email_toogle'] == '1'){
                $recipient_email = explode(',', str_replace(' ', ',', str_replace(';', ',', $fraudSetting['email_recipient'])));
                foreach($recipient_email as $key => $recipient){
                    if($recipient != ' ' && $recipient != ""){
                        $to		 = $recipient;
                        $subject = app($this->autocrm)->TextReplace($fraudSetting['email_subject'], $user['phone'], ['transaction_count_day' => (string)$countTrxDay, 'transaction_count_week' => (string)$countTrxWeek, 'device_id' => $deviceUser['device_id'], 'device_type' => $deviceUser['device_type'] , 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trxId, 'area_outlet' => $areaOutlet]);
                        $content = app($this->autocrm)->TextReplace($fraudSetting['email_content'], $user['phone'], ['transaction_count_day' => (string)$countTrxDay, 'transaction_count_week' => (string)$countTrxWeek, 'device_id' => $deviceUser['device_id'], 'device_type' => $deviceUser['device_type'] , 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trxId, 'area_outlet' => $areaOutlet]);

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

                        try{
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
                        }catch(\Exception $e){

                        }
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

                        $content = app($this->autocrm)->TextReplace($fraudSetting['sms_content'], $user['phone'], ['transaction_count_day' => (string)$countTrxDay, 'transaction_count_week' => (string)$countTrxWeek, 'device_id' => $deviceUser['device_id'], 'device_type' => $deviceUser['device_type'] , 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trxId, 'area_outlet' => $areaOutlet]);

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
                                $content = $this->TextReplace($fraudSetting['whatsapp_content'], $user['phone'], ['transaction_count_day' => $countTrxDay, 'transaction_count_week' => $countTrxWeek, 'device_id' => $deviceUser['device_id'], 'device_type' => $deviceUser['device_type'], 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trxId, 'area_outlet' => $areaOutlet]);

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
        }

        if($autoSuspend && $delToken){
            return true;
        }else{
            return false;
        }
    }

    function updateStatus(Request $request) {
        $post = $request->json()->all();
        if(isset($post['id_fraud_setting']) && isset($post['fraud_settings_status'])){
            $save = FraudSetting::where('id_fraud_setting', $post['id_fraud_setting'])->update($post);

            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail','messages' => ['incomplete data']]);
        }

    }

    function logFraud(Request $request, $type){
        $post = $request->json()->all();
        $date_start = date('Y-m-d');
        $date_end = date('Y-m-d');

        if(isset($post['date_start'])){
            $date_start = date('Y-m-d', strtotime($post['date_start']));
        }

        if(isset($post['date_end'])){
            $date_end = date('Y-m-d', strtotime($post['date_end']));
        }

        if($type == 'device'){
            $table = 'fraud_detection_log_device';
            $id = 'id_fraud_detection_log_device';
            $queryList = FraudDetectionLogDevice::join('users', 'users.id', '=', 'fraud_detection_log_device.id_user')
                ->join('users_device_login', 'users_device_login.device_id', '=', 'fraud_detection_log_device.device_id')
                ->whereRaw("DATE(fraud_detection_log_device.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                ->where('users_device_login.status','Active')
                ->orderBy('fraud_detection_log_device.created_at','desc')->groupBy('fraud_detection_log_device.device_id')->with(['usersFraud','usersNoFraud','allUsersdevice']);
        }elseif($type == 'transaction-day'){
            $table = 'fraud_detection_log_transaction_day';
            $id = 'id_fraud_detection_log_transaction_day';

            if(isset($post['export']) && $post['export'] == 1){
                $queryList = FraudDetectionLogTransactionDay::
                join('users', 'users.id', '=', 'fraud_detection_log_transaction_day.id_user')
                    ->join('transactions', 'transactions.id_user', '=', 'fraud_detection_log_transaction_day.id_user')
                    ->join('outlets','outlets.id_outlet', '=', 'transactions.id_outlet')
                    ->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->whereRaw('DATE(fraud_detection_log_transaction_day.fraud_detection_date) = DATE(transactions.transaction_date)')
                    ->whereRaw("DATE(fraud_detection_log_transaction_day.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_day.status','Active')
                    ->orderBy('fraud_detection_log_transaction_day.created_at','desc')->select('users.name', 'users.phone', 'users.email', 'outlets.outlet_name', 'fraud_detection_log_transaction_day.*', 'transactions.*');
            }else{
                $queryList = FraudDetectionLogTransactionDay::join('users', 'users.id', '=', 'fraud_detection_log_transaction_day.id_user')
                    ->join('transactions', 'transactions.id_user', '=', 'fraud_detection_log_transaction_day.id_user')
                    ->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->whereRaw("DATE(fraud_detection_log_transaction_day.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_day.status','Active')
                    ->select('users.name', 'users.phone','fraud_detection_log_transaction_day.*', 'transactions.*')
                    ->orderBy('fraud_detection_log_transaction_day.created_at','desc')->groupBy('fraud_detection_log_transaction_day.id_fraud_detection_log_transaction_day');
            }

        }elseif($type == 'transaction-week'){
            $table = 'fraud_detection_log_transaction_week';
            $id = 'id_fraud_detection_log_transaction_week';
            if(isset($post['export']) && $post['export'] == 1){
                $queryList = FraudDetectionLogTransactionWeek::join('users', 'users.id', '=', 'fraud_detection_log_transaction_week.id_user')
                    ->join('transactions', 'transactions.id_user', '=', 'fraud_detection_log_transaction_week.id_user')
                    ->join('outlets','outlets.id_outlet', '=', 'transactions.id_outlet')
                    ->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->whereRaw("DATE(fraud_detection_log_transaction_week.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_week.status','Active')
                    ->orderBy('fraud_detection_log_transaction_week.created_at','desc')->select('users.name', 'users.phone', 'users.email', 'outlets.outlet_name','fraud_detection_log_transaction_week.*', 'transactions.*');

            }else{
                $queryList = FraudDetectionLogTransactionWeek::join('users', 'users.id', '=', 'fraud_detection_log_transaction_week.id_user')
                    ->join('transactions', 'transactions.id_user', '=', 'fraud_detection_log_transaction_week.id_user')
                    ->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->whereRaw("DATE(fraud_detection_log_transaction_week.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_week.status','Active')
                    ->select('users.name', 'users.phone','fraud_detection_log_transaction_week.*', 'transactions.*')
                    ->orderBy('fraud_detection_log_transaction_week.created_at','desc')->groupBy('fraud_detection_log_transaction_week.id_fraud_detection_log_transaction_week');
            }



        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $conditions = $post['conditions'];
            foreach ($conditions as $key => $cond) {
                foreach($cond as $index => $condition){
                    if(isset($condition['subject'])){
                        if($condition['operator'] != '='){
                            $conditionParameter = $condition['operator'];
                        }

                        if($cond['rule'] == 'and'){
                            if($condition['subject'] == 'name' || $condition['subject'] == 'phone'){
                                $var = "users.".$condition['subject'];

                                if($condition['operator'] == 'like')
                                    $queryList = $queryList->where($var,'like','%'.$condition['parameter'].'%');
                                else
                                    $queryList = $queryList->where($var,'=',$condition['parameter']);
                            }

                            if($condition['subject'] == 'device'){

                                if($conditionParameter == 'None'){
                                    $queryList = $queryList->whereNull('users.android_device')->whereNull('users.ios_device');
                                }

                                if($conditionParameter == 'Android'){
                                    $queryList = $queryList->whereNotNull('users.android_device')->whereNull('users.ios_device');
                                }

                                if($conditionParameter == 'IOS'){
                                    $queryList = $queryList->notNull('users.android_device')->whereNotNull('users.ios_device');
                                }

                                if($conditionParameter == 'Both'){
                                    $queryList = $queryList->whereNotNull('users.android_device')->whereNotNull('users.ios_device');
                                }

                            }

                            if($condition['subject'] == 'outlet'){
                                $queryList = $queryList->where('transactions.id_outlet','=',$conditionParameter);
                            }

                            if($condition['subject'] == 'number_of_breaking'){
                                $queryList = $queryList->havingRaw('COUNT(distinct '.$table.'.'.$id.') '.$condition['operator'].$condition['parameter']);
                            }
                        }else{
                            if($condition['subject'] == 'name' || $condition['subject'] == 'phone'){
                                $var = "users.".$condition['subject'];

                                if($condition['operator'] == 'like')
                                    $queryList = $queryList->orWhere($var,'like','%'.$condition['parameter'].'%');
                                else
                                    $queryList = $queryList->orWhere($var,'=',$condition['parameter']);
                            }

                            if($condition['subject'] == 'device'){

                                if($conditionParameter == 'None'){
                                    $queryList = $queryList->orWhereNull('users.android_device')->orWhereNull('users.ios_device');
                                }

                                if($conditionParameter == 'Android'){
                                    $queryList = $queryList->orwhereNotNull('users.android_device')->orWhereNull('users.ios_device');
                                }

                                if($conditionParameter == 'IOS'){
                                    $queryList = $queryList->orwhereNull('users.android_device')->orwhereNotNull('users.ios_device');
                                }

                                if($conditionParameter == 'Both'){
                                    $queryList = $queryList->orwhereNotNull('users.android_device')->orwhereNotNull('users.ios_device');
                                }
                            }

                            if($condition['subject'] == 'outlet'){
                                $queryList = $queryList->orWhere('transactions.id_outlet','=',$conditionParameter);
                            }

                            if($condition['subject'] == 'number_of_breaking'){
                                $queryList = $queryList->orhavingRaw('COUNT(distinct '.$table.'.'.$id.') '.$condition['operator'].$condition['parameter']);
                            }
                        }
                    }
                }
            }
        }

        $list = $queryList->get()->toArray();

        return response()->json([
            'status' => 'success',
            'result' => $list
        ]);
    }

    function detailFraudDevice(Request $request){
        $post = $request->json()->all();

        if(isset($post['device_id'])){
            $detail = UsersDeviceLogin::join('users', 'users.id', '=', 'users_device_login.id_user')
                ->leftJoin('fraud_detection_log_device', 'fraud_detection_log_device.device_id', '=', 'users_device_login.device_id')
                ->select('users.*', 'users_device_login.*', 'fraud_detection_log_device.fraud_setting_parameter_detail', 'fraud_detection_log_device.fraud_setting_forward_admin_status',
                    'fraud_detection_log_device.fraud_setting_auto_suspend_status', 'fraud_detection_log_device.fraud_setting_auto_suspend_value',
                    'fraud_detection_log_device.fraud_setting_auto_suspend_time_period','fraud_detection_log_device.id_fraud_detection_log_device', 'fraud_detection_log_device.device_type')
                ->whereRaw('fraud_detection_log_device.device_id = "'.$post['device_id'].'"')->orderBy('users_device_login.created_at','asc')
                ->groupBy('users.id')
                ->get()->toArray();
            $detail_fraud = FraudDetectionLogDevice::join('users', 'users.id', '=', 'fraud_detection_log_device.id_user')
                ->select('users.name','users.phone','fraud_detection_log_device.*')
                ->whereRaw('fraud_detection_log_device.device_id = "'.$post['device_id'].'"')
                ->where('fraud_detection_log_device.status', 'Active')
                ->get()->toArray();
        }

        return response()->json([
            'status' => 'success',
            'result' => [
                'detail_user' => $detail,
                'detail_fraud' => $detail_fraud
            ]
        ]);
    }

    function detailFraudTransactionDay(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_fraud_detection_log_transaction_day'])){
            $detailLog = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day',$post['id_fraud_detection_log_transaction_day'])->with('user')->first();
        }

        $detailTransaction = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->where('transactions.transaction_payment_status','Completed')
            ->whereNull('transaction_pickups.reject_at')
            ->whereRaw('Date(transaction_date) = "'.date('Y-m-d', strtotime($detailLog['fraud_detection_date'])).'"')
            ->where('id_user',$detailLog['id_user'])
            ->select('transactions.*')
            ->with('outlet')->get();
        $detailUser = User::where('id',$detailLog['id_user'])->first();
        return response()->json([
            'status' => 'success',
            'result' => [
                'detail_log' => $detailLog,
                'detail_transaction' => $detailTransaction,
                'detail_user' => $detailUser
            ]
        ]);
    }

    function detailFraudTransactionWeek(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_fraud_detection_log_transaction_week'])){
            $detailLog = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week',$post['id_fraud_detection_log_transaction_week'])->with('user')->first();
        }
        $year = $detailLog['fraud_detection_year'];
        $week = $detailLog['fraud_detection_week'];
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $start = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end = $dto->format('Y-m-d');
        $detailTransaction = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->where('transactions.transaction_payment_status','Completed')
            ->whereNull('transaction_pickups.reject_at')
            ->where('id_user',$detailLog['id_user'])
            ->whereRaw('Date(transaction_date) BETWEEN "'.$start.'" AND "'.$end.'"')
            ->select('transactions.*')
            ->with('outlet')->get();
        $detailUser = User::where('id',$detailLog['id_user'])->first();
        return response()->json([
            'status' => 'success',
            'result' => [
                'detail_log' => $detailLog,
                'detail_transaction' => $detailTransaction,
                'detail_user' => $detailUser
            ]
        ]);
    }

    function updateLog(Request $request){
        $post = $request->json()->all();

        if(isset($post['type']) && $post['type'] == 'device'){
            unset($post['type']);
            $update = FraudDetectionLogDevice::where('device_id', $post['device_id'])->where('id_user',$post['id_user'])
                ->update($post);
        }elseif(isset($post['type']) && $post['type'] == 'transaction-day'){
            unset($post['type']);
            $update = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day', $post['id_fraud_detection_log_transaction_day'])
                ->update($post);
        }elseif(isset($post['type']) && $post['type'] == 'transaction-week'){
            unset($post['type']);
            $update = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week', $post['id_fraud_detection_log_transaction_week'])
                ->update($post);
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    function detailLogUser(Request $request){
        $post = $request->json()->all();
        if(isset($post['phone'])){
            $user = User::where('phone',$post['phone'])->first();
            $logDevice = User::join('fraud_detection_log_device', 'users.id', '=', 'fraud_detection_log_device.id_user')
                ->where('users.phone',$post['phone'])->where('fraud_detection_log_device.status','Active')
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_device.*')->get()->toArray();
            $logTransactionDay = User::join('fraud_detection_log_transaction_day', 'users.id', '=', 'fraud_detection_log_transaction_day.id_user')
                ->where('users.phone',$post['phone'])->where('fraud_detection_log_transaction_day.status','Active')
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_transaction_day.*')->get()->toArray();
            $logTransactionWeek = User::join('fraud_detection_log_transaction_week', 'users.id', '=', 'fraud_detection_log_transaction_week.id_user')
                ->where('users.phone',$post['phone'])->where('fraud_detection_log_transaction_week.status','Active')
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_transaction_week.*')->get()->toArray();

            $result = [
                'status' => 'success',
                'result' => [
                    'detail_user' => $user,
                    'list_device' => $logDevice,
                    'list_trans_day' => $logTransactionDay,
                    'list_trans_week' => $logTransactionWeek
                ]
            ];
        }else{
            $result = [
                'status' => 'fail',
                'result' => []
            ];
        }

        return response()->json($result);
    }

    function listUserFraud(Request $request){
        $post = $request->json()->all();
        $date_start = date('Y-m-d');
        $date_end = date('Y-m-d');

        if(isset($post['date_start'])){
            $date_start = date('Y-m-d', strtotime($post['date_start']));
        }

        if(isset($post['date_end'])){
            $date_end = date('Y-m-d', strtotime($post['date_end']));
        }

        $data = [
            'date_start' => $date_start,
            'date_end' => $date_end
        ];

        $queryList = User::where(function ($query) use ($data) {
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_device WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_transaction_day WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_transaction_week WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
        });

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $conditions = $post['conditions'];
            foreach ($conditions as $key => $cond) {
                foreach($cond as $index => $condition){
                    if(isset($condition['subject'])){

                        if($cond['rule'] == 'and'){
                            if($condition['subject'] == 'name' || $condition['subject'] == 'phone'){
                                $var = "users.".$condition['subject'];

                                if($condition['operator'] == 'like')
                                    $queryList = $queryList->where($var,'like','%'.$condition['parameter'].'%');
                                else
                                    $queryList = $queryList->where($var,'=',$condition['parameter']);
                            }

                        }else{
                            if($condition['subject'] == 'name' || $condition['subject'] == 'phone'){
                                $var = "users.".$condition['subject'];

                                if($condition['operator'] == 'like')
                                    $queryList = $queryList->orWhere($var,'like','%'.$condition['parameter'].'%');
                                else
                                    $queryList = $queryList->orWhere($var,'=',$condition['parameter']);
                            }
                        }
                    }
                }
            }
        }
        $total = $queryList->count();
        $list = $queryList->skip($post['skip'])->take($post['take'])->get()->toArray();

        return response()->json([
            'status' => 'success',
            'result' => $list,
            'total' => $total
        ]);
    }

    function updateDeviceLoginStatus(Request $request) {
        $post = $request->json()->all();
        if(isset($post['device_id']) && isset($post['id_user'])){
            $save = UsersDeviceLogin::where('device_id', $post['device_id'])->where('id_user', $post['id_user'])->update($post);

            if($save){
                if($post['status'] == 'Inactive'){
                    $delToken = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $post['id_user'])->where('oauth_access_token_providers.provider', 'users')->delete();
                }
            }
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail','messages' => ['incomplete data']]);
        }

    }
}
