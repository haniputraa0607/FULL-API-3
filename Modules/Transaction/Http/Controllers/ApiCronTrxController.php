<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Queue;
use App\Lib\Midtrans;

use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use App\Lib\classTexterSMS;
use App\Lib\classMaskingJson;
use App\Lib\apiwha;
use Validator;
use Hash;
use DB;
use Mail;

use App\Jobs\CronBalance;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPickup;
use App\Http\Models\User;
use App\Http\Models\LogBalance;
use App\Http\Models\AutocrmEmailLog;
use App\Http\Models\Autocrm;
use App\Http\Models\Setting;
use App\Http\Models\LogPoint;
use App\Http\Models\Outlet;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\OutletApp\Jobs\AchievementCheck;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionDay;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionWeek;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;

class ApiCronTrxController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        // ini_set('max_execution_time', 600);
        ini_set('max_execution_time', 0);
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
        $this->voucher  = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->promo_campaign = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->getNotif = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->trx    = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->membership       = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->subscription  = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        $this->shopeepay      = "Modules\ShopeePay\Http\Controllers\ShopeePayController";
    }

    public function cron(Request $request)
    {
        $log = MyHelper::logCron('Cancel Transaction');
        try {
            $crossLine = date('Y-m-d H:i:s', strtotime('- 3days'));
            $dateLine  = date('Y-m-d H:i:s', strtotime('- 1days'));
            $now       = date('Y-m-d H:i:s');
            $expired   = date('Y-m-d H:i:s',strtotime('- 5minutes'));

            $getTrx = Transaction::where('transaction_payment_status', 'Pending')->where('transaction_date', '<=', $expired)->get();

            if (empty($getTrx)) {
                $log->success('empty');
                return response()->json(['empty']);
            }
            $count = 0;
            foreach ($getTrx as $key => $singleTrx) {

                $singleTrx->load('outlet_name');

                $productTrx = TransactionProduct::where('id_transaction', $singleTrx->id_transaction)->get();
                if (empty($productTrx)) {
                    continue;
                }

                $user = User::where('id', $singleTrx->id_user)->first();
                if (empty($user)) {
                    continue;
                }
                if($singleTrx->trasaction_payment_type == 'Midtrans') {
                    $midtransStatus = Midtrans::status($singleTrx->id_transaction);
                    if ((($midtransStatus['status'] ?? false) == 'fail' && ($midtransStatus['messages'][0] ?? false) == 'Midtrans payment not found') || in_array(($midtransStatus['response']['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure']) || ($midtransStatus['status_code'] ?? false) == '404') {
                        $connectMidtrans = Midtrans::expire($singleTrx->transaction_receipt_number);
                    } else {
                        continue;
                    }
                }elseif($singleTrx->trasaction_payment_type == 'Ipay88') {
                    $trx_ipay = TransactionPaymentIpay88::where('id_transaction',$singleTrx->id_transaction)->first();

                    if ($trx_ipay && strtolower($trx_ipay->payment_method) == 'credit card' && $singleTrx->transaction_date > date('Y-m-d H:i:s', strtotime('- 15minutes'))) {
                        continue;
                    }

                    $update = \Modules\IPay88\Lib\IPay88::create()->update($trx_ipay?:$singleTrx->id_transaction,[
                        'type' =>'trx',
                        'Status' => '0',
                        'requery_response' => 'Cancelled by cron'
                    ],false,false);
                    continue;                
                }
                // $detail = $this->getHtml($singleTrx, $productTrx, $user->name, $user->phone, $singleTrx->created_at, $singleTrx->transaction_receipt_number);

                // $autoCrm = app($this->autocrm)->SendAutoCRM('Transaction Online Cancel', $user->phone, ['date' => $singleTrx->created_at, 'status' => $singleTrx->transaction_payment_status, 'name'  => $user->name, 'id' => $singleTrx->transaction_receipt_number, 'receipt' => $detail, 'id_reference' => $singleTrx->transaction_receipt_number]);
                // if (!$autoCrm) {
                //     continue;
                // }

                DB::beginTransaction();

                MyHelper::updateFlagTransactionOnline($singleTrx, 'cancel', $user);

                $singleTrx->transaction_payment_status = 'Cancelled';
                $singleTrx->void_date = $now;
                $singleTrx->save();

                if (!$singleTrx) {
                    continue;
                }

                //reversal balance
                $logBalance = LogBalance::where('id_reference', $singleTrx->id_transaction)->whereIn('source', ['Online Transaction', 'Transaction'])->where('balance', '<', 0)->get();
                foreach($logBalance as $logB){
                    $reversal = app($this->balance)->addLogBalance( $singleTrx->id_user, abs($logB['balance']), $singleTrx->id_transaction, 'Reversal', $singleTrx->transaction_grandtotal);
    	            if (!$reversal) {
    	            	DB::rollback();
    	            	continue;
    	            }
                    $order_id = TransactionPickup::select('order_id')->where('id_transaction', $singleTrx->id_transaction)->pluck('order_id')->first();
                    $usere= User::where('id',$singleTrx->id_user)->first();
                    $send = app($this->autocrm)->SendAutoCRM('Transaction Failed Point Refund', $usere->phone,
                        [
                            "outlet_name"       => $singleTrx->outlet_name->outlet_name,
                            "transaction_date"  => $singleTrx->transaction_date,
                            'id_transaction'    => $singleTrx->id_transaction,
                            'receipt_number'    => $singleTrx->transaction_receipt_number,
                            'received_point'    => (string) abs($logB['balance']),
                            'order_id'          => $order_id,
                        ]
                    );
                }

                // delete promo campaign report
                if ($singleTrx->id_promo_campaign_promo_code) {
                	$update_promo_report = app($this->promo_campaign)->deleteReport($singleTrx->id_transaction, $singleTrx->id_promo_campaign_promo_code);
                	if (!$update_promo_report) {
    	            	DB::rollBack();
    	            	continue;
    	            }	
                }

                // return voucher
                $update_voucher = app($this->voucher)->returnVoucher($singleTrx->id_transaction);

                // return subscription
                $update_subscription = app($this->subscription)->returnSubscription($singleTrx->id_transaction);

                if (!$update_voucher) {
                	DB::rollback();
                	continue;
                }
                DB::commit();

            }

            $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }

    public function checkSchedule()
    {
        $log = MyHelper::logCron('Check Schedule');
        try {
            $result = [];

            $data = LogBalance::orderBy('id_log_balance', 'DESC')->whereNotNull('enc')->get()->toArray();

            foreach ($data as $key => $val) {
                $dataHash = [
                    'id_log_balance'                 => $val['id_log_balance'],
                    'id_user'                        => $val['id_user'],
                    'balance'                        => $val['balance'],
                    'balance_before'                 => $val['balance_before'],
                    'balance_after'                  => $val['balance_after'],
                    'id_reference'                   => $val['id_reference'],
                    'source'                         => $val['source'],
                    'grand_total'                    => $val['grand_total'],
                    'ccashback_conversion'           => $val['ccashback_conversion'],
                    'membership_level'               => $val['membership_level'],
                    'membership_cashback_percentage' => $val['membership_cashback_percentage']
                ];


                $encodeCheck = json_encode($dataHash);

                if (MyHelper::decrypt2019($val['enc']) != $encodeCheck) {
                    $result[] = $val;
                }
            }

            if (!empty($result)) {
                $crm = Autocrm::where('autocrm_title','=','Cron Transaction')->with('whatsapp_content')->first();
                if (!empty($crm)) {
                    if(!empty($crm['autocrm_forward_email'])){
                        $exparr = explode(';',str_replace(',',';',$crm['autocrm_forward_email']));
                        foreach($exparr as $email){
                            $n   = explode('@',$email);
                            $name = $n[0];

                            $to      = $email;

                            $content = str_replace('%table_trx%', '', $crm['autocrm_forward_email_content']);

                            $content .= $this->html($result);
                            // return response()->json($this->html($result));
                            // get setting email
                            $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
                            $setting = array();
                            foreach ($getSetting as $key => $value) {
                                $setting[$value['key']] = $value['value'];
                            }

                            $subject = $crm['autocrm_forward_email_subject'];

                            $data = array(
                                'customer'     => $name,
                                'html_message' => $content,
                                'setting'      => $setting
                            );

                            Mail::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
                            {
                                $message->to($to, $name)->subject($subject);
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

                            // $logData = [];
                            // $logData['id_user'] = 999999999;
                            // $logData['email_log_to'] = $email;
                            // $logData['email_log_subject'] = $subject;
                            // $logData['email_log_message'] = $content;

                            // $logs = AutocrmEmailLog::create($logData);
                        }
                    }
                }
            }

            if (!empty($result)) {
                $log->fail(['data_error' => count($result), 'message' => 'Check your email']);
                return ['status' => 'success', 'data_error' => count($result), 'message' => 'Check your email'];
            } else {
                $log->success(['data_error' => count($result)]);
                return ['status' => 'success', 'data_error' => count($result)];
            }
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function html($data)
    {
        $label = '';
        foreach ($data as $key => $value) {
            // $real = json_decode(MyHelper::decryptkhususnew($value['enc']));
            $real = json_decode(MyHelper::decrypt2019($value['enc']));
            // dd($real->source);
            $user = User::where('id', $value['id_user'])->first();
            if ($value['source'] == 'Transaction' || $value['source'] == 'Rejected Order' || $value['source'] == 'Reverse Point from Rejected Order') {
                $detail = Transaction::with('outlet', 'transaction_pickup')->where('id_transaction', $value['id_reference'])->first();

                $label .= '<tr>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.($key+1).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Real</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$user['name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->source.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.date('Y-m-d', strtotime($detail['created_at'])).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['transaction_receipt_number'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['transaction_pickup']['order_id'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->balance.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->balance_before.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->balance_after.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->grand_total.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->membership_level.'</td>
  </tr>
  <tr>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Change</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$user['name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['source'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.date('Y-m-d', strtotime($detail['created_at'])).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['transaction_receipt_number'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['transaction_pickup']['order_id'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance_before'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance_after'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['grand_total'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['membership_level'].'</td>
  </tr>';
            } else {
                $label .= '<tr>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.($key+1).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Real</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$user['name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['source'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.date('Y-m-d', strtotime($value['created_at'])).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->balance.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->balance_before.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->balance_after.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->grand_total.'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$real->membership_level.'</td>
  </tr>
  <tr>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Change</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$user['name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['source'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.date('Y-m-d', strtotime($value['created_at'])).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance_before'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance_after'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['grand_total'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['membership_level'].'</td>
  </tr>';
            }
        }
        return '<table style="font-family: arial, sans-serif;border-collapse: collapse;width: 100%;border: 1px solid #dddddd;">
  <tr>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">No</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Ket Data</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Name</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Type</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Date</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Receipt Number</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Order ID</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Get Point</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Point Before</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Point After</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Grand Total</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Membership Level</th>
  </tr>
  '.$label.'
</table>';
    }

    public function completeTransactionPickup(){
        $log = MyHelper::logCron('Complete Transaction Pickup');
        try {
            $trxs = Transaction::whereDate('transaction_date', '<', date('Y-m-d'))
                ->where('trasaction_type', 'Pickup Order')
                ->join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
                ->whereNull('taken_at')
                ->whereNull('reject_at')
                ->whereNull('taken_by_system_at')
                ->get();
            $idTrx = [];
            // apply point if ready_at null
            foreach ($trxs as $newTrx) {
                $idTrx[] = $newTrx->id_transaction;
                if(
                    !empty($newTrx->ready_at) || //   has been marked ready   or 
                    $newTrx->transaction_payment_status != 'Completed' || // payment status not complete  or
                    $newTrx->cashback_insert_status || // cashback has been given   or
                    $newTrx->pickup_by != 'Customer' // not pickup by the customer
                ){
                    // continue without add cashback
                    continue;
                }
                $newTrx->load('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers','promo_campaign_promo_code','promo_campaign_promo_code.promo_campaign');

                $checkType = TransactionMultiplePayment::where('id_transaction', $newTrx->id_transaction)->get()->toArray();
                $column = array_column($checkType, 'type');

                $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';

                MyHelper::updateFlagTransactionOnline($newTrx, 'success', $newTrx->user);
                if ((!in_array('Balance', $column) || $use_referral) && $newTrx->user) {

                    $promo_source = null;
                    if ( $newTrx->id_promo_campaign_promo_code || $newTrx->transaction_vouchers || $use_referral) 
                    {
                        if ( $newTrx->id_promo_campaign_promo_code ) {
                            $promo_source = 'promo_code';
                        }
                        elseif ( ($newTrx->transaction_vouchers[0]->status??false) == 'success' )
                        {
                            $promo_source = 'voucher_online';
                        }
                    }

                    if( app($this->trx)->checkPromoGetPoint($promo_source) || $use_referral)
                    {
                        $savePoint = app($this->getNotif)->savePoint($newTrx);
                    }
                }
                $newTrx->update(['cashback_insert_status' => 1]);

                if ($newTrx->user) {
                    //check achievement
                    AchievementCheck::dispatch(['id_transaction' => $newTrx->id_transaction, 'phone' => $newTrx->user->phone])->onConnection('achievement');
                }
            
            }
            //update taken_by_sistem_at
            $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                        ->update(['taken_by_system_at' => date('Y-m-d 00:00:00')]);

            $log->success('success');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function cancelTransactionIPay()
    {
        // 15 minutes before
        $max_time = date('Y-m-d H:i:s',time()-900);
        $trxs = Transaction::select('id_transaction')->where([
            'trasaction_payment_type' => 'Ipay88',
            'transaction_payment_status' => 'Pending'
        ])->where('transaction_date','<',$max_time)->take(50)->pluck('id_transaction');
        foreach ($trxs as $id_trx) {
            $trx_ipay = TransactionPaymentIpay88::where('id_transaction',$id_trx)->first();
            $update = \Modules\IPay88\Lib\IPay88::create()->update($trx_ipay?:$id_trx,[
                'type' =>'trx',
                'Status' => '0',
                'requery_response' => 'Cancelled by cron'
            ],false,false);
        }
    }

    public function autoReject()
    {
        $log = MyHelper::logCron('Auto Reject Order');
        try {
            $minutes = (int) MyHelper::setting('auto_reject_time','value', 15)*60;
            $max_time = date('Y-m-d H:i:s',time()-$minutes);

            $trxs = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                ->where('transactions.transaction_payment_status','Completed')
                ->whereNull('receive_at')
                ->whereNull('reject_at')
                ->whereNull('taken_by_system_at')
                ->whereDate('transactions.transaction_date',date('Y-m-d'))
                ->where('transaction_date','<',$max_time)
                ->get();

            $reason = 'auto reject order by system';
            $post = ['reason' => $reason, 'id_transaction' => 0];
            $result = ['error' => 0 , 'success' => 0];
            foreach ($trxs as $trx) {
                $reject_type = 'point';
                DB::beginTransaction();
                $post['id_transaction'] = $trx->id_transaction;
                $order = $trx;
                $outlet = Outlet::where('id_outlet',$order->id_outlet)->first();
                $user = User::where('id', $order['id_user'])->first();
                if(!$user || !$outlet) {
                    $result['error']++;
                    continue;
                }
                $user = $user->toArray();

                $getLogFraudDay = FraudDetectionLogTransactionDay::whereRaw('Date(fraud_detection_date) ="' . date('Y-m-d', strtotime($order->transaction_date)) . '"')
                    ->where('id_user', $order->id_user)
                    ->first();
                if ($getLogFraudDay) {
                    $checkCount = $getLogFraudDay['count_transaction_day'] - 1;
                    if ($checkCount <= 0) {
                        $delLogTransactionDay = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day', $getLogFraudDay['id_fraud_detection_log_transaction_day'])
                            ->delete();
                    } else {
                        $updateLogTransactionDay = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day', $getLogFraudDay['id_fraud_detection_log_transaction_day'])->update([
                            'count_transaction_day' => $checkCount,
                            'updated_at'            => date('Y-m-d H:i:s'),
                        ]);
                    }

                }

                $getLogFraudWeek = FraudDetectionLogTransactionWeek::where('fraud_detection_week', date('W', strtotime($order->transaction_date)))
                    ->where('fraud_detection_week', date('Y', strtotime($order->transaction_date)))
                    ->where('id_user', $order->id_user)
                    ->first();
                if ($getLogFraudWeek) {
                    $checkCount = $getLogFraudWeek['count_transaction_week'] - 1;
                    if ($checkCount <= 0) {
                        $delLogTransactionWeek = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week', $getLogFraudWeek['id_fraud_detection_log_transaction_week'])
                            ->delete();
                    } else {
                        $updateLogTransactionWeek = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week', $getLogFraudWeek['id_fraud_detection_log_transaction_week'])->update([
                            'count_transaction_week' => $checkCount,
                            'updated_at'             => date('Y-m-d H:i:s'),
                        ]);
                    }
                }


                $rejectBalance = false;
                $point = 0;
                //refund ke balance
                // if($order['trasaction_payment_type'] == "Midtrans"){
                $multiple = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
                if ($multiple) {
                    foreach ($multiple as $pay) {
                        if ($pay['type'] == 'Balance') {
                            $payBalance = TransactionPaymentBalance::find($pay['id_payment']);
                            if ($payBalance) {
                                $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    $result['error']++;
                                    continue 2;
                                }
                                $rejectBalance = true;
                            }
                        } elseif ($pay['type'] == 'Ovo') {
                            $payOvo = TransactionPaymentOvo::find($pay['id_payment']);
                            if ($payOvo) {
                                if(Configs::select('is_active')->where('config_name','refund ovo')->pluck('is_active')->first()){
                                    $point = 0;
                                    $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
                                        ->join('transactions','transactions.id_transaction','=','transaction_payment_ovos.id_transaction')
                                        ->first();
                                    $refund = Ovo::Void($transaction);
                                    $reject_type = 'refund';
                                    if ($refund['status_code'] != '200') {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                }else{
                                    $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                                    if ($refund == false) {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                    $rejectBalance = true;
                                }
                            }
                        } elseif (strtolower($pay['type']) == 'ipay88') {
                            $point = 0;
                            $payIpay = TransactionPaymentIpay88::find($pay['id_payment']);
                            if ($payIpay) {
                                if(strtolower($payIpay['payment_method']) == 'ovo' && MyHelper::setting('refund_ipay88')){
                                    $refund = \Modules\IPay88\Lib\IPay88::create()->void($payIpay);
                                    $reject_type = 'refund';
                                    if (!$refund) {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                }else{
                                    $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payIpay['amount']/100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                                    if ($refund == false) {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                    $rejectBalance = true;
                                }
                            }
                        } elseif (strtolower($pay['type']) == 'shopeepay') {
                            $point = 0;
                            $payShopeepay = TransactionPaymentShopeePay::find($pay['id_payment']);
                            if ($payShopeepay) {
                                if(MyHelper::setting('refund_shopeepay')) {
                                    $refund = app($this->shopeepay)->void($payShopeepay['id_transaction'], 'trx', $errors);
                                    $reject_type = 'refund';
                                    if (!$refund) {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                }else{
                                    $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payShopeepay['amount']/100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                                    if ($refund == false) {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                    $rejectBalance = true;
                                }
                            }
                        } else {
                            $point = 0;
                            $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                            if ($payMidtrans) {
                                if(MyHelper::setting('refund_midtrans')){
                                    $refund = Midtrans::refund($payMidtrans['vt_transaction_id'],['reason' => $post['reason']??'']);
                                    $reject_type = 'refund';
                                    if ($refund['status'] != 'success') {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                } else {
                                    $refund = app($this->balance)->addLogBalance( $order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                                    if ($refund == false) {
                                        DB::rollback();
                                        $result['error']++;
                                        continue 2;
                                    }
                                    $rejectBalance = true;
                                }
                            }
                        }
                    }
                } else {
                    $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $order['id_transaction'])->first();
                    $payOvo      = TransactionPaymentOvo::where('id_transaction', $order['id_transaction'])->first();
                    $payIpay     = TransactionPaymentIpay88::where('id_transaction', $order['id_transaction'])->first();
                    if ($payMidtrans) {
                        $point = 0;
                        if(MyHelper::setting('refund_midtrans')){
                            $refund = Midtrans::refund($payMidtrans['vt_transaction_id'],['reason' => $post['reason']??'']);
                            $reject_type = 'refund';
                            if ($refund['status'] != 'success') {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                        } else {
                            $refund = app($this->balance)->addLogBalance( $order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                            $rejectBalance = true;
                        }
                    } elseif ($payOvo) {
                        if(Configs::select('is_active')->where('config_name','refund ovo')->pluck('is_active')->first()){
                            $point = 0;
                            $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
                                ->join('transactions','transactions.id_transaction','=','transaction_payment_ovos.id_transaction')
                                ->first();
                            $refund = Ovo::Void($transaction);
                            $reject_type = 'refund';
                            if ($refund['status_code'] != '200') {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                        }else{
                            $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                            $rejectBalance = true;
                        }
                    } elseif ($payIpay) {
                        if(strtolower($payIpay['payment_method']) == 'ovo' && MyHelper::setting('refund_ipay88')){
                            $refund = \Modules\IPay88\Lib\IPay88::create()->void($payIpay);
                            $reject_type = 'refund';
                            if (!$refund) {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                        }else{
                            $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payIpay['amount']/100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                            $rejectBalance = true;
                        }
                    } else {
                        $payBalance = TransactionPaymentBalance::where('id_transaction', $order['id_transaction'])->first();
                        if ($payBalance) {
                            $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                $result['error']++;
                                continue;
                            }
                            $rejectBalance = true;
                        }
                    }
                    
                }
                // }
                // delete promo campaign report
                if ($order->id_promo_campaign_promo_code)
                {
                    $update_promo_report = app($this->promo_campaign)->deleteReport($order->id_transaction, $order->id_promo_campaign_promo_code);
                }
                // return voucher
                $update_voucher = app($this->voucher)->returnVoucher($order->id_transaction);

                // return subscription
                $update_subscription = app($this->subscription)->returnSubscription($order->id_transaction);

                //reject order
                $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                    'reject_at'     => date('Y-m-d H:i:s'),
                    'reject_type'   => $reject_type,
                    'reject_reason' => $reason,
                ]);

                if(!$pickup) {
                    DB::rollback();
                    $result['error']++;
                    continue;
                }

                DB::commit();

                //send notif to customer
                $send = app($this->autocrm)->SendAutoCRM('Order Reject', $user['phone'], [
                    "outlet_name"      => $outlet['outlet_name'],
                    "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                    "transaction_date" => $order->transaction_date,
                    'id_transaction'   => $order->id_transaction,
                    'order_id'         => $order->order_id,
                ]);
                if ($send != true) {
                    DB::rollback();
                    $result['error']++;
                    continue;
                }

                //send notif point refund
                if($rejectBalance == true){
                    $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'],
                    [
                        "outlet_name"      => $outlet['outlet_name'],
                        "transaction_date" => $order['transaction_date'],
                        'id_transaction'   => $order['id_transaction'],
                        'receipt_number'   => $order['transaction_receipt_number'],
                        'received_point'   => (string) $point,
                        'order_id'         => $order->order_id,
                    ]);
                    if ($send != true) {
                        DB::rollback();
                        $result['error']++;
                        continue;
                    }
                }
                $result['success']++;
            }
            $log->success($result);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
}
