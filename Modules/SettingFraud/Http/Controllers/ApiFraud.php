<?php

namespace Modules\SettingFraud\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\DailyTransactions;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\FraudBetweenTransaction;
use Modules\SettingFraud\Entities\FraudDetectionLogCheckPromoCode;
use Modules\SettingFraud\Entities\FraudDetectionLogReferral;
use Modules\SettingFraud\Entities\FraudDetectionLogReferralUsers;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionInBetween;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionPoint;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Http\Models\UserDevice;
use App\Http\Models\UsersDeviceLogin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\SettingFraud\Entities\FraudSetting;
use Modules\SettingFraud\Entities\FraudDetectionLog;
use Modules\SettingFraud\Entities\FraudDetectionLogDevice;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionDay;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionWeek;
use App\Http\Models\Setting;
use Illuminate\Support\Facades\DB;

use App\Lib\MyHelper;
use App\Lib\classMaskingJson;
use App\Lib\apiwha;
use DateTime;
use Mail;
use Modules\SettingFraud\Entities\LogCheckPromoCode;
use function GuzzleHttp\Psr7\str;
use File;
use Storage;

class ApiFraud extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		$this->rajasms = new classMaskingJson();
		$this->apiwha = new apiwha();
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

    function checkFraudTrxOnline($user, $trx){
        $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 day%')->where('fraud_settings_status','Active')->first();
        $fraudTrxWeek = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 week%')->where('fraud_settings_status','Active')->first();

        $autoSuspend = 0;
        $forwardAdmin = 0;
        $countUser = 0;
        $stringUserList = '';
        $stringTransactionDay = '';
        $stringTransactionWeek = '';
        $areaOutlet = '';
        $dateTime = $trx['transaction_date'];

        //========= This process to check if user have fraud ============//
        $geCountTrxDay = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->where('transactions.id_user', $trx['id_user'])
            ->whereRaw('DATE(transactions.transaction_date) = "' . date('Y-m-d', strtotime($trx['transaction_date'])) . '"')
            ->where('transactions.transaction_payment_status', 'Completed')
            ->whereNull('transaction_pickups.reject_at')
            ->count();

        $currentWeekNumber = date('W', strtotime($trx['transaction_date']));
        $currentYear = date('Y', strtotime($trx['transaction_date']));
        $dto = new DateTime();
        $dto->setISODate($currentYear, $currentWeekNumber);
        $start = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end = $dto->format('Y-m-d');

        $geCountTrxWeek = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->where('id_user', $trx['id_user'])
            ->where('transactions.transaction_payment_status', 'Completed')
            ->whereNull('transaction_pickups.reject_at')
            ->whereRaw('Date(transactions.transaction_date) BETWEEN "' . $start . '" AND "' . $end . '"')
            ->count();

        $countTrxDay = $geCountTrxDay + 1;
        $countTrxWeek = $geCountTrxWeek + 1;
        //================================ End ================================//

       if($fraudTrxDay){

            if($countTrxDay > (int)$fraudTrxDay['parameter_detail']){
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
                        'fraud_setting_parameter_detail' => $fraudTrxDay['parameter_detail'],
                        'fraud_setting_forward_admin_status' => $fraudTrxDay['forward_admin_status'],
                        'fraud_setting_auto_suspend_status' => $fraudTrxDay['auto_suspend_status'],
                        'fraud_setting_auto_suspend_value' => $fraudTrxDay['auto_suspend_value'],
                        'fraud_setting_auto_suspend_time_period' => $fraudTrxDay['suspend_time_period']
                    ]);
                }

                if($fraudTrxDay['forward_admin_status'] == '1'){
                    $forwardAdmin = 1;

                    $detailTransaction = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                        ->where('transactions.transaction_payment_status','Completed')
                        ->whereNull('transaction_pickups.reject_at')
                        ->whereRaw('Date(transaction_date) = "'.date('Y-m-d', strtotime($dateTime)).'"')
                        ->where('id_user',$user['id'])
                        ->orderBy('transactions.created_at','desc')
                        ->select('transactions.*',
                            DB::raw('(CASE
                                WHEN (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" ) 
                                is NULL THEN NULL
                                ELSE (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" )
                            END) as balance'))
                        ->with(['outlet_city','user'])->get()->toArray();

                    $stringTransactionDay = '';

                    if(count($detailTransaction) > 0){
                        $areaOutlet = $detailTransaction[0]['outlet_city']['city_name'];
                        $stringTransactionDay .= '<table id="table-fraud-list">';
                        $stringTransactionDay .= '<tr>';
                        $stringTransactionDay .= '<td>Status Fraud</td>';
                        $stringTransactionDay .= '<td>Receipt Number</td>';
                        $stringTransactionDay .= '<td>Outlet</td>';
                        $stringTransactionDay .= '<td>Transaction Date</td>';
                        $stringTransactionDay .= '<td>Transaction Time</td>';
                        $stringTransactionDay .= '<td>Point</td>';
                        $stringTransactionDay .= '<td>Nominal</td>';
                        $stringTransactionDay .= '<tr>';
                        foreach ($detailTransaction as $val){
                            if($val['fraud_flag'] != null){
                                if($val['fraud_flag'] == 'transaction day'){
                                    $status = '<span style="color: red">Fraud <strong>Day</strong></span>';
                                }else{
                                    $status = '<span style="color: red">Fraud <strong>Week</strong></span>';
                                }
                            }else{
                                $status = '<span style="color: green">No Fraud</span>';
                            }
                            $stringTransactionDay .= '<tr>';
                            $stringTransactionDay .= '<td>'.$status.'</td>';
                            $stringTransactionDay .= '<td>'.$val['transaction_receipt_number'].'</td>';
                            $stringTransactionDay .= '<td>'.$val['outlet_city']['outlet_name'].'</td>';
                            $stringTransactionDay .= '<td>'.date('d F Y',strtotime($val['transaction_date'])).'</td>';
                            $stringTransactionDay .= '<td>'.date('H:i',strtotime($val['transaction_date'])).'</td>';
                            $stringTransactionDay .= '<td>'.($val['balance'] != NULL ? $val['balance'] : '0').'</td>';
                            $stringTransactionDay .= '<td>'.number_format($val['transaction_grandtotal']).'</td>';
                            $stringTransactionDay .= '<tr>';
                        }
                        $stringTransactionDay .= '</table>';
                    }
                }

                $timeperiod = $fraudTrxDay['auto_suspend_time_period'] - 1;
                $getLogWithTimePeriod = FraudDetectionLogTransactionDay::whereRaw("DATE(fraud_detection_date) BETWEEN '".date('Y-m-d',strtotime('-'.$timeperiod.' days',strtotime($dateTime)))."' AND '".date('Y-m-d', strtotime($dateTime))."'")
                    ->where('id_user', $user['id'])
                    ->where('status', 'Active')->get();
                $countLog = count($getLogWithTimePeriod);

                if($countLog > (int)$fraudTrxDay['auto_suspend_value']){

                    if($fraudTrxDay['auto_suspend_status'] == '1'){
                        $autoSuspend = 1;
                    }
                }
            }

           $contentEmail = ['transaction_count_day' => (string)$countTrxDay, 'transaction_count_week' => (string)$countTrxWeek, 'device_id' => [], 'device_type' => [] , 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trx['transaction_receipt_number'], 'area_outlet' => $areaOutlet, 'point' => 0];
           $sendFraud = $this->SendFraudDetection($fraudTrxDay['id_fraud_setting'], $user, null, null, null, $autoSuspend, $forwardAdmin, $contentEmail);
       }

       if($fraudTrxWeek) {
           if ($countTrxWeek > (int)$fraudTrxWeek['parameter_detail']) {
               $WeekNumber = date('W', strtotime($dateTime));
               $year = date('Y', strtotime($dateTime));
               $getFraudLogWeek = FraudDetectionLogTransactionWeek::where('fraud_detection_week', $WeekNumber)
                   ->where('fraud_detection_year', $year)
                   ->where('id_user', $user['id'])
                   ->where('status', 'Active')->first();

               if ($getFraudLogWeek) {
                   FraudDetectionLogTransactionWeek::where('id_user', $user['id'])->where('id_fraud_detection_log_transaction_week', $getFraudLogWeek['id_fraud_detection_log_transaction_week'])->update([
                       'count_transaction_week' => $countTrxWeek,
                       'updated_at' => date('Y-m-d H:i:s')
                   ]);

               } else {
                   FraudDetectionLogTransactionWeek::create([
                       'id_user' => $user['id'],
                       'fraud_detection_year' => $year,
                       'fraud_detection_week' => $WeekNumber,
                       'count_transaction_week' => $countTrxWeek,
                       'fraud_setting_parameter_detail' => $fraudTrxWeek['parameter_detail'],
                       'fraud_setting_forward_admin_status' => $fraudTrxWeek['forward_admin_status'],
                       'fraud_setting_auto_suspend_status' => $fraudTrxWeek['auto_suspend_status'],
                       'fraud_setting_auto_suspend_value' => $fraudTrxWeek['auto_suspend_value'],
                       'fraud_setting_auto_suspend_time_period' => $fraudTrxWeek['suspend_time_period']
                   ]);
               }

               if ($fraudTrxWeek['forward_admin_status'] == '1') {
                   $forwardAdmin = 1;

                   $year = $year;
                   $week = $WeekNumber;
                   $dto = new DateTime();
                   $dto->setISODate($year, $week);
                   $start = $dto->format('Y-m-d');
                   $dto->modify('+6 days');
                   $end = $dto->format('Y-m-d');
                   $detailTransaction = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->whereNull('transaction_pickups.reject_at')
                       ->where('id_user', $user['id'])
                       ->whereRaw('Date(transaction_date) BETWEEN "' . $start . '" AND "' . $end . '"')
                       ->orderBy('transactions.created_at', 'desc')
                       ->select('transactions.*',
                           DB::raw('(CASE
                                WHEN (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" ) 
                                is NULL THEN NULL
                                ELSE (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" )
                            END) as balance'))
                       ->with(['outlet_city', 'user'])->get();

                   $stringTransactionWeek = '';

                   if (count($detailTransaction) > 0) {
                       $areaOutlet = $detailTransaction[0]['outlet_city']['city_name'];
                       $stringTransactionWeek .= '<table id="table-fraud-list">';
                       $stringTransactionWeek .= '<tr>';
                       $stringTransactionWeek .= '<td>Status Fraud</td>';
                       $stringTransactionWeek .= '<td>Receipt Number</td>';
                       $stringTransactionWeek .= '<td>Outlet</td>';
                       $stringTransactionWeek .= '<td>Transaction Date</td>';
                       $stringTransactionWeek .= '<td>Transaction Time</td>';
                       $stringTransactionWeek .= '<td>Point</td>';
                       $stringTransactionWeek .= '<td>Nominal</td>';
                       $stringTransactionWeek .= '<tr>';
                       foreach ($detailTransaction as $val) {
                           if ($val['fraud_flag'] != null) {
                               if ($val['fraud_flag'] == 'transaction day') {
                                   $status = '<span style="color: red">Fraud <strong>Day</strong></span>';
                               } else {
                                   $status = '<span style="color: red">Fraud <strong>Week</strong></span>';
                               }
                           } else {
                               $status = '<span style="color: green">No Fraud</span>';
                           }
                           $stringTransactionWeek .= '<tr>';
                           $stringTransactionWeek .= '<td>' . $status . '</td>';
                           $stringTransactionWeek .= '<td>' . $val['transaction_receipt_number'] . '</td>';
                           $stringTransactionWeek .= '<td>' . $val['outlet']['outlet_name'] . '</td>';
                           $stringTransactionWeek .= '<td>' . date('d F Y', strtotime($val['transaction_date'])) . '</td>';
                           $stringTransactionWeek .= '<td>' . date('H:i', strtotime($val['transaction_date'])) . '</td>';
                           $stringTransactionWeek .= '<td>' . ($val['balance'] != NULL ? $val['balance'] : '0') . '</td>';
                           $stringTransactionWeek .= '<td>' . number_format($val['transaction_grandtotal']) . '</td>';
                           $stringTransactionWeek .= '<tr>';
                       }
                       $stringTransactionWeek .= '</table>';
                   }
               }

               $totalWeekPeriod = $fraudTrxWeek['auto_suspend_time_period'] / 7;
               if ((int)$totalWeekPeriod == 0) {
                   $weekStart = $WeekNumber - 1;
               } else {
                   $weekStart = $WeekNumber - $totalWeekPeriod;
               }

               $getLogWithTimePeriod = FraudDetectionLogTransactionWeek::whereRaw("fraud_detection_week BETWEEN " . (int)$weekStart . ' AND ' . $WeekNumber)
                   ->where('fraud_detection_year', $year)
                   ->where('id_user', $user['id'])
                   ->where('status', 'Active')->get();
               $countLog = count($getLogWithTimePeriod);
               if ($countLog > (int)$fraudTrxWeek['auto_suspend_value']) {
                   if ($fraudTrxWeek['auto_suspend_status'] == '1') {
                       $autoSuspend = 1;
                   }
               }
           }

           $contentEmail = ['transaction_count_day' => (string)$countTrxDay, 'transaction_count_week' => (string)$countTrxWeek, 'device_id' => [], 'device_type' => [] , 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trx['transaction_receipt_number'], 'area_outlet' => $areaOutlet, 'point' => 0];
           $sendFraud = $this->SendFraudDetection($fraudTrxWeek['id_fraud_setting'], $user, null, null, null, $autoSuspend, $forwardAdmin, $contentEmail);
       }

        if(isset($sendFraud))
            return $sendFraud;
        else
            return false;
    }

    function checkFraud($fraudSetting, $user, $device = null, $countTrxDay, $countTrxWeek, $dateTime, $deleteToken, $trxId = null,
                        $currentBalance = null, $mostOutlet = null, $atOutlet = null){
        $autoSuspend = 0;
        $forwardAdmin = 0;
        $countUser = 0;
        $stringUserList = '';
        $stringTransactionDay = '';
        $stringTransactionWeek = '';
        $areaOutlet = '';

        if(strpos($fraudSetting['parameter'], 'device') !== false){

            $deviceCus = UsersDeviceLogin::where('device_id', '=' ,$device['device_id'])
                ->where('status','Active')
                ->orderBy('created_at','desc')
                ->groupBy('id_user')
                ->get()->toArray();

            if($deviceCus && $deviceCus[0]['id_user'] ==  $user['id'] && count($deviceCus) > (int)$fraudSetting['parameter_detail']){
                $checkLog = FraudDetectionLogDevice::where('id_user',$user['id'])->where('device_id',$device['device_id'])->first();

                if(!empty($checkLog)){
                    $dt = [
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $updateLog = FraudDetectionLogDevice::where('id_user',$user['id'])->where('device_id',$device['device_id'])->update($dt);
                    if(!$updateLog){
                        return false;
                    }
                }else{
                    $dt = [
                        'id_user' => $user['id'],
                        'device_id' => $device['device_id'],
                        'device_type' => $device['device_type'],
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

                $getLogUserDevice = FraudDetectionLogDevice::where('device_id',$device['device_id'])->where('status','Active')->get()->toArray();

                if($getLogUserDevice){
                    if($fraudSetting['auto_suspend_status'] == '1'){
                        $autoSuspend = 1;
                    }

                    if($fraudSetting['forward_admin_status'] == '1'){
                        $forwardAdmin = 1;
                        $list_user = UsersDeviceLogin::join('users','users.id','users_device_login.id_user')
                            ->where('users_device_login.device_id',$device['device_id'])
                            ->whereRaw('users_device_login.id_user != '.$user['id'])
                            ->where('users_device_login.status','Active')
                            ->orderBy('users_device_login.created_at','asc')
                            ->get()->toArray();
                        $countUser = count($list_user);

                        $stringUserList = '';

                        if(count($list_user) > 0){
                            $no = 0;
                            $stringUserList .= '<table id="table-fraud-list">';
                            $stringUserList .= '<tr>';
                            $stringUserList .= '<td>No</td>';
                            $stringUserList .= '<td>Name</td>';
                            $stringUserList .= '<td>Phone</td>';
                            $stringUserList .= '<td>Email</td>';
                            $stringUserList .= '<td>Last Login Date</td>';
                            $stringUserList .= '<td>Last Login Time</td>';
                            $stringUserList .= '<tr>';
                            foreach ($list_user as $val){
                                $no = $no + 1;
                                $stringUserList .= '<tr>';
                                $stringUserList .= '<td>'.$no.'</td>';
                                $stringUserList .= '<td>'.$val['name'].'</td>';
                                $stringUserList .= '<td>'.$val['phone'].'</td>';
                                $stringUserList .= '<td>'.$val['email'].'</td>';
                                $stringUserList .= '<td>'.date('d F Y',strtotime($val['last_login'])).'</td>';
                                $stringUserList .= '<td>'.date('H:i',strtotime($val['last_login'])).'</td>';
                                $stringUserList .= '<tr>';
                            }
                            $stringUserList .= '</table>';
                        }
                    }
                }
            }
        }elseif(strpos($fraudSetting['parameter'], 'transactions in 1 day') !== false){

            if($countTrxDay > (int)$fraudSetting['parameter_detail']){
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
                        ->select('transactions.*',
                            DB::raw('(CASE
                                WHEN (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" ) 
                                is NULL THEN NULL
                                ELSE (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" )
                            END) as balance'))
                        ->with(['outlet_city','user'])->get()->toArray();

                    $stringTransactionDay = '';

                    if(count($detailTransaction) > 0){
                        $areaOutlet = $detailTransaction[0]['outlet_city']['city_name'];
                        $stringTransactionDay .= '<table id="table-fraud-list">';
                        $stringTransactionDay .= '<tr>';
                        $stringTransactionDay .= '<td>Status Fraud</td>';
                        $stringTransactionDay .= '<td>Receipt Number</td>';
                        $stringTransactionDay .= '<td>Outlet</td>';
                        $stringTransactionDay .= '<td>Transaction Date</td>';
                        $stringTransactionDay .= '<td>Transaction Time</td>';
                        $stringTransactionDay .= '<td>Point</td>';
                        $stringTransactionDay .= '<td>Nominal</td>';
                        $stringTransactionDay .= '<tr>';
                        foreach ($detailTransaction as $val){
                            if($val['fraud_flag'] != null){
                                if($val['fraud_flag'] == 'transaction day'){
                                    $status = '<span style="color: red">Fraud <strong>Day</strong></span>';
                                }else{
                                    $status = '<span style="color: red">Fraud <strong>Week</strong></span>';
                                }
                            }else{
                                $status = '<span style="color: green">No Fraud</span>';
                            }
                            $stringTransactionDay .= '<tr>';
                            $stringTransactionDay .= '<td>'.$status.'</td>';
                            $stringTransactionDay .= '<td>'.$val['transaction_receipt_number'].'</td>';
                            $stringTransactionDay .= '<td>'.$val['outlet_city']['outlet_name'].'</td>';
                            $stringTransactionDay .= '<td>'.date('d F Y',strtotime($val['transaction_date'])).'</td>';
                            $stringTransactionDay .= '<td>'.date('H:i',strtotime($val['transaction_date'])).'</td>';
                            $stringTransactionDay .= '<td>'.($val['balance'] != NULL ? $val['balance'] : '0').'</td>';
                            $stringTransactionDay .= '<td>'.number_format($val['transaction_grandtotal']).'</td>';
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
            }
        }elseif(strpos($fraudSetting['parameter'], 'transactions in 1 week') !== false){
            if($countTrxWeek > (int)$fraudSetting['parameter_detail']){
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
                        ->select('transactions.*',
                            DB::raw('(CASE
                                WHEN (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" ) 
                                is NULL THEN NULL
                                ELSE (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" )
                            END) as balance'))
                        ->with(['outlet_city','user'])->get();

                    $stringTransactionWeek = '';

                    if(count($detailTransaction) > 0){
                        $areaOutlet = $detailTransaction[0]['outlet_city']['city_name'];
                        $stringTransactionWeek .= '<table id="table-fraud-list">';
                        $stringTransactionWeek .= '<tr>';
                        $stringTransactionWeek .= '<td>Status Fraud</td>';
                        $stringTransactionWeek .= '<td>Receipt Number</td>';
                        $stringTransactionWeek .= '<td>Outlet</td>';
                        $stringTransactionWeek .= '<td>Transaction Date</td>';
                        $stringTransactionWeek .= '<td>Transaction Time</td>';
                        $stringTransactionWeek .= '<td>Point</td>';
                        $stringTransactionWeek .= '<td>Nominal</td>';
                        $stringTransactionWeek .= '<tr>';
                        foreach ($detailTransaction as $val){
                            if($val['fraud_flag'] != null){
                                if($val['fraud_flag'] == 'transaction day'){
                                    $status = '<span style="color: red">Fraud <strong>Day</strong></span>';
                                }else{
                                    $status = '<span style="color: red">Fraud <strong>Week</strong></span>';
                                }
                            }else{
                                $status = '<span style="color: green">No Fraud</span>';
                            }
                            $stringTransactionWeek .= '<tr>';
                            $stringTransactionWeek .= '<td>'.$status.'</td>';
                            $stringTransactionWeek .= '<td>'.$val['transaction_receipt_number'].'</td>';
                            $stringTransactionWeek .= '<td>'.$val['outlet']['outlet_name'].'</td>';
                            $stringTransactionWeek .= '<td>'.date('d F Y',strtotime($val['transaction_date'])).'</td>';
                            $stringTransactionWeek .= '<td>'.date('H:i',strtotime($val['transaction_date'])).'</td>';
                            $stringTransactionWeek .= '<td>'.($val['balance'] != NULL ? $val['balance'] : '0').'</td>';
                            $stringTransactionWeek .= '<td>'.number_format($val['transaction_grandtotal']).'</td>';
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
        }elseif(strpos($fraudSetting['parameter'], 'Point user') !== false){
            $createLog = FraudDetectionLogTransactionPoint::create([
                'id_user' => $user['id'],
                'current_balance' => $currentBalance,
                'at_outlet' => $atOutlet,
                'most_outlet' => $mostOutlet,
                'count_transaction_day' => $countTrxDay,
                'fraud_detection_date'=> date('Y-m-d H:i:s', strtotime($dateTime)),
                'fraud_setting_parameter_detail' => $fraudSetting['parameter_detail'],
                'fraud_setting_forward_admin_status' => $fraudSetting['forward_admin_status'],
                'fraud_setting_auto_suspend_status' => $fraudSetting['auto_suspend_status'],
                'fraud_setting_auto_suspend_value' => $fraudSetting['auto_suspend_value'],
                'fraud_setting_auto_suspend_time_period' => $fraudSetting['suspend_time_period']
            ]);

            if($fraudSetting['auto_suspend_status'] == '1'){
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime($startDate. ' - '.$fraudSetting['fraud_setting_auto_suspend_time_period'].' days'));
                $getLogWithTimePeriod = FraudDetectionLogTransactionPoint::whereRaw("created_at BETWEEN ".$startDate.' AND '.$endDate)
                    ->where('id_user', $user['id'])
                    ->where('status', 'Active')->get();
                $countLog = count($getLogWithTimePeriod);
                if($countLog > (int)$fraudSetting['auto_suspend_value']){
                    $autoSuspend = 1;
                }
            }

            if($fraudSetting['forward_admin_status'] == '1'){
                $forwardAdmin = 1;
            }
        }elseif(strpos($fraudSetting['parameter'], 'Transaction between') !== false){
            $id_user = $user->id;
            $parameterDetailTime = $fraudSetting->parameter_detail_time;
            $parameterDetail = $fraudSetting->parameter_detail;
            $forwardAdmin = 0;
            $autoSuspend = 0;

            $insertLog = FraudDetectionLogTransactionInBetween::create([
                'id_user' => $id_user,
                'fraud_setting_parameter_detail' => $fraudSetting['parameter_detail'],
                'fraud_setting_forward_admin_status' => $fraudSetting['forward_admin_status'],
                'fraud_setting_auto_suspend_status' => $fraudSetting['auto_suspend_status'],
                'fraud_setting_auto_suspend_value' => $fraudSetting['auto_suspend_value'],
                'fraud_setting_auto_suspend_time_period' => $fraudSetting['auto_suspend_time_period']
            ]);

            if($insertLog){
                $data = [];
                foreach ($trxId as $value){
                    $dtInsert = [
                        'id_fraud_detection_log_transaction_in_between' => $insertLog['id_fraud_detection_log_transaction_in_between'],
                        'id_transaction' => $value
                    ];
                    $data[] = $dtInsert;
                }
                DB::table('fraud_between_transaction')->insert($data);
            }

            if($fraudSetting['forward_admin_status'] == '1'){
                $forwardAdmin = 1;
            }

            $autoSuspendTimePeriod = $fraudSetting->auto_suspend_time_period - 1;
            $endDateLog = date('Y-m-d');
            $startDateLog = date('Y-m-d', strtotime($endDateLog. ' - '.$autoSuspendTimePeriod.' days'));
            $getLog = FraudDetectionLogTransactionInBetween::whereRaw("DATE(created_at) BETWEEN '".$startDateLog."' AND '".$endDateLog."'")
                ->where('id_user', $id_user)
                ->where('status', 'Active')->get();
            $countLog = count($getLog);

            if($countLog > (int)$fraudSetting['auto_suspend_value']){
                $autoSuspend = 1;
            }
        }elseif(strpos($fraudSetting['parameter'], 'promo code') !== false){
            $id_user = $user->id;
            $forwardAdmin = 0;
            $autoSuspend = 0;

            if($fraudSetting['forward_admin_status'] == '1'){
                $forwardAdmin = 1;
            }

            if($fraudSetting['auto_suspend_status'] == '1'){
                $autoSuspend = 1;
            }
        }

        $contentEmail = ['transaction_count_day' => (string)$countTrxDay, 'transaction_count_week' => (string)$countTrxWeek, 'device_id' => $device['device_id'], 'device_type' => $device['device_type'] , 'count_account' => (string)$countUser, 'user_list' => $stringUserList, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), 'list_transaction_day' => $stringTransactionDay, 'list_transaction_week' => $stringTransactionWeek, 'receipt_number' => $trxId, 'area_outlet' => $areaOutlet, 'point' => $currentBalance];
        $sendFraud = $this->SendFraudDetection($fraudSetting['id_fraud_setting'], $user, null, $device, $deleteToken, $autoSuspend, $forwardAdmin, $contentEmail);

        if(isset($sendFraud))
            return $sendFraud;
        else
        return false;
    }

    function SendFraudDetection($id_fraud_setting, $user, $idTransaction = null, $deviceUser = null, $deleteToken, $autoSuspend, $forwardAdmin, $additionalData){
        $fraudSetting = FraudSetting::find($id_fraud_setting);
        if(!$fraudSetting){
            return false;
        }


        if($autoSuspend == 1){
            $delToken = 0;
            if($fraudSetting['auto_suspend_status'] == '1'){

                if($fraudSetting['auto_suspend_value'] == 'all_account'){
                    $getAllUser = UsersDeviceLogin::where('device_id',$deviceUser['device_id'])
                        ->orderBy('last_login','desc')->get()->pluck('id_user');
                    $delToken = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->whereIn('oauth_access_tokens.user_id', $getAllUser)->where('oauth_access_token_providers.provider', 'users')->delete();
                    $updateUser = User::whereIn('id',$getAllUser)->whereRaw('level != "Super Admin"')->update(['is_suspended' => 1]);
                }elseif($fraudSetting['auto_suspend_value'] == 'last_account'){
                    $getAllUser = UsersDeviceLogin::where('device_id',$deviceUser['device_id'])
                        ->orderBy('last_login','desc')->get()->pluck('id_user');
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
                        $subject = app($this->autocrm)->TextReplace($fraudSetting['email_subject'], $user['phone'], $additionalData);
                        $content = app($this->autocrm)->TextReplace($fraudSetting['email_content'], $user['phone'], $additionalData);

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
                            Mail::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
                            {
                                $message->to($to, $name)->subject($subject);
                                if(env('MAIL_DRIVER') == 'mailgun'){
                                    $message->trackClicks(true)
                                            ->trackOpens(true);
                                }
                                if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                                    $message->from($setting['email_sender'], $setting['email_from']);
                                }else if(!empty($setting['email_sender'])){
                                    $message->from($setting['email_sender']);
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
                            'callbackurl' => config('url.app_url'),
                            'datapacket'=>array()
                        );

                        $content = app($this->autocrm)->TextReplace($fraudSetting['sms_content'], $user['phone'], $additionalData);

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
                                $content = $this->TextReplace($fraudSetting['whatsapp_content'], $user['phone'], $additionalData);

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

    function fraudTrxPoint($sumBalance, $user, $data){
        $fraudTrxPoint = FraudSetting::where('parameter', 'LIKE', '%point%')->where('fraud_settings_status','Active')->first();
        if($fraudTrxPoint){
            if($sumBalance > $fraudTrxPoint['parameter_detail']){
                $countOutlet = Transaction::where('transaction_payment_status','Completed')->where('id_user', $user['id'])
                    ->groupBy('id_outlet')->selectRaw('count(id_outlet) as "total_oultet", id_outlet')->orderBy('total_oultet', 'desc')->get()->toArray();

                if(!empty($countOutlet)){
                    if($countOutlet[0]['id_outlet'] != $data['id_outlet']){
                        DB::rollBack();
                        $checkFraud = $this->checkFraud($fraudTrxPoint, $user, null, 0, 0, date('Y-m-d H:i:s'), 0,null,
                            $sumBalance, $countOutlet[0]['id_outlet'], $data['id_outlet']);
                        return ['status' => 'fail', 'messages' => ['Transaction failed. Point can not use in this outlet.']];
                    }
                }
            }
        }

        return true;
    }

	function fraudCheckPromoCode($data){
        $fraudCheckPromo = FraudSetting::where('parameter', 'LIKE', '%promo code%')->where('fraud_settings_status','Active')->first();

        if(!$fraudCheckPromo){
            return [
                'status'=>'success'
            ];
        }

        $folder1 = 'fraud';
        $folder2 = 'checkPromoCode';
        $file = $data['id_user'].'.json';

        //check folder
        if(env('STORAGE') == 'local'){
            if(!Storage::disk(env('STORAGE'))->exists($folder1)){
                Storage::makeDirectory($folder1);
            }

            if(!Storage::disk(env('STORAGE'))->exists($folder1.'/'.$folder2)){
                Storage::makeDirectory($folder1.'/'.$folder2);
            }
        }

        if(Storage::disk(env('STORAGE'))->exists($folder1.'/'.$folder2.'/'.$file)){
            $readContent = Storage::disk(env('STORAGE'))->get($folder1.'/'.$folder2.'/'.$file);
            $content = json_decode($readContent);
            $currentTime = date('Y-m-d H:i:s');

            if(strtotime($currentTime) < strtotime($content->available_access)){
                return [
                    'status'=>'fail',
                    'messages'=>[$fraudCheckPromo['result_text']]
                ];
            }else{
                $deleteData = DailyCheckPromoCode::where('id_user', $data['id_user'])->delete();
                MyHelper::deleteFile($folder1.'/'.$folder2.'/'.$file);
            }
        }

        $createLogCheckPromoCode  = LogCheckPromoCode::create($data);
        $createDailyCheckPromoCode  = DailyCheckPromoCode::create($data);

        $time = $fraudCheckPromo->parameter_detail_time;
        $numberOfViolation = $fraudCheckPromo->parameter_detail;
        $end = date('Y-m-d H:i:s');
        $start = date('Y-m-d H:i:s',strtotime('-'.(int)$time.' minutes',strtotime($end)));

        $getDailyLog = DailyCheckPromoCode::where('id_user', $data['id_user'])
            ->where('created_at','>=',$start)
            ->where('created_at','<=',$end)
            ->count();

        if($getDailyLog > $numberOfViolation){
            $userData = User::where('id', $data['id_user'])->first();
            $createLog = FraudDetectionLogCheckPromoCode::create([
                'id_user' => $data['id_user'],
                'count' => $getDailyLog,
                'fraud_setting_parameter_detail' => $fraudCheckPromo['parameter_detail'],
                'fraud_parameter_detail_time' => $fraudCheckPromo['parameter_detail_time'],
                'fraud_hold_time' => $fraudCheckPromo['hold_time'],
                'fraud_setting_forward_admin_status' => $fraudCheckPromo['forward_admin_status'],
                'fraud_setting_auto_suspend_status' => $fraudCheckPromo['auto_suspend_status'],
                'fraud_setting_auto_suspend_value' => $fraudCheckPromo['auto_suspend_value'],
                'fraud_setting_auto_suspend_time_period' => $fraudCheckPromo['suspend_time_period']
            ]);

            if($createLog){
                $availebleTime = date('Y-m-d H:i:s',strtotime('+'.(int)$fraudCheckPromo['hold_time'].' minutes',strtotime(date('Y-m-d H:i:s'))));
                $contentFile = [
                    'available_access' => $availebleTime
                ];
                $createFile = MyHelper::createFile($contentFile, 'json', 'fraud/checkPromoCode/', $data['id_user']);
                $sendFraud = $this->checkFraud($fraudCheckPromo, $userData, null, 0, 0, null, 0, 0, null, null, null);
                return [
                    'status'=>'fail',
                    'messages'=>[$fraudCheckPromo['result_text']]
                ];
            }else{
                return [
                    'status'=>'fail',
                    'messages'=>['Failed to add log promo code']
                ];
            }
        }else{
            return [
                'status'=>'success'
            ];
        }
    }

    function fraudCheckReferralUser($data){
        $fraudSetting = FraudSetting::where('parameter', 'LIKE', '%referral user%')->where('fraud_settings_status','Active')->first();
        if($fraudSetting){
            $chekFraudRefferalCode = PromoCampaignReferralTransaction::where('id_user', $data['id_user'])->count();
            if($chekFraudRefferalCode > 1){
                $data['fraud_setting_forward_admin_status'] = $fraudSetting['forward_admin_status'];
                $data['fraud_setting_auto_suspend_status'] = $fraudSetting['forward_admin_status'];
                FraudDetectionLogReferralUsers::create($data);
            }
        }
        return true;
    }

    function fraudCheckReferral($data){
        $fraudSetting = FraudSetting::where('parameter', 'LIKE', '%referral global%')->where('fraud_settings_status','Active')->first();
        if($fraudSetting){
            $paramater = $fraudSetting['parameter_detail'];
            $paramaterTime = $fraudSetting['parameter_detail_time'] - 1;
            $end = date('Y-m-d');
            $start = date('Y-m-d', strtotime('-'.$paramaterTime.' day', strtotime($end)));

            $getDataTransaction = PromoCampaignPromoCode::join('promo_campaign_referral_transactions', 'promo_campaign_referral_transactions.id_promo_campaign_promo_code', 'promo_campaign_promo_codes.id_promo_campaign_promo_code')
                                ->whereRaw('DATE(promo_campaign_referral_transactions.created_at) BETWEEN "'.$start.'" AND "'.$end.'"')
                                ->where('promo_code', $data['referral_code'])
                                ->select('promo_code', DB::raw('COUNT(promo_code) as count_data'))
                                ->get()->toArray();

            $sendNotif = $this->detailfraudReferral('referral', $data);
            if($sendNotif){
                if(isset($getDataTransaction[0]['count_data']) && $getDataTransaction[0]['count_data'] > $paramater){
                    $data['fraud_setting_parameter_detail'] = $fraudSetting['parameter_detail'];
                    $data['fraud_setting_parameter_detail_time'] = $fraudSetting['parameter_detail_time'];
                    $data['fraud_setting_forward_admin_status'] = $fraudSetting['forward_admin_status'];
                    $data['fraud_setting_auto_suspend_status'] = $fraudSetting['forward_admin_status'];
                    FraudDetectionLogReferral::create($data);
                }
            }
        }
        return true;
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
                    ->whereNotNull('transactions.fraud_flag')
                    ->whereRaw('DATE(fraud_detection_log_transaction_day.fraud_detection_date) = DATE(transactions.transaction_date)')
                    ->whereRaw("DATE(fraud_detection_log_transaction_day.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_day.status','Active')
                    ->orderBy('fraud_detection_log_transaction_day.created_at','desc')->select('users.name', 'users.phone', 'users.email', 'outlets.outlet_name', 'fraud_detection_log_transaction_day.*', 'transactions.*', 'fraud_detection_log_transaction_day.created_at as log_date');
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
                    ->whereNotNull('transactions.fraud_flag')
                    ->whereRaw("DATE(fraud_detection_log_transaction_week.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_week.status','Active')
                    ->orderBy('fraud_detection_log_transaction_week.created_at','desc')->select('users.name', 'users.phone', 'users.email', 'outlets.outlet_name','fraud_detection_log_transaction_week.*', 'transactions.*', 'fraud_detection_log_transaction_week.created_at as log_date');

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
        }elseif($type == 'transaction-between'){
            $table = 'fraud_detection_log_transaction_in_between';
            $id = 'id_fraud_detection_log_transaction_in_between';
            if(isset($post['export']) && $post['export'] == 1){
                $queryList = FraudDetectionLogTransactionInBetween::join('users', 'users.id', '=', 'fraud_detection_log_transaction_in_between.id_user')
                    ->join('fraud_between_transaction', 'fraud_detection_log_transaction_in_between.id_fraud_detection_log_transaction_in_between', '=', 'fraud_between_transaction.id_fraud_detection_log_transaction_in_between')
                    ->join('transactions', 'transactions.id_transaction', '=', 'fraud_between_transaction.id_transaction')
                    ->join('outlets', 'outlets.id_outlet', '=', 'transactions.id_outlet')
                    ->whereRaw("DATE(fraud_detection_log_transaction_in_between.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->groupBy('transactions.id_transaction')
                    ->select('transactions.*', 'outlets.outlet_name', 'fraud_detection_log_transaction_in_between.*', 'users.name', 'users.phone', 'users.email', 'fraud_detection_log_transaction_in_between.created_at as log_date');
            }else{
                $queryList = FraudDetectionLogTransactionInBetween::join('users', 'users.id', '=', 'fraud_detection_log_transaction_in_between.id_user')
                    ->whereRaw("DATE(fraud_detection_log_transaction_in_between.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                    ->where('fraud_detection_log_transaction_in_between.status','Active')
                    ->orderBy('fraud_detection_log_transaction_in_between.created_at','desc')
                    ->groupBy(DB::raw('Date(fraud_detection_log_transaction_in_between.created_at)'), 'id_user')
                    ->select('users.name', 'users.phone','fraud_detection_log_transaction_in_between.*');
            }
        }elseif($type == 'transaction-point'){
            $table = 'fraud_detection_log_transaction_point';
            $id = 'id_fraud_detection_log_transaction_point';
            $queryList = FraudDetectionLogTransactionPoint::join('users', 'users.id', '=', 'fraud_detection_log_transaction_point.id_user')
                ->whereRaw("DATE(fraud_detection_log_transaction_point.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                ->where('fraud_detection_log_transaction_point.status','Active')
                ->orderBy('fraud_detection_log_transaction_point.created_at','desc')->with(['mostOutlet', 'atOutlet']);
        }elseif($type == 'referral-user'){
            $table = 'fraud_detection_log_referral_users';
            $id = 'id_fraud_detection_log_referral_users';
            $queryList = FraudDetectionLogReferralUsers::join('users', 'users.id', 'fraud_detection_log_referral_users.id_user')
                        ->join('transactions', 'fraud_detection_log_referral_users.id_transaction', 'transactions.id_transaction')
                        ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                        ->whereRaw("DATE(fraud_detection_log_referral_users.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                        ->select('fraud_detection_log_referral_users.*', 'outlets.outlet_name', 'transactions.transaction_receipt_number', 'transactions.trasaction_type', 'users.name', 'users.phone', 'users.email');
        }elseif($type == 'referral'){
            $table = 'fraud_detection_log_referral';
            $id = 'id_fraud_detection_log_referral';
            $queryList = FraudDetectionLogReferral::join('users', 'users.id', 'fraud_detection_log_referral.id_user')
                ->join('transactions', 'fraud_detection_log_referral.id_transaction', 'transactions.id_transaction')
                ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                ->whereRaw("DATE(fraud_detection_log_referral.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                ->select('fraud_detection_log_referral.*', 'outlets.outlet_name', 'transactions.transaction_receipt_number', 'transactions.trasaction_type', 'users.name', 'users.phone', 'users.email');
        }elseif($type == 'promo-code'){
            $table = 'fraud_detection_log_check_promo_code';
            $id = 'id_fraud_detection_log_check_promo_code';
            $queryList = FraudDetectionLogCheckPromoCode::join('users', 'users.id', 'fraud_detection_log_check_promo_code.id_user')
                ->whereRaw("DATE(fraud_detection_log_check_promo_code.created_at) BETWEEN '".$date_start."' AND '".$date_end."'")
                ->select('fraud_detection_log_check_promo_code.*', 'users.name', 'users.phone', 'users.email');
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

                            if($condition['subject'] == 'outlet' && $type == 'transaction-between'){
                                $queryList = $queryList->whereRaw('fraud_detection_log_transaction_in_between.id_user in
                                                (SELECT id_user FROM fraud_between_transaction fbt
                                                JOIN transactions t ON t.id_transaction = fbt.id_transaction
                                                JOIN outlets o ON o.id_outlet = t.id_outlet WHERE t.id_outlet = '.$conditionParameter.')');
                            }elseif($condition['subject'] == 'outlet'){
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

                            if($condition['subject'] == 'outlet' && $type == 'transaction-between'){
                                $queryList = $queryList->orWhere('fraud_detection_log_transaction_in_between.id_user in
                                                (SELECT id_user FROM fraud_between_transaction fbt
                                                JOIN transactions t ON t.id_transaction = fbt.id_transaction
                                                JOIN outlets o ON o.id_outlet = t.id_outlet WHERE t.id_outlet = '.$conditionParameter.')');
                            }elseif($condition['subject'] == 'outlet'){
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
            ->select('transactions.*',  DB::raw('(CASE
                                WHEN (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" ) 
                                is NULL THEN NULL
                                ELSE (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" )
                            END) as balance'))
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
            ->select('transactions.*',  DB::raw('(CASE
                                WHEN (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" ) 
                                is NULL THEN NULL
                                ELSE (select balance from log_balances  where log_balances.id_reference = transactions.id_transaction AND log_balances.balance > 0 AND  log_balances.source ="Transaction" )
                            END) as balance'))
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

    function detailFraudTransactionBetween(Request $request){
        $post = $request->json()->all();


        $detailLog = FraudDetectionLogTransactionInBetween::join('fraud_between_transaction', 'fraud_detection_log_transaction_in_between.id_fraud_detection_log_transaction_in_between', '=', 'fraud_between_transaction.id_fraud_detection_log_transaction_in_between')
                    ->join('transactions', 'transactions.id_transaction', '=', 'fraud_between_transaction.id_transaction')
                    ->join('outlets', 'outlets.id_outlet', '=', 'transactions.id_outlet')
                    ->where('fraud_detection_log_transaction_in_between.id_user',$post['id_user'])->whereDate('fraud_detection_log_transaction_in_between.created_at',$post['date_fraud'])
                    ->groupBy('transactions.id_transaction')
                    ->select('transactions.*', 'outlets.outlet_name', 'fraud_detection_log_transaction_in_between.*')
                    ->with(['user'])->get()->toArray();

        return response()->json([
            'status' => 'success',
            'result' => [
                'detail_log' => $detailLog
            ]
        ]);
    }

    function detailFraudPromoCode(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_fraud_detection_log_check_promo_code'])){
            $detail_fraud = FraudDetectionLogCheckPromoCode::join('users', 'users.id', '=', 'fraud_detection_log_check_promo_code.id_user')
                ->where('id_fraud_detection_log_check_promo_code', $post['id_fraud_detection_log_check_promo_code'])
                ->select('fraud_detection_log_check_promo_code.*', 'users.name', 'users.phone', 'users.email')
                ->first();
            $end = $detail_fraud['created_at'];
            $start = date('Y-m-d H:i:s',strtotime('-'.(int)$detail_fraud['fraud_parameter_detail_time'].' minutes',strtotime($end)));
            $listCheckPromo = DailyCheckPromoCode::where('created_at','>=',$start)
                ->where('created_at','<=',$end)->get()->toArray();
        }

        return response()->json([
            'status' => 'success',
            'result' => [
                'list_promo_code' => $listCheckPromo,
                'detail_fraud' => $detail_fraud
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
            $logTransactionPoint = User::join('fraud_detection_log_transaction_point', 'users.id', '=', 'fraud_detection_log_transaction_point.id_user')
                ->where('users.phone',$post['phone'])->where('fraud_detection_log_transaction_point.status','Active')
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_transaction_point.*')->get()->toArray();
            $logTransactionBetween = User::join('fraud_detection_log_transaction_in_between', 'users.id', '=', 'fraud_detection_log_transaction_in_between.id_user')
                ->where('users.phone',$post['phone'])->where('fraud_detection_log_transaction_in_between.status','Active')
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_transaction_in_between.*')->get()->toArray();
            $logReferralUser = User::join('fraud_detection_log_referral_users', 'users.id', '=', 'fraud_detection_log_referral_users.id_user')
                ->join('transactions', 'fraud_detection_log_referral_users.id_transaction', 'transactions.id_transaction')
                ->where('users.phone',$post['phone'])
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_referral_users.*', 'transactions.transaction_receipt_number', 'transactions.trasaction_type')->get()->toArray();
            $logReferral = User::join('fraud_detection_log_referral', 'users.id', '=', 'fraud_detection_log_referral.id_user')
                ->where('users.phone',$post['phone'])
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_referral.*')->get()->toArray();
            $logPromoCode = User::join('fraud_detection_log_check_promo_code', 'users.id', '=', 'fraud_detection_log_check_promo_code.id_user')
                ->where('users.phone',$post['phone'])
                ->select('users.name', 'users.phone', 'users.email', 'fraud_detection_log_check_promo_code.*')->get()->toArray();

            $result = [
                'status' => 'success',
                'result' => [
                    'detail_user' => $user,
                    'list_device' => $logDevice,
                    'list_trans_day' => $logTransactionDay,
                    'list_trans_week' => $logTransactionWeek,
                    'list_trans_point' => $logTransactionPoint,
                    'list_trans_between' => $logTransactionBetween,
                    'list_referral_user' => $logReferralUser,
                    'list_referral' => $logReferral,
                    'list_promo_code' => $logPromoCode
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
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_transaction_point WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_transaction_in_between WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_referral_users WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_check_promo_code WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
            $query->orwhereRaw('users.id in (SELECT id_user FROM fraud_detection_log_referral WHERE id_user = users.id AND DATE(created_at) BETWEEN "'.$data['date_start'].'" AND "'.$data['date_end'].'")');
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

    /*=============== All Cron ===============*/
    public function fraudCron(){
        //cron fraud in between
        $fraudBetween = $this->cronFraudInBetween();
        if (!$fraudBetween) {
            return false;
        }

        //delete data from table daily check promo code
        $deleteDailyPromoCode = $this->deleteDailyLogCheckPromo();
        if (!$deleteDailyPromoCode) {
            return false;
        }

        //delete data daily trx
        $deleteDailyTrx = $this->deleteDailyTransactions();
        if (!$deleteDailyTrx) {
            return false;
        }

        return true;
    }

    public function cronFraudInBetween(){
        $fraudSetting = FraudSetting::where('parameter', 'LIKE', '%between%')->where('fraud_settings_status','Active')->first();

        if($fraudSetting){
            $parameterDetail = $fraudSetting->parameter_detail;
            $currentDate = date('Y-m-d');
            $bellowDate = date('Y-m-d',strtotime($currentDate." -3 Months"));

            //Delete data bellow 3 months from current date
            $deleteDailyTrx = DailyTransactions::whereDate('transaction_date', '<', $bellowDate)->delete();

            //Count current transaction by user
            $getTrxFisrt = DailyTransactions::whereDate('transaction_date','=',$currentDate)
                                ->where('flag_check',0)
                               ->orderBy('id_user')
                               ->orderBy('transaction_date', 'asc')
                               ->pluck('id_transaction');

            $getTrxStatusCompleted = Transaction::whereIn('id_transaction',$getTrxFisrt)
                    ->where('transaction_payment_status', 'Completed')->pluck('id_transaction');

            $getTrx = DailyTransactions::whereDate('transaction_date','=',$currentDate)
                ->where('flag_check',0)
                ->whereIn('id_transaction',$getTrxStatusCompleted)
                ->orderBy('id_user')
                ->orderBy('transaction_date', 'asc')
                ->get()->toArray();

            //checking between transaction
            $count = count($getTrx);
            if($count > 0){
                for($i=0;$i<$count-1;$i++){
                    if($getTrx[$i]['id_user'] != $getTrx[$i+1]['id_user']){
                        continue;
                    }
                    $toTime = strtotime($getTrx[$i]['transaction_date']);
                    $fromTime = strtotime($getTrx[$i+1]['transaction_date']);
                    $differentTime = abs($toTime - $fromTime) / 60;

                    if($differentTime <= (int)$parameterDetail){
                        $userData = User::where('id', $getTrx[$i]['id_user'])->first();
                        $checkFraudMinute = $this->checkFraud($fraudSetting, $userData, null, 0, 0, null, 0, [$getTrx[$i]['id_transaction'], $getTrx[$i+1]['id_transaction']]);
                    }
                    DailyTransactions::where('id_daily_transaction', $getTrx[$i]['id_daily_transaction'])->update(['flag_check' => 1]);
                }
            }
        }

        return 'true';

    }

    function detailfraudReferral($type, $dt){
        //this cron only execution action by setting
        if($type == 'referral user'){
            //referral user
            $fraudSetting = FraudSetting::where('parameter', 'LIKE', '%referral user%')->where('fraud_settings_status','Active')->first();
            $userData = User::where('id', $dt['id_user'])->first();
            $dataTrx = Transaction::where('id_transaction', $dt['id_transaction'])->first();

            $content = '<table>';
            $content .= '<tr>';
            $content .= '<td>User Name</td>';
            $content .= '<td>: '.$userData['name'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>User Phone</td>';
            $content .= '<td>: '.$userData['phone'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Receipt Number</td>';
            $content .= '<td>: '.$dataTrx['id_transaction'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Referral code</td>';
            $content .= '<td>: '.$dt['referral_code'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Transaction Date</td>';
            $content .= '<td>: '.date('d F Y', strtotime($dt['referral_code_use_date'])).'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Transaction Time</td>';
            $content .= '<td>: '.date('H:i', strtotime($dt['referral_code_use_date'])).'</td>';
            $content .= '</tr>';
            $content .= '</table>';

            $contentEmail = ['data_fraud' => $content, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), ];
            $sendFraud = $this->SendFraudDetection($fraudSetting['id_fraud_setting'], $userData, null, null, 0, $fraudSetting['auto_suspend_status'], $fraudSetting['forward_admin_status'], $contentEmail);
        }elseif ($type == 'referral'){
            //referral global
            $fraudSetting = FraudSetting::where('parameter', 'LIKE', '%referral global%')->where('fraud_settings_status','Active')->first();
            $userData = User::where('id', $dt['id_user'])->first();
            $dataTrx = Transaction::where('id_transaction', $dt['id_transaction'])->first();

            $content = '<table>';
            $content .= '<tr>';
            $content .= '<td>User Name</td>';
            $content .= '<td>: '.$userData['name'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>User Phone</td>';
            $content .= '<td>: '.$userData['phone'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Receipt Number</td>';
            $content .= '<td>: '.$dataTrx['id_transaction'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Referral code</td>';
            $content .= '<td>: '.$dt['referral_code'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Transaction Date</td>';
            $content .= '<td>: '.date('d F Y', strtotime($dt['referral_code_use_date'])).'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>Transaction Time</td>';
            $content .= '<td>: '.date('H:i', strtotime($dt['referral_code_use_date'])).'</td>';
            $content .= '</tr>';
            $content .= '</table>';

            $contentEmail = ['data_fraud' => $content, 'fraud_date' => date('d F Y'), 'fraud_time' => date('H:i'), ];
            $sendFraud = $this->SendFraudDetection($fraudSetting['id_fraud_setting'], $userData, null, null, 0, $fraudSetting['auto_suspend_status'], $fraudSetting['forward_admin_status'], $contentEmail);
        }

        return true;
    }

    public function deleteDailyLogCheckPromo(){
        $currentDate = date('Y-m-d');
        $bellowDate = date('Y-m-d',strtotime($currentDate." -1 Months"));

        //Delete data bellow 3 months from current date
        $deleteDailyTrx = DailyCheckPromoCode::whereDate('created_at', '<', $bellowDate)->delete();

        return 'true';
    }

    public function deleteDailyTransactions(){
        $currentDate = date('Y-m-d');
        $bellowDate = date('Y-m-d',strtotime($currentDate." -1 Months"));

        //Delete data bellow 3 months from current date
        $deleteDailyTrx = DailyTransactions::whereDate('created_at', '<', $bellowDate)->delete();

        return 'true';
    }
    /*=============== End Cron ===============*/
}
