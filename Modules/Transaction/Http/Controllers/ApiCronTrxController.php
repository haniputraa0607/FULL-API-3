<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Xendit\Entities\TransactionPaymentXendit;
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

            $getTrx = TransactionGroup::where('transaction_payment_status', 'Pending')
                ->whereNotNull('transaction_payment_type')
                ->where('transaction_group_date', '<=', $expired)->get();

            if (empty($getTrx)) {
                $log->success('empty');
                return response()->json(['empty']);
            }

            $count = 0;
            foreach ($getTrx as $key => $singleTrx) {
                $payment_type = $singleTrx->transaction_payment_type;
                if ($payment_type == 'Balance') {
                    $multi_payment = TransactionMultiplePayment::select('type')->where('id_transaction_group', $singleTrx->id_transaction_group)->pluck('type')->toArray();
                    foreach ($multi_payment as $pm) {
                        if ($pm != 'Balance') {
                            $payment_type = $pm;
                            break;
                        }
                    }
                }

                $user = User::where('id', $singleTrx->id_user)->first();
                if (empty($user)) {
                    continue;
                }
                if($payment_type == 'Midtrans') {
                    $dtMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $singleTrx->id_transaction_group)->first();

                    if(empty($dtMidtrans)){
                        continue;
                    }

                    $paymentMethod = str_replace(" ","_",$dtMidtrans['payment_type']);
                    $configTime = config('payment_method.midtrans_'.strtolower($paymentMethod).'.refund_time');
                    $configTime = (int)$configTime;;
                    $trxDate = strtotime($singleTrx->transaction_group_date);
                    $currentDate = strtotime(date('Y-m-d H:i:s'));
                    $mins = ($currentDate - $trxDate) / 60;
                    if($mins < $configTime){
                        continue;
                    }

                    $midtransStatus = Midtrans::status($singleTrx->transaction_group_date);
                    if(!empty($midtransStatus['status_code']) && $midtransStatus['status_code'] == 200){
                        $singleTrx->triggerPaymentCompleted();
                        continue;
                    }elseif ((($midtransStatus['status'] ?? false) == 'fail' && ($midtransStatus['messages'][0] ?? false) == 'Midtrans payment not found') || in_array(($midtransStatus['response']['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure']) || ($midtransStatus['status_code'] ?? false) == '404' ||
                        (!empty($midtransStatus['payment_type']) && $midtransStatus['payment_type'] == 'gopay' && $midtransStatus['transaction_status'] == 'pending')) {
                        $connectMidtrans = Midtrans::expire($singleTrx->transaction_receipt_number);

                        if(!$connectMidtrans){
                            continue;
                        }
                    }
                }elseif ($payment_type == 'Xendit'){
                    $dtXendit = TransactionPaymentXendit::where('id_transaction_group', $singleTrx->id_transaction_group)->first();
                    if(empty($dtXendit['xendit_id'])){
                        continue;
                    }

                    $paymentMethod = str_replace(" ","_",$dtXendit['type']);
                    $configTime = config('payment_method.xendit_'.strtolower($paymentMethod).'.refund_time');
                    $configTime = (int)$configTime;
                    $trxDate = strtotime($singleTrx->transaction_group_date);
                    $currentDate = strtotime(date('Y-m-d H:i:s'));
                    $mins = ($currentDate - $trxDate) / 60;
                    if($mins < $configTime){
                        continue;
                    }

                    $status = app('Modules\Xendit\Http\Controllers\XenditController')->checkStatus($dtXendit->xendit_id, $dtXendit->type);
                    if ($status && $status['status'] == 'PENDING') {
                        $cancel = app('Modules\Xendit\Http\Controllers\XenditController')->expireInvoice($dtXendit['xendit_id']);
                        if(!$cancel){
                            continue;
                        }
                    }elseif($status && ($status['status'] == 'COMPLETED' || $status['status'] == 'PAID')){
                        $singleTrx->triggerPaymentCompleted();
                        continue;
                    }else{
                        continue;
                    }
                }

                $singleTrx->triggerPaymentCancelled();
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

                                if(!empty($setting['email_reply_to']) && !empty($setting['email_reply_to_name'])){
                                    $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                                }else if(!empty($setting['email_reply_to'])){
                                    $message->replyTo($setting['email_reply_to']);
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
            $trxs = Transaction::whereDate('transaction_date', '>=', date('Y-m-d', strtotime('yesterday')))
                ->where('trasaction_type', 'Pickup Order')
                ->join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
                ->where('transaction_payment_status', 'Completed')
                ->whereNotNull('receive_at')
                ->whereNull('taken_at')
                ->whereNull('reject_at')
                ->whereNull('taken_by_system_at')
                ->where('transaction_pickups.pickup_at', '<=', date('Y-m-d H:i:s'))
                ->with('outlet')
                ->get();
            $idTrx = [];
            // reject when not ready
            $processed = [
                'rejected' => 0,
                'failed_reject' => 0,
                'errors' => []
            ];

            $shared = \App\Lib\TemporaryDataManager::create('reject_order');
            $shared['reject_batch'] = true;
            $shared['void_failed'] = collect([]);
            
            foreach ($trxs as $newTrx) {
                $idTrx[] = $newTrx->id_transaction;
                if(
                    !empty($newTrx->ready_at) || //   has been marked ready   or 
                    $newTrx->transaction_payment_status != 'Completed'// payment status not complete  or
                ){
                    // continue without reject
                    continue;
                }

                $transaction = $newTrx;
                // reject order
                $params = [
                    'order_id' => $transaction['order_id'],
                    'reason'   => 'auto reject order by system [not ready]'
                ];
                // mocking request object and create fake request
                $fake_request = new \Modules\OutletApp\Http\Requests\DetailOrder();
                $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
                $fake_request->merge(['user' => $transaction->outlet]);
                $fake_request->setUserResolver(function () use ($transaction) {
                    return $transaction->outlet;
                });

                $reject = app('Modules\OutletApp\Http\Controllers\ApiOutletApp')->rejectOrder($fake_request, date('Y-m-d', strtotime($newTrx->transaction_date)));

                if ($reject instanceof \Illuminate\Http\JsonResponse || $reject instanceof \Illuminate\Http\Response) {
                    $reject = $reject->original;
                }

                if (is_array($reject)) {
                    if (($reject['status'] ?? false) == 'success') {
                        $processed['rejected']++;
                    } else {
                        // taken
                        if (($reject['should_taken'] ?? false) === true) {
                            TransactionPickup::where('id_transaction', $newTrx->id_transaction)
                                        ->update(['taken_by_system_at' => date('Y-m-d H:i:s')]);
                            \App\Jobs\UpdateQuestProgressJob::dispatch($newTrx->id_transaction)->onConnection('quest');
                        }
                        $processed['failed_reject']++;
                        $processed['errors'][] = $reject['messages'] ?? 'Something went wrong';
                    }
                }
            }

            if ($shared['void_failed']->count()) {
                $variables = [
                    'detail' => view('emails.failed_refund', ['transactions' => $shared['void_failed']])->render()
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $shared['void_failed'][0]['phone'], $variables, null, true);
            }

            // // apply point if ready_at null
            // foreach ($trxs as $newTrx) {
            //     $idTrx[] = $newTrx->id_transaction;
            //     if(
            //         !empty($newTrx->ready_at) || //   has been marked ready   or 
            //         $newTrx->transaction_payment_status != 'Completed' || // payment status not complete  or
            //         $newTrx->cashback_insert_status || // cashback has been given   or
            //         $newTrx->pickup_by != 'Customer' // not pickup by the customer
            //     ){
            //         // continue without add cashback
            //         continue;
            //     }
            //     $newTrx->load('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers','promo_campaign_promo_code','promo_campaign_promo_code.promo_campaign');

            //     $checkType = TransactionMultiplePayment::where('id_transaction', $newTrx->id_transaction)->get()->toArray();
            //     $column = array_column($checkType, 'type');

            //     $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';

            //     MyHelper::updateFlagTransactionOnline($newTrx, 'success', $newTrx->user);
            //     if ((!in_array('Balance', $column) || $use_referral) && $newTrx->user) {

            //         $promo_source = null;
            //         if ( $newTrx->id_promo_campaign_promo_code || $newTrx->transaction_vouchers || $use_referral) 
            //         {
            //             if ( $newTrx->id_promo_campaign_promo_code ) {
            //                 $promo_source = 'promo_code';
            //             }
            //             elseif ( ($newTrx->transaction_vouchers[0]->status??false) == 'success' )
            //             {
            //                 $promo_source = 'voucher_online';
            //             }
            //         }

            //         if( app($this->trx)->checkPromoGetPoint($promo_source) || $use_referral)
            //         {
            //             $savePoint = app($this->getNotif)->savePoint($newTrx);
            //         }
            //     }
            //     $newTrx->update(['cashback_insert_status' => 1]);

            //     if ($newTrx->user) {
            //         //check achievement
            //         AchievementCheck::dispatch(['id_transaction' => $newTrx->id_transaction, 'phone' => $newTrx->user->phone])->onConnection('achievement');
            //     }
            
            // }
            //update taken_by_sistem_at
            $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                        ->whereNotNull('ready_at')
                                        ->update(['taken_by_system_at' => date('Y-m-d H:i:s')]);
            foreach ($idTrx as $id_trx) {
                \App\Jobs\UpdateQuestProgressJob::dispatch($id_trx)->onConnection('quest');
            }
            \App\Jobs\UpdateQuestProgressJob::dispatch($newTrx->id_transaction)->onConnection('quest');

            //change status transaction to invalid transaction
            $dataTrx = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                ->join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions as dot', 'dot.id_transaction', 'transactions.id_transaction')
                ->whereDate('transaction_date', '<', date('Y-m-d'))
                ->where('trasaction_type', 'Pickup Order')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('receive_at')
                ->whereNull('reject_at')
                ->where(function ($q){
                    $q->whereNotNull('transaction_pickups.taken_at')
                        ->orWhereNotNull('transaction_pickups.taken_by_system_at');
                })
                ->whereNull('transactions.transaction_flag_invalid')
                ->whereNull('dot.id_disburse_outlet')
                ->pluck('transactions.id_transaction')->toArray();

            if(!empty($dataTrx)){
                $updateTrx = Transaction::whereIn('id_transaction', $dataTrx)
                    ->update(['transaction_flag_invalid' => 'Invalid']);
                if($updateTrx){
                    $dtLog = [];
                    foreach ($dataTrx as $idTransaction){
                        $dtLog[] = [
                            'id_transaction' => $idTransaction,
                            'reason' => 'failed reject [by system]',
                            'tansaction_flag' => 'Invalid',
                            'updated_date' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    LogInvalidTransaction::insert($dtLog);
                }
            }

            $log->success(['success', 'reject' => $processed]);
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
                ->with('outlet')
                ->whereDate('transactions.transaction_date', '>=', date('Y-m-d', strtotime('yesterday')))
                ->where(function($query) use ($max_time) {
                    $query->where(function($query2) use ($max_time) {
                        $query2->whereNotNull('completed_at')->where('completed_at', '<', $max_time);
                    })
                    ->orWhere(function($query2) use ($max_time) {
                        $query2->whereNull('completed_at')->where('transaction_date', '<', $max_time);
                    });
                })
                ->get();

            $processed = [
                'rejected' => 0,
                'failed_reject' => 0,
                'errors' => []
            ];

            $shared = \App\Lib\TemporaryDataManager::create('reject_order');
            $shared['reject_batch'] = true;
            $shared['void_failed'] = collect([]);
            
            foreach ($trxs as $transaction) {
                $params = [
                    'order_id' => $transaction['order_id'],
                    'reason'   => 'auto reject order by system'
                ];
                // mocking request object and create fake request
                $fake_request = new \Modules\OutletApp\Http\Requests\DetailOrder();
                $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
                $fake_request->merge(['user' => $transaction->outlet]);
                $fake_request->setUserResolver(function () use ($transaction) {
                    return $transaction->outlet;
                });

                $reject = app('Modules\OutletApp\Http\Controllers\ApiOutletApp')->rejectOrder($fake_request, date('Y-m-d', strtotime($transaction->transaction_date)));

                if ($reject instanceof \Illuminate\Http\JsonResponse || $reject instanceof \Illuminate\Http\Response) {
                    $reject = $reject->original;
                }

                if (is_array($reject)) {
                    if (($reject['status'] ?? false) == 'success') {
                        $processed['rejected']++;
                    } else {
                        $processed['failed_reject']++;
                        $processed['errors'][] = $reject['messages'] ?? 'Something went wrong';
                    }
                }
            }

            if ($shared['void_failed']->count()) {
                $variables = [
                    'detail' => view('emails.failed_refund', ['transactions' => $shared['void_failed']])->render()
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $shared['void_failed'][0]['phone'], $variables, null, true);
            }

            $log->success($processed);
            return $processed;
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }

    /**
     * Reject transaction not ready where transaction not ready after pickup_at + 10 minutes
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function autoRejectReady()
    {
        $log = MyHelper::logCron('Auto Reject Not Ready');
        try {
            $max_pickup = 600; // 10 minutes
            $trxs = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->where('transaction_payment_status', 'Completed')
                ->where('pickup_by', 'Customer')
                ->whereNotNull('receive_at')
                ->whereNull('ready_at')
                ->whereNull('taken_at')
                ->whereNull('taken_by_system_at')
                ->whereNull('reject_at')
                ->with('outlet')
                ->where('pickup_at', '<', date('Y-m-d H:i:s', time() - $max_pickup))
                ->get();

            $processed = [
                'rejected' => 0,
                'failed_reject' => 0,
                'errors' => []
            ];

            $shared = \App\Lib\TemporaryDataManager::create('reject_order');
            $shared['reject_batch'] = true;
            $shared['void_failed'] = collect([]);

            foreach ($trxs as $transaction) {
                $params = [
                    'order_id' => $transaction['order_id'],
                    'reason'   => 'auto reject order by system [not ready]'
                ];
                // mocking request object and create fake request
                $fake_request = new \Modules\OutletApp\Http\Requests\DetailOrder();
                $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
                $fake_request->merge(['user' => $transaction->outlet]);
                $fake_request->setUserResolver(function () use ($transaction) {
                    return $transaction->outlet;
                });

                $reject = app('Modules\OutletApp\Http\Controllers\ApiOutletApp')->rejectOrder($fake_request, date('Y-m-d', strtotime($transaction->transaction_date)));

                if ($reject instanceof \Illuminate\Http\JsonResponse || $reject instanceof \Illuminate\Http\Response) {
                    $reject = $reject->original;
                }

                if (is_array($reject)) {
                    if (($reject['status'] ?? false) == 'success') {
                        $dataNotif = [
                            'subject' => 'Order ditolak oleh sistem',
                            'string_body' => 'Outlet tidak merespon order JIWA+',
                            'type' => 'trx',
                            'id_reference'=> $transaction['id_transaction']
                        ];
                        app('Modules\OutletApp\Http\Controllers\ApiOutletApp')->outletNotif($dataNotif,$transaction->id_outlet);
                        $processed['rejected']++;
                    } else {
                        $processed['failed_reject']++;
                        $processed['errors'][] = $reject['messages'] ?? 'Something went wrong';
                    }
                }
            }

            if ($shared['void_failed']->count()) {
                $variables = [
                    'detail' => view('emails.failed_refund', ['transactions' => $shared['void_failed']])->render()
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $shared['void_failed'][0]['phone'], $variables, null, true);
            }

            $log->success($processed);
            return $processed;
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            return ['status' => 'fail', 'messages' => [$e->getMessage()]];
        }
    }

    /**
     * Set ready transaction not ready where transaction not ready after pickup_at - 5 minutes
     * @return array        result
     */
    public function autoReadyOrder()
    {
        $log = MyHelper::logCron('Auto Ready Order');
        try {
            $max_pickup = 300; // 5 minutes
            $trxs = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->where('transaction_payment_status', 'Completed')
                ->where('pickup_by', 'Customer')
                ->whereNotNull('receive_at')
                ->whereNull('ready_at')
                ->whereNull('taken_at')
                ->whereNull('taken_by_system_at')
                ->whereNull('reject_at')
                ->with('outlet')
                ->where('pickup_at', '<', date('Y-m-d H:i:s', time() - $max_pickup))
                ->get();

            $processed = [
                'found' => $trxs->count(),
                'setready' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($trxs as $transaction) {
                $params = [
                    'order_id' => $transaction['order_id']
                ];
                // mocking request object and create fake request
                $fake_request = new \Modules\OutletApp\Http\Requests\DetailOrder();
                $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
                $fake_request->merge(['user' => $transaction->outlet]);
                $fake_request->setUserResolver(function () use ($transaction) {
                    return $transaction->outlet;
                });

                $reject = app('Modules\OutletApp\Http\Controllers\ApiOutletApp')->SetReady($fake_request, true);

                if ($reject instanceof \Illuminate\Http\JsonResponse || $reject instanceof \Illuminate\Http\Response) {
                    $reject = $reject->original;
                }

                if (is_array($reject)) {
                    if (($reject['status'] ?? false) == 'success') {
                        $processed['setready']++;
                    } else {
                        $processed['failed']++;
                        $processed['errors'][] = $reject['messages'] ?? 'Something went wrong';
                    }
                }
            }

            $log->success($processed);
            return $processed;
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            return ['status' => 'fail', 'messages' => [$e->getMessage()]];
        }
    }
}
