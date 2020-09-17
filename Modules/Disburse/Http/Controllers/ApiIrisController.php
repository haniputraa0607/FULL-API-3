<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\DailyReportTrx;
use App\Http\Models\Deal;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionBalance;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Jobs\DisburseJob;
use Cassandra\Exception\ExecutionException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\Disburse;

use DB;
use Modules\Disburse\Entities\DisburseOutlet;
use Modules\Disburse\Entities\DisburseOutletTransaction;
use Modules\Disburse\Entities\DisburseTransaction;
use Modules\Disburse\Entities\LogIRIS;
use Modules\Disburse\Entities\MDR;
use Modules\Disburse\Entities\UserFranchisee;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;

use DOMDocument;
use Illuminate\Support\Facades\Log;
class ApiIrisController extends Controller
{
    public function notification(Request $request){
        $post = $request->json()->all();
        $reference_no = $post['reference_no'];
        $status = $post['status'];

        $arrStatus = [
            'queued' => 'Queued',
            'processed' => 'Processed',
            'completed' => 'Success',
            'failed' => 'Fail',
            'rejected' => 'Rejected',
            'approved' => 'Approved'
        ];
        $data = Disburse::where('reference_no', $reference_no)->update(['disburse_status' => $arrStatus[$status]??NULL,
            'error_code' => $post['error_code']??null, 'error_message' => $post['error_message']??null]);

        $dataLog = [
            'subject' => 'Callback IRIS',
            'id_reference' => $post['reference_no']??null,
            'request'=> json_encode($post)
        ];

        if($data){
            $dataLog['response'] = json_encode(['status' => 'success']);
            LogIRIS::create($dataLog);
            return response()->json(['status' => 'success']);
        }else{
            $dataLog['response'] = json_encode(['status' => 'fail', 'messages' => ['Failed Update status']]);
            LogIRIS::create($dataLog);
            return response()->json(['status' => 'fail',
                'messages' => ['Failed Update status']]);
        }
    }

    public function disburse(){
        $log = MyHelper::logCron('Disburse');
        try {
            $getSettingGlobalTimeToSent = Setting::where('key', 'disburse_global_setting_time_to_sent')->first();

            if($getSettingGlobalTimeToSent && $getSettingGlobalTimeToSent['value'] !== ""){
                /*
                 -first check current date is holiday or not
                 -today is holiday when return false
                 -today is not holiday when return true
                 -cron runs on weekdays
                */
                $currentDate = date('Y-m-d');
                $day = date('D', strtotime($currentDate));
                $getHoliday = $this->getHoliday();

                if($day != 'Sat' && $day != 'Sun' && array_search($currentDate, $getHoliday) === false){
                    $time = $getSettingGlobalTimeToSent['value'];
                    $getDateForQuery = $this->getDateForQuery($time, $getHoliday);
                    $dateForQuery = $getDateForQuery;

                    $getData = Transaction::join('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
                        ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                        ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                        ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                        ->leftJoin('bank_account_outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                        ->leftJoin('bank_accounts', 'bank_accounts.id_bank_account', 'bank_account_outlets.id_bank_account')
                        ->leftJoin('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
                        ->whereNull('disburse_outlet.id_disburse_outlet')
                        ->whereNotNull('bank_accounts.beneficiary_name')
                        ->where(function ($q){
                            $q->whereNotNull('transaction_pickups.taken_at')
                                ->orWhereNotNull('transaction_pickups.taken_by_system_at');
                        })
                        ->whereDate('transactions.transaction_date', '<', $dateForQuery)
                        ->select('disburse_outlet_transactions.*', 'transaction_shipment_go_send', 'transactions.transaction_date', 'transactions.id_outlet', 'transactions.id_transaction', 'transactions.transaction_subtotal',
                            'transactions.transaction_grandtotal', 'transactions.transaction_discount', 'transactions.id_promo_campaign_promo_code',
                            'bank_name.bank_code', 'outlets.status_franchise', 'outlets.outlet_special_status', 'outlets.outlet_special_fee',
                            'bank_accounts.id_bank_account', 'bank_accounts.beneficiary_name', 'bank_accounts.beneficiary_account', 'bank_accounts.beneficiary_email', 'bank_accounts.beneficiary_alias')
                        ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign', 'transaction_payment_subscription'])
                        ->orderBy('transactions.created_at', 'desc')->get()->toArray();

                    $arrStatus = [
                        'queued' => 'Queued',
                        'processed' => 'Processed',
                        'completed' => 'Success',
                        'failed' => 'Fail',
                        'rejected' => 'Rejected'
                    ];

                    $checkBalance = MyHelper::connectIris('Balance', 'GET','api/v1/balance', []);
                    $balance = 0;
                    if(isset($checkBalance['status']) && $checkBalance['status'] == 'success'){
                        $balance = $checkBalance['response']['balance'];
                    }

                    if(!empty($getData) && $balance > 0){
                        $tmpBalance = 0;

                        DB::beginTransaction();

                        try{
                            $arrTmp = [];
                            $arrTmpDisburse = [];
                            foreach ($getData as $data){

                                if(!is_null($data['beneficiary_account'])){
                                    $amount = $data['income_outlet'];
                                    $incomeCentral = $data['income_central'];
                                    $expenseCentral = $data['expense_central'];
                                    $feeItemForCentral = $data['fee_item'];
                                    $grandTotal = $data['transaction_grandtotal'];
                                    $totalChargedPromo = $data['discount'];
                                    $totalFee = $data['payment_charge'];
                                    $nominalBalance = $data['subscription'];
                                    $totalChargedSubcriptionOutlet =

                                    $transactionShipment = 0;
                                    if(!empty($data['transaction_shipment_go_send'])){
                                        $transactionShipment = $data['transaction_shipment_go_send'];
                                    }
                                    //check balance
                                    $tmpBalance = $tmpBalance + $amount;//for check balance
                                    if($tmpBalance > $balance){
                                        break;
                                    }

                                    //set to send disburse per bank account
                                    $checkAccount = array_search($data['beneficiary_account'], array_column($arrTmpDisburse, 'beneficiary_account'));
                                    if($checkAccount === false){
                                        $arrTmpDisburse[] = [
                                            'beneficiary_name' => $data['beneficiary_name'],
                                            'beneficiary_account' => $data['beneficiary_account'],
                                            'beneficiary_bank' => $data['bank_code'],
                                            'beneficiary_email' => $data['beneficiary_email'],
                                            'beneficiary_alias' => $data['beneficiary_alias'],
                                            'id_bank_account' => $data['id_bank_account'],
                                            'total_amount' => $amount
                                        ];
                                    }else{
                                        $arrTmpDisburse[$checkAccount]['total_amount'] = $arrTmpDisburse[$checkAccount]['total_amount'] + $amount;
                                    }

                                    //set to disburse outlet and disburse outlet transaction
                                    $checkOultet = array_search($data['id_outlet'], array_column($arrTmp, 'id_outlet'));

                                    if($checkOultet === false){
                                        $arrTmp[] = [
                                            'beneficiary_account' => $data['beneficiary_account'],
                                            'id_outlet' => $data['id_outlet'],
                                            'total_amount' => $amount,
                                            'total_income_central' => $incomeCentral,
                                            'total_expense_central' => $expenseCentral,
                                            'total_fee_item' => $feeItemForCentral,
                                            'total_omset' => $grandTotal,
                                            'total_promo_charged' => $totalChargedPromo,
                                            'total_discount' => abs($data['transaction_discount']),
                                            'total_subtotal' => $data['transaction_subtotal'],
                                            'total_delivery_price' => $transactionShipment,
                                            'total_payment_charge' => $totalFee,
                                            'total_point_use_expense' => $nominalBalance,
                                            'total_subscription' => $totalChargedSubcriptionOutlet,
                                            'transactions' => [$data['id_disburse_transaction']]
                                        ];
                                    }else{
                                        $arrTmp[$checkOultet]['total_amount'] = $arrTmp[$checkOultet]['total_amount'] + $amount;
                                        $arrTmp[$checkOultet]['total_income_central'] = $arrTmp[$checkOultet]['total_income_central'] + $incomeCentral;
                                        $arrTmp[$checkOultet]['total_expense_central'] = $arrTmp[$checkOultet]['total_expense_central'] + $expenseCentral;
                                        $arrTmp[$checkOultet]['total_fee_item'] = $arrTmp[$checkOultet]['total_fee_item'] + $feeItemForCentral;
                                        $arrTmp[$checkOultet]['total_omset'] = $arrTmp[$checkOultet]['total_omset'] + $grandTotal;
                                        $arrTmp[$checkOultet]['total_promo_charged'] = $arrTmp[$checkOultet]['total_promo_charged'] + $totalChargedPromo;
                                        $arrTmp[$checkOultet]['total_subtotal'] = $arrTmp[$checkOultet]['total_subtotal'] +  $data['transaction_subtotal'];
                                        $arrTmp[$checkOultet]['total_discount'] = $arrTmp[$checkOultet]['total_discount'] + abs($data['transaction_discount']);
                                        $arrTmp[$checkOultet]['total_delivery_price'] = $arrTmp[$checkOultet]['total_delivery_price'] + $transactionShipment;
                                        $arrTmp[$checkOultet]['total_payment_charge'] = $arrTmp[$checkOultet]['total_payment_charge'] + $totalFee;
                                        $arrTmp[$checkOultet]['total_point_use_expense'] = $arrTmp[$checkOultet]['total_point_use_expense'] + $nominalBalance;
                                        $arrTmp[$checkOultet]['total_subscription'] = $arrTmp[$checkOultet]['total_subscription'] + $totalChargedSubcriptionOutlet;
                                        $arrTmp[$checkOultet]['transactions'][] = $data['id_disburse_transaction'];
                                    }
                                }
                            }

                            $dataToSend = [];
                            $dataToInsert = [];

                            foreach ($arrTmpDisburse as $value){
                                $toSend = [
                                    'beneficiary_name' => $value['beneficiary_name'],
                                    'beneficiary_account' => $value['beneficiary_account'],
                                    'beneficiary_bank' => $value['beneficiary_bank'],
                                    'beneficiary_email' => $value['beneficiary_email'],
                                    'amount' => $value['total_amount'],
                                    'notes' => 'Payment from apps '.date('d M Y'),
                                ];

                                $dataToSend[] = $toSend;

                                $dataToInsert[] = [
                                    'disburse_nominal' => $value['total_amount'],
                                    'id_bank_account' => $value['id_bank_account'],
                                    'beneficiary_name' => $value['beneficiary_name'],
                                    'beneficiary_bank_name' => $value['beneficiary_bank'],
                                    'beneficiary_account_number' => $value['beneficiary_account'],
                                    'beneficiary_email' => $value['beneficiary_email'],
                                    'beneficiary_alias' => $value['beneficiary_alias'],
                                    'notes' => 'Payment from apps '.date('d M Y'),
                                    'request' => json_encode($toSend)
                                ];
                            }

                            foreach ($arrTmp as $val){
                                $checkAccount = array_search($val['beneficiary_account'], array_column($dataToInsert, 'beneficiary_account_number'));
                                if($checkAccount !== false){
                                    $dataToInsert[$checkAccount]['disburse_outlet'][] = [
                                        'id_outlet' => $val['id_outlet'],
                                        'disburse_nominal' => $val['total_amount'],
                                        'total_income_central' => $val['total_income_central'],
                                        'total_expense_central' => $val['total_expense_central'],
                                        'total_fee_item' => $val['total_fee_item'],
                                        'total_omset' => $val['total_omset'],
                                        'total_subtotal' => $val['total_subtotal'],
                                        'total_promo_charged' => $val['total_promo_charged'],
                                        'total_discount' => $val['total_discount'],
                                        'total_delivery_price' => $val['total_delivery_price'],
                                        'total_payment_charge' => $val['total_payment_charge'],
                                        'total_point_use_expense' => $val['total_point_use_expense'],
                                        'total_subscription' => $val['total_subscription'],
                                        'transactions' => $val['transactions']
                                    ];
                                }
                            }

                            $sendToIris = MyHelper::connectIris('Payouts', 'POST','api/v1/payouts', ['payouts' => $dataToSend]);

                            if(isset($sendToIris['status']) && $sendToIris['status'] == 'success'){
                                if(isset($sendToIris['response']['payouts']) && !empty($sendToIris['response']['payouts'])){
                                    $j = 0;
                                    foreach ($sendToIris['response']['payouts'] as $val){
                                        $dataToInsert[$j]['response'] = json_encode($val);
                                        $dataToInsert[$j]['reference_no'] = $val['reference_no'];
                                        $dataToInsert[$j]['disburse_status'] = $arrStatus[$val['status']];

                                        $insertToDisburseOutlet = $dataToInsert[$j]['disburse_outlet'];
                                        unset($dataToInsert[$j]['disburse_outlet']);

                                        $insert = Disburse::create($dataToInsert[$j]);

                                        if($insert){
                                            foreach ($insertToDisburseOutlet as $do){
                                                $do['id_disburse'] = $insert['id_disburse'];
                                                $disburseOutlet = DisburseOutlet::create($do);
                                                if($disburseOutlet){
                                                    DisburseOutletTransaction::whereIn('id_disburse_transaction', $do['transactions'])
                                                            ->update(['id_disburse_outlet' => $disburseOutlet['id_disburse_outlet'], 'updated_at' => date('Y-m-d H:i:s')]);
                                                }
                                            }
                                        }
                                        $j++;
                                    }
                                }
                            }
                            DB::commit();
                        }catch (\Exception $e){
                            DB::rollback();
                            return 'fail';
                        }
                    }

                    //proses retry failed disburse
                    if($balance > 0){
                        $dataRetry = Disburse::join('bank_accounts', 'bank_accounts.id_bank_account', 'disburse.id_bank_account')
                            ->leftJoin('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
                            ->where('disburse_status', 'Retry From Failed')
                            ->select('bank_accounts.beneficiary_name', 'bank_accounts.beneficiary_account', 'bank_name.bank_code as beneficiary_bank', 'bank_accounts.beneficiary_email',
                                'disburse_nominal as amount', 'notes', 'reference_no as ref')
                            ->get()->toArray();

                        if(!empty($dataRetry)){
                            $sendToIris = MyHelper::connectIris('Payouts', 'POST','api/v1/payouts', ['payouts' => $dataRetry]);

                            if(isset($sendToIris['status']) && $sendToIris['status'] == 'success'){
                                if(isset($sendToIris['response']['payouts']) && !empty($sendToIris['response']['payouts'])){
                                    $a=0;
                                    $return = $sendToIris['response']['payouts'];
                                    foreach ($dataRetry as $val){
                                        $getData = Disburse::where('reference_no', $val['ref'])->first();
                                        $oldRefNo = $getData['old_reference_no'].','.$getData['reference_no'];
                                        $oldRefNo = ltrim($oldRefNo,",");
                                        $count = $getData['count_retry'] + 1;
                                        $update = Disburse::where('id_disburse', $getData['id_disburse'])
                                            ->update(['old_reference_no' => $oldRefNo, 'reference_no' => $return[$a]['reference_no'],
                                                'count_retry' => $count, 'disburse_status' => $arrStatus[ $return[$a]['status']]]);
                                        $a++;
                                    }
                                }
                            }
                        }
                    }

                    //proses approve if setting approver by sistem
                    $settingApprover = Setting::where('key', 'disburse_auto_approve_setting')->first();
                    if($settingApprover && $settingApprover['value'] == 1){
                        $getDataToApprove = Disburse::where('disburse_status', 'Queued')
                            ->pluck('reference_no');
                        if(count($getDataToApprove) > 0){
                            $sendApprover = MyHelper::connectIris('Approver', 'POST','api/v1/payouts/approve', ['reference_nos' => $getDataToApprove], 1);
                            if(isset($sendApprover['status']) && $sendApprover['status'] == 'fail'){
                                $getDataToApprove = Disburse::whereIn('reference_no', $getDataToApprove)->update(['disburse_status' => 'Fail', 'error_message' => implode(',',$sendApprover['response']['errors'])]);
                            }
                        }
                    }
                }
            }

            $log->success();
            return 'succes';
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        };
    }

    public function calculationTransaction($id_transaction){
        $check = DisburseOutletTransaction::where('id_transaction', $id_transaction)->first();
        if($check){
            return false;
        }

        $data = Transaction::where('id_transaction', $id_transaction)
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign', 'transaction_payment_subscription'])
            ->first();

        if(!empty($data)){
            $settingGlobalFee = Setting::where('key', 'global_setting_fee')->first()->value_text;
            $settingGlobalFee = json_decode($settingGlobalFee);
            $settingMDRAll = MDR::get()->toArray();
            $subTotal = $data['transaction_subtotal'];
            $grandTotal = $data['transaction_grandtotal'];
            $nominalFeeToCentral = $subTotal;
            $feePGCentral = 0;
            $feePG = 0;
            $feePGType = 'Percent';
            $feePointCentral = 100;
            $feePointOutlet = 0;
            $feePromoCentral = 0;
            $feeSubcriptionCentral = 0;
            $feeSubcriptionOutlet = 0;
            $feePromoOutlet = 0;
            $balanceNominal = 0;
            $nominalBalance = 0;
            $nominalBalanceCentral = 0;
            $totalFeeForCentral = 0;
            $amount = 0;
            $amountMDR = 0 ;
            $transactionShipment = 0;
            if(!empty($data['transaction_shipment_go_send'])){
                $transactionShipment = $data['transaction_shipment_go_send'];
            }

            $charged = NULL;

            if(!empty($data['transaction_multiple_payment'])){

                // ===== Calculate Fee Subscription ====== //
                $totalChargedSubcriptionOutlet = 0;
                $totalChargedSubcriptionCentral = 0;
                $nominalSubscription = 0;
                if(!empty($data['transaction_payment_subscription'])){
                    $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user','subscription_user_vouchers.id_subscription_user')
                        ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                        ->where('subscription_user_vouchers.id_subscription_user_voucher', $data['transaction_payment_subscription']['id_subscription_user_voucher'])
                        ->groupBy('subscriptions.id_subscription')->select('subscriptions.*')->first();
                    if($getSubcription){
                        $nominalSubscription = abs($data['transaction_payment_subscription']['subscription_nominal']);
                        $nominalFeeToCentral = $subTotal - abs($data['transaction_payment_subscription']['subscription_nominal']);
                        $feeSubcriptionCentral = $getSubcription['charged_central'];
                        $feeSubcriptionOutlet = $getSubcription['charged_outlet'];
                        if((int) $feeSubcriptionCentral !== 100){
                            $totalChargedSubcriptionOutlet = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionOutlet / 100));
                            $totalChargedSubcriptionCentral = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionCentral / 100));
                        }else{
                            $totalChargedSubcriptionCentral = $data['transaction_payment_subscription']['subscription_nominal'];
                        }
                    }
                }

                $statusSplitPayment = 0;//for flag if transaction user split payment with balance (point)
                if(count($data['transaction_multiple_payment']) > 1){
                    $statusSplitPayment = 1;
                }

                foreach ($data['transaction_multiple_payment'] as $payments){

                    if(strtolower($payments['type']) == 'midtrans'){
                        $midtrans = TransactionPaymentMidtran::where('id_transaction', $data['id_transaction'])->first();
                        $payment = $midtrans['payment_type'];
                        if($statusSplitPayment == 1){
                            $amountMDR = $grandTotal - $nominalSubscription;
                        }else{
                            $amountMDR = $midtrans['gross_amount'];
                        }

                        $keyMidtrans = array_search(strtoupper($payment), array_column($settingMDRAll, 'payment_name'));
                        if($keyMidtrans !== false){
                            $feePGCentral = $settingMDRAll[$keyMidtrans]['mdr_central'];
                            $feePG = $settingMDRAll[$keyMidtrans]['mdr'];
                            $feePGType = $settingMDRAll[$keyMidtrans]['percent_type'];
                            $charged = $settingMDRAll[$keyMidtrans]['charged'];
                        }else{
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }

                    }elseif (strtolower($payments['type']) == 'balance'){
                        $balanceNominal = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first()->balance_nominal;

                        if($statusSplitPayment == 0){
                            $balanceMdr = array_search(strtoupper('FULL POINT'), array_column($settingMDRAll, 'payment_name'));
                            if($balanceMdr !== false){
                                $feePGCentral = $settingMDRAll[$balanceMdr]['mdr_central'];
                                $feePG = $settingMDRAll[$balanceMdr]['mdr'];
                                $feePGType = $settingMDRAll[$balanceMdr]['percent_type'];
                                $charged = $settingMDRAll[$balanceMdr]['charged'];

                                $feePointOutlet = (float)$settingMDRAll[$balanceMdr]['mdr'];
                                $feePointCentral = 100 - $feePointOutlet;

                                if((int)$feePointOutlet === 0){
                                    //calculate charged point to central
                                    $nominalBalanceCentral = $balanceNominal;
                                }else{
                                    //calculate charged point to outlet
                                    $nominalBalance = $balanceNominal * (floatval($feePointOutlet) / 100);

                                    //calculate charged point to central
                                    $nominalBalanceCentral = $balanceNominal * (floatval($feePointCentral) / 100);
                                }
                            }else{
                                DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                                return true;
                            }
                        }else{
                            $feePointCentral = 100;
                            $nominalBalanceCentral = $balanceNominal;
                        }
                    }elseif(strtolower($payments['type']) == 'ipay88'){
                        $ipay88 = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first();
                        if($statusSplitPayment == 1){
                            $amountMDR = $grandTotal - $nominalSubscription;
                        }else{
                            $amountMDR = $ipay88['amount']/100;
                        }

                        $method =  $ipay88['payment_method'];
                        $keyipay88 = array_search(strtoupper($method), array_column($settingMDRAll, 'payment_name'));
                        if($keyipay88 !== false){
                            $feePGCentral = $settingMDRAll[$keyipay88]['mdr_central'];
                            $feePG = $settingMDRAll[$keyipay88]['mdr'];
                            $feePGType = $settingMDRAll[$keyipay88]['percent_type'];
                            $charged = $settingMDRAll[$keyipay88]['charged'];
                        }else{
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }
                    }elseif (strtolower($payments['type']) == 'ovo'){
                        $ovo = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first();

                        if($statusSplitPayment == 1){
                            $amountMDR = $grandTotal - $nominalSubscription;
                        }else{
                            $amountMDR = $ovo['amount']/100;
                        }

                        $keyipayOvo = array_search('OVO', array_column($settingMDRAll, 'payment_name'));
                        if($keyipayOvo !== false){
                            $feePGCentral = $settingMDRAll[$keyipayOvo]['mdr_central'];
                            $feePG = $settingMDRAll[$keyipayOvo]['mdr'];
                            $feePGType = $settingMDRAll[$keyipayOvo]['percent_type'];
                            $charged = $settingMDRAll[$keyipayOvo]['charged'];
                        }else{
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }
                    }
                }

                $totalChargedPromo = 0;
                $totalChargedPromoCentral = 0;
                if(count($data['vouchers']) > 0){
                    $getDeal = Deal::where('id_deals', $data['vouchers'][0]['id_deals'])->first();
                    $feePromoCentral = $getDeal['charged_central'];
                    $feePromoOutlet = $getDeal['charged_outlet'];
                    $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                    if((int) $feePromoCentral !== 100){
                        $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                        $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                    }else{
                        $totalChargedPromoCentral = $data['transaction_discount'];
                    }
                }elseif (!empty($data['promo_campaign'])){
                    $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                    $feePromoCentral = $data['promo_campaign']['charged_central'];
                    $feePromoOutlet = $data['promo_campaign']['charged_outlet'];
                    if((int) $feePromoCentral !== 100){
                        $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                        $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                    }else{
                        $totalChargedPromoCentral = $data['transaction_discount'];
                    }
                }

                //fee payment gateway
                if($feePGType == 'Percent'){
                    $totalFee = $amountMDR * (($feePGCentral + $feePG) / 100);
                    $totalFeeForCentral = $amountMDR * ($feePGCentral/100);
                }else{
                    $totalFee = $feePGCentral + $feePG;
                    $totalFeeForCentral = $feePGCentral;
                }

                $percentFee = 0;
                if($data['outlet_special_status'] == 1){
                    $percentFee = $data['outlet_special_fee'];
                }else{
                    if($data['status_franchise'] == 1){
                        $percentFee = ($settingGlobalFee->fee_outlet == '' ? 0 : $settingGlobalFee->fee_outlet);
                    }else{
                        $percentFee = ($settingGlobalFee->fee_central == '' ? 0 : $settingGlobalFee->fee_central);
                    }
                }

                $feeItemForCentral = (floatval($percentFee) / 100) * $nominalFeeToCentral;

                $amount = round($subTotal - ((floatval($percentFee) / 100) * $nominalFeeToCentral) - $totalFee - $nominalBalance - $totalChargedPromo - $totalChargedSubcriptionOutlet, 2);
                $incomeCentral = round(((floatval($percentFee) / 100) * $nominalFeeToCentral) + $totalFeeForCentral, 2);
                $expenseCentral = round($nominalBalanceCentral + $totalChargedPromoCentral + $totalChargedSubcriptionCentral, 2);

                $dataInsert = [
                    'id_transaction' => $data['id_transaction'],
                    'income_outlet'=> $amount,
                    'income_central'=> $incomeCentral,
                    'expense_central'=> $expenseCentral,
                    'fee_item' => $feeItemForCentral,
                    'discount' => $totalChargedPromo,
                    'payment_charge' => $totalFee,
                    'point_use_expense' => $nominalBalance,
                    'subscription' => $totalChargedSubcriptionOutlet,
                    'fee' => $percentFee,
                    'mdr' => $feePG,
                    'mdr_central' => $feePGCentral,
                    'mdr_charged' => $charged,
                    'mdr_type' => $feePGType,
                    'charged_point_central' => $feePointCentral,
                    'charged_point_outlet' => $feePointOutlet,
                    'charged_promo_central' => $feePromoCentral,
                    'charged_promo_outlet' => $feePromoOutlet,
                    'charged_subscription_central' => $feeSubcriptionCentral,
                    'charged_subscription_outlet' => $feeSubcriptionOutlet,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                Log::info(json_encode($dataInsert));
                $insert = DisburseOutletTransaction::insert($dataInsert);

                return $insert;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function sycnFeeTransaction(Request $request){
        $post = $request->json()->all();
        $date_start = $post['date_start'];
        $date_end = $post['date_end'];

        $datas = Transaction::join('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
            ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
            ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->where(function ($q){
                $q->whereNull('disburse_outlet_transactions.id_disburse_outlet');
                $q->orWhereNull('disburse.disburse_status', '!=', 'Success');
            })
            ->whereDate('transactions.transaction_date', '>=', $date_start)
            ->whereDate('transactions.transaction_date', '<=', $date_end)
            ->select('transactions.*', 'disburse_outlet_transactions.id_disburse_transaction', 'disburse.disburse_status', 'outlets.*')
            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign', 'transaction_payment_subscription'])
            ->get()->toArray();

        if(!empty($datas)){
            foreach ($datas as $data){
                $settingGlobalFee = Setting::where('key', 'global_setting_fee')->first()->value_text;
                $settingGlobalFee = json_decode($settingGlobalFee);
                $settingMDRAll = MDR::get()->toArray();
                $subTotal = $data['transaction_subtotal'];
                $grandTotal = $data['transaction_grandtotal'];
                $nominalFeeToCentral = $subTotal;
                $feePGCentral = 0;
                $feePG = 0;
                $feePGType = 'Percent';
                $feePointCentral = 100;
                $feePointOutlet = 0;
                $feePromoCentral = 0;
                $feeSubcriptionCentral = 0;
                $feeSubcriptionOutlet = 0;
                $feePromoOutlet = 0;
                $balanceNominal = 0;
                $nominalBalance = 0;
                $nominalBalanceCentral = 0;
                $totalFeeForCentral = 0;
                $amount = 0;
                $amountMDR = 0 ;
                $transactionShipment = 0;
                if(!empty($data['transaction_shipment_go_send'])){
                    $transactionShipment = $data['transaction_shipment_go_send'];
                }

                $charged = NULL;

                if(!empty($data['transaction_multiple_payment'])){

                    // ===== Calculate Fee Subscription ====== //
                    $totalChargedSubcriptionOutlet = 0;
                    $totalChargedSubcriptionCentral = 0;
                    $nominalSubscription = 0;
                    if(!empty($data['transaction_payment_subscription'])){
                        $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user','subscription_user_vouchers.id_subscription_user')
                            ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                            ->where('subscription_user_vouchers.id_subscription_user_voucher', $data['transaction_payment_subscription']['id_subscription_user_voucher'])
                            ->groupBy('subscriptions.id_subscription')->select('subscriptions.*')->first();
                        if($getSubcription){
                            $nominalSubscription = abs($data['transaction_payment_subscription']['subscription_nominal']);
                            $nominalFeeToCentral = $subTotal - abs($data['transaction_payment_subscription']['subscription_nominal']);
                            $feeSubcriptionCentral = $getSubcription['charged_central'];
                            $feeSubcriptionOutlet = $getSubcription['charged_outlet'];
                            if((int) $feeSubcriptionCentral !== 100){
                                $totalChargedSubcriptionOutlet = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionOutlet / 100));
                                $totalChargedSubcriptionCentral = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionCentral / 100));
                            }else{
                                $totalChargedSubcriptionCentral = $data['transaction_payment_subscription']['subscription_nominal'];
                            }
                        }
                    }

                    $statusSplitPayment = 0;//for flag if transaction user split payment with balance (point)
                    if(count($data['transaction_multiple_payment']) > 1){
                        $statusSplitPayment = 1;
                    }

                    //process calculation payment gateway
                    foreach ($data['transaction_multiple_payment'] as $payments){

                        if(strtolower($payments['type']) == 'midtrans'){
                            $midtrans = TransactionPaymentMidtran::where('id_transaction', $data['id_transaction'])->first();
                            $payment = $midtrans['payment_type'];
                            if($statusSplitPayment == 1){
                                $amountMDR = $grandTotal - $nominalSubscription;
                            }else{
                                $amountMDR = $midtrans['gross_amount'];
                            }

                            $keyMidtrans = array_search(strtoupper($payment), array_column($settingMDRAll, 'payment_name'));
                            if($keyMidtrans !== false){
                                $feePGCentral = $settingMDRAll[$keyMidtrans]['mdr_central'];
                                $feePG = $settingMDRAll[$keyMidtrans]['mdr'];
                                $feePGType = $settingMDRAll[$keyMidtrans]['percent_type'];
                                $charged = $settingMDRAll[$keyMidtrans]['charged'];
                            }

                        }elseif (strtolower($payments['type']) == 'balance'){
                            $balanceNominal = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first()->balance_nominal;

                            if($statusSplitPayment == 0){
                                $balanceMdr = array_search(strtoupper('FULL POINT'), array_column($settingMDRAll, 'payment_name'));
                                if($balanceMdr !== false){
                                    $feePGCentral = $settingMDRAll[$balanceMdr]['mdr_central'];
                                    $feePG = $settingMDRAll[$balanceMdr]['mdr'];
                                    $feePGType = $settingMDRAll[$balanceMdr]['percent_type'];
                                    $charged = $settingMDRAll[$balanceMdr]['charged'];

                                    $feePointOutlet = (float)$settingMDRAll[$balanceMdr]['mdr'];
                                    $feePointCentral = 100 - $feePointOutlet;

                                    if((int)$feePointOutlet === 0){
                                        //calculate charged point to central
                                        $nominalBalanceCentral = $balanceNominal;
                                    }else{
                                        //calculate charged point to outlet
                                        $nominalBalance = $balanceNominal * (floatval($feePointOutlet) / 100);

                                        //calculate charged point to central
                                        $nominalBalanceCentral = $balanceNominal * (floatval($feePointCentral) / 100);
                                    }
                                }
                            }else{
                                $feePointCentral = 100;
                                $nominalBalanceCentral = $balanceNominal;
                            }
                        }elseif(strtolower($payments['type']) == 'ipay88'){
                            $ipay88 = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first();
                            if($statusSplitPayment == 1){
                                $amountMDR = $grandTotal - $nominalSubscription;
                            }else{
                                $amountMDR = $ipay88['amount']/100;
                            }

                            $method =  $ipay88['payment_method'];
                            $keyipay88 = array_search(strtoupper($method), array_column($settingMDRAll, 'payment_name'));
                            if($keyipay88 !== false){
                                $feePGCentral = $settingMDRAll[$keyipay88]['mdr_central'];
                                $feePG = $settingMDRAll[$keyipay88]['mdr'];
                                $feePGType = $settingMDRAll[$keyipay88]['percent_type'];
                                $charged = $settingMDRAll[$keyipay88]['charged'];
                            }
                        }elseif (strtolower($payments['type']) == 'ovo'){
                            $ovo = TransactionPaymentOvo::where('id_transaction', $data['id_transaction'])->first();

                            if($statusSplitPayment == 1){
                                $amountMDR = $grandTotal - $nominalSubscription;
                            }else{
                                $amountMDR = $ovo['amount']/100;
                            }

                            $keyipayOvo = array_search('OVO', array_column($settingMDRAll, 'payment_name'));
                            if($keyipayOvo !== false){
                                $feePGCentral = $settingMDRAll[$keyipayOvo]['mdr_central'];
                                $feePG = $settingMDRAll[$keyipayOvo]['mdr'];
                                $feePGType = $settingMDRAll[$keyipayOvo]['percent_type'];
                                $charged = $settingMDRAll[$keyipayOvo]['charged'];
                            }
                        }elseif (strtolower($payments['type']) == 'shopeepay'){
                            $shopeepay = TransactionPaymentShopeePay::where('id_transaction', $data['id_transaction'])->first();
                            if($statusSplitPayment == 1){
                                $amountMDR = $grandTotal - $nominalSubscription;
                            }else{
                                $amountMDR = $shopeepay['amount']/100;
                            }

                            $keyshopee = array_search(strtoupper('shopeepay'), array_column($settingMDRAll, 'payment_name'));
                            if($keyshopee !== false){
                                $feePGCentral = $settingMDRAll[$keyshopee]['mdr_central'];
                                $feePG = $settingMDRAll[$keyshopee]['mdr'];
                                $feePGType = $settingMDRAll[$keyshopee]['percent_type'];
                                $charged = $settingMDRAll[$keyshopee]['charged'];
                            }
                        }
                    }

                    $totalChargedPromo = 0;
                    $totalChargedPromoCentral = 0;
                    if(count($data['vouchers']) > 0){
                        $getDeal = Deal::where('id_deals', $data['vouchers'][0]['id_deals'])->first();
                        $feePromoCentral = $getDeal['charged_central'];
                        $feePromoOutlet = $getDeal['charged_outlet'];
                        $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                        if((int) $feePromoCentral !== 100){
                            $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                            $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                        }else{
                            $totalChargedPromoCentral = $data['transaction_discount'];
                        }
                    }elseif (!empty($data['promo_campaign'])){
                        $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                        $feePromoCentral = $data['promo_campaign']['charged_central'];
                        $feePromoOutlet = $data['promo_campaign']['charged_outlet'];
                        if((int) $feePromoCentral !== 100){
                            $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                            $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                        }else{
                            $totalChargedPromoCentral = $data['transaction_discount'];
                        }
                    }

                    //fee payment gateway
                    if($feePGType == 'Percent'){
                        $totalFee = $amountMDR * (($feePGCentral + $feePG) / 100);
                        $totalFeeForCentral = $amountMDR * ($feePGCentral/100);
                    }else{
                        $totalFee = $feePGCentral + $feePG;
                        $totalFeeForCentral = $feePGCentral;
                    }

                    $percentFee = 0;
                    if($data['outlet_special_status'] == 1){
                        $percentFee = $data['outlet_special_fee'];
                    }else{
                        if($data['status_franchise'] == 1){
                            $percentFee = ($settingGlobalFee->fee_outlet == '' ? 0 : $settingGlobalFee->fee_outlet);
                        }else{
                            $percentFee = ($settingGlobalFee->fee_central == '' ? 0 : $settingGlobalFee->fee_central);
                        }
                    }

                    $feeItemForCentral = (floatval($percentFee) / 100) * $nominalFeeToCentral;

                    $amount = round($subTotal - ((floatval($percentFee) / 100) * $nominalFeeToCentral) - $totalFee - $nominalBalance - $totalChargedPromo - $totalChargedSubcriptionOutlet, 2);
                    $incomeCentral = round(((floatval($percentFee) / 100) * $nominalFeeToCentral) + $totalFeeForCentral, 2);
                    $expenseCentral = round($nominalBalanceCentral + $totalChargedPromoCentral + $totalChargedSubcriptionCentral, 2);

                    $dataInsert = [
                        'id_transaction' => $data['id_transaction'],
                        'income_outlet'=> $amount,
                        'income_central'=> $incomeCentral,
                        'expense_central'=> $expenseCentral,
                        'fee_item' => $feeItemForCentral,
                        'discount' => $totalChargedPromo,
                        'discount_central' => $totalChargedPromoCentral,
                        'payment_charge' => $totalFee,
                        'point_use_expense' => $nominalBalance,
                        'subscription' => $totalChargedSubcriptionOutlet,
                        'subscription_central' => $totalChargedSubcriptionCentral,
                        'fee' => $percentFee,
                        'mdr' => $feePG,
                        'mdr_central' => $feePGCentral,
                        'mdr_charged' => $charged,
                        'mdr_type' => $feePGType,
                        'charged_point_central' => $feePointCentral,
                        'charged_point_outlet' => $feePointOutlet,
                        'charged_promo_central' => $feePromoCentral,
                        'charged_promo_outlet' => $feePromoOutlet,
                        'charged_subscription_central' => $feeSubcriptionCentral,
                        'charged_subscription_outlet' => $feeSubcriptionOutlet,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $insert = DisburseOutletTransaction::where('id_disburse_transaction', $data['id_disburse_transaction'])->update($dataInsert);

                    return $insert;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }

    public function getHoliday(){
        $instance = new \Google\Holidays();
        $holidays = $instance->withApiKey('AIzaSyAUj00RnoTm0A3rVsfb-Buy9Eqq4PAXxXw')
            ->inCountry('indonesian')
            ->withDatesOnly()
            ->list();
        return $holidays;
    }

    public function getDateForQuery($timeSetting, $publicHoliday){
        $currentDate = date('Y-m-d');

        $getWorkDay = 0;
        $x = 0;
        $date = "";

        while($getWorkDay < (int)$timeSetting) {
            $date = date('Y-m-d',strtotime('-'.$x.' days',strtotime($currentDate)));
            // if date is not sunday, saturday, and holiday then work date ++
            if(date('D', strtotime($date)) !== 'Sat' && date('D', strtotime($date)) !== 'Sun'
                && array_search($date, $publicHoliday) === false){
                $getWorkDay++;
            }
            $x++;
        }

        return $date;
    }
}
