<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\DailyReportTrx;
use App\Http\Models\Deal;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionBalance;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Jobs\DisburseJob;
use App\Jobs\SendEmailDisburseJob;
use Cassandra\Exception\ExecutionException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\Disburse;
use DB;
use Modules\Disburse\Entities\DisburseOutlet;
use Modules\Disburse\Entities\DisburseOutletTransaction;
use Modules\Disburse\Entities\DisburseTransaction;
use Modules\Disburse\Entities\LogIRIS;
use Modules\Disburse\Entities\LogTopupIris;
use Modules\Disburse\Entities\MDR;
use Modules\Disburse\Entities\PromoPaymentGatewayTransaction;
use Modules\Disburse\Entities\RulePromoPaymentGateway;
use Modules\Merchant\Entities\MerchantLogBalance;
use  Modules\UserFranchise\Entities\UserFranchisee;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use App\Lib\SendMail as Mail;
use DOMDocument;
use Illuminate\Support\Facades\Log;
use Modules\Transaction\Entities\TransactionBundlingProduct;

class ApiIrisController extends Controller
{
    public function __construct()
    {
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->promo_payment_gateway = "Modules\Disburse\Http\Controllers\ApiRulePromoPaymentGatewayController";
    }

    public function notification(Request $request)
    {
        $post = $request->json()->all();
        $status = $post['status'];

        if ($status == 'topup') {
            LogTopupIris::create(['response' => json_encode($post)]);
            return response()->json(['status' => 'success']);
        } else {
            $reference_no = $post['reference_no'];
            //if status alredy success then no update to database
            $check = Disburse::where('reference_no', $reference_no)->first();
            if ($check['disburse_status'] == 'Success' || $check['disburse_status'] == 'Fail') {
                $dataLog = [
                    'subject' => 'Callback IRIS',
                    'id_reference' => $post['reference_no'] ?? null,
                    'request' => json_encode($post),
                    'response' => json_encode(['status' => 'success'])
                ];
                LogIRIS::create($dataLog);
                return response()->json(['status' => 'success']);
            }

            $arrStatus = [
                'queued' => 'Queued',
                'processed' => 'Processed',
                'completed' => 'Success',
                'failed' => 'Fail',
                'rejected' => 'Rejected',
                'approved' => 'Approved'
            ];
            $data = Disburse::where('reference_no', $reference_no)->update(['disburse_status' => $arrStatus[$status] ?? null,
                'error_code' => $post['error_code'] ?? null, 'error_message' => $post['error_message'] ?? null]);

            $dataLog = [
                'subject' => 'Callback IRIS',
                'id_reference' => $post['reference_no'] ?? null,
                'request' => json_encode($post)
            ];

            if ($data) {
                if ($status == 'completed' || $status == 'failed') {
                    MerchantLogBalance::where('id_merchant_log_balance', $check['id_merchant_log_balance'])->update(['merchant_balance_status' => $status]);

                    $idMerchant = MerchantLogBalance::where('id_merchant_log_balance', $check['id_merchant_log_balance'])->first()['id_merchant'] ?? null;
                    $user = User::join('merchants', 'merchants.id_user', 'users.id')->where('id_merchant', $idMerchant)
                            ->select('users.*')->first();
                    app($this->autocrm)->SendAutoCRM(
                        'Merchant Withdrawal',
                        $user['phone'],
                        [
                            'amount' => number_format((int)$check['disburse_nominal'], 0, ",", "."),
                            'status' => $status
                        ],
                        null,
                        false,
                        false,
                        'merchant'
                    );
                }

                $dataLog['response'] = json_encode(['status' => 'success']);
                LogIRIS::create($dataLog);
                return response()->json(['status' => 'success']);
            } else {
                $dataLog['response'] = json_encode(['status' => 'fail', 'messages' => ['Failed Update status']]);
                LogIRIS::create($dataLog);
                return response()->json(['status' => 'fail',
                    'messages' => ['Failed Update status']]);
            }
        }
    }

    public function disburse()
    {
        $log = MyHelper::logCron('Disburse');
        try {
            $arrFailedPayouts = [];
            $arrSuccess = [];
            $arrReferenceNoFailed = [];
            $getCurrenDay = date('d');
            $getSettingDate = Setting::where('key', 'disburse_date')->first();
            $getSettingDate = (array)json_decode($getSettingDate['value_text'] ?? '');
            $getMinSendDate = $getSettingDate['min_date_send_disburse'] ?? 5;

            if ((int)$getCurrenDay >= (int)$getMinSendDate) {
                $currentDate = date('Y-m-d');
                $day = date('D', strtotime($currentDate));
                $getHoliday = $this->getHoliday();

                if ($day != 'Sat' && $day != 'Sun' && array_search($currentDate, $getHoliday) === false) {
                    $getSettingFeeDisburse = Setting::where('key', 'disburse_setting_fee_transfer')->first();
                    $lastDate = $getSettingDate['last_date_disburse'] ?? null;
                    $monthDb = date('n', strtotime($lastDate));
                    $monthCurrent = date('n');

                    if (
                        $getSettingFeeDisburse && $getSettingFeeDisburse['value'] !== "" &&
                        ($monthCurrent != $monthDb || is_null($lastDate))
                    ) {
                        /*
                         -first check current date is holiday or not
                         -today is holiday when return false
                         -today is not holiday when return true
                         -cron runs on weekdays
                        */
                        $dateCutOf = date("t", strtotime($currentDate . ' -1 MONTH'));
                        $year = date('Y', strtotime($currentDate . ' -1 MONTH'));
                        $month = date('m', strtotime($currentDate . ' -1 MONTH'));
                        $dateForQuery = date('Y-m-d', strtotime($year . '-' . $month . '-' . $dateCutOf));
                        $feeDisburse = (int)$getSettingFeeDisburse['value'];

                        $getData = Transaction::join('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
                            ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                            ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                            ->leftJoin('bank_account_outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                            ->leftJoin('bank_accounts', 'bank_accounts.id_bank_account', 'bank_account_outlets.id_bank_account')
                            ->leftJoin('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
                            ->where(function ($q) {
                                $q->whereNull('disburse_outlet.id_disburse_outlet')
                                    ->orWhereIn('disburse_status', ['Retry From Failed', 'Retry From Failed Payouts']);
                            })
                            ->whereNotNull('bank_accounts.beneficiary_name')
                            ->whereNull('transaction_pickups.reject_at')
                            ->where('transactions.transaction_payment_status', 'Completed')
                            ->where(function ($q) {
                                $q->whereNotNull('transaction_pickups.taken_at')
                                    ->orWhereNotNull('transaction_pickups.taken_by_system_at');
                            })
                            ->where(function ($q) {
                                $q->where('transactions.transaction_flag_invalid', 'Valid')
                                    ->orWhereNull('transactions.transaction_flag_invalid');
                            })
                            ->whereDate('transactions.transaction_date', '<=', $dateForQuery)
                            ->select(
                                'disburse_outlet_transactions.*',
                                'transactions.transaction_shipment',
                                'transaction_shipment_go_send',
                                'transactions.transaction_date',
                                'transactions.id_outlet',
                                'transactions.id_transaction',
                                'transactions.transaction_subtotal',
                                'transactions.transaction_grandtotal',
                                'transactions.transaction_discount',
                                'transactions.id_promo_campaign_promo_code',
                                'bank_name.bank_code',
                                'outlets.status_franchise',
                                'outlets.outlet_special_status',
                                'outlets.outlet_special_fee',
                                'bank_accounts.id_bank_account',
                                'bank_accounts.beneficiary_name',
                                'bank_accounts.beneficiary_account',
                                'bank_accounts.beneficiary_email',
                                'bank_accounts.beneficiary_alias'
                            )
                            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign', 'transaction_payment_subscription'])
                            ->orderBy('transactions.created_at', 'desc')->get()->toArray();

                        $arrStatus = [
                            'queued' => 'Queued',
                            'processed' => 'Processed',
                            'completed' => 'Success',
                            'failed' => 'Fail',
                            'rejected' => 'Rejected'
                        ];

                        if (!empty($getData)) {
                            DB::beginTransaction();

                            try {
                                $arrTmp = [];
                                $arrTmpDisburse = [];
                                foreach ($getData as $data) {
                                    //check promo payment gateway
                                    if (!empty($data['id_rule_promo_payment_gateway'])) {
                                        $rulePromoPG = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])->first();
                                        if (date('Y-m-d') < $rulePromoPG['end_date'] || $data['status_validation_promo_payment_gateway'] == 0) {
                                            continue;
                                        }
                                    }

                                    if (!is_null($data['beneficiary_account'])) {
                                        $amount = $data['income_outlet'];
                                        $incomeCentral = $data['income_central'];
                                        $expenseCentral = $data['expense_central'];
                                        $feeItemForCentral = $data['fee_item'];
                                        $grandTotal = $data['transaction_grandtotal'];
                                        $totalChargedPromo = $data['discount'];
                                        $totalFee = $data['payment_charge'];
                                        $nominalBalance = $data['subscription'];
                                        $totalChargedSubcriptionOutlet = 0;

                                        $transactionShipment = 0;
                                        if (!empty($data['transaction_shipment_go_send'])) {
                                            $transactionShipment = $data['transaction_shipment_go_send'];
                                        } elseif ($data['transaction_shipment']) {
                                            $transactionShipment = $data['transaction_shipment'];
                                        }

                                        //set to send disburse per bank account
                                        $checkAccount = array_search($data['beneficiary_account'], array_column($arrTmpDisburse, 'beneficiary_account'));
                                        if ($checkAccount === false) {
                                            $arrTmpDisburse[] = [
                                                'beneficiary_name' => $data['beneficiary_name'],
                                                'beneficiary_account' => $data['beneficiary_account'],
                                                'beneficiary_bank' => $data['bank_code'],
                                                'beneficiary_email' => $data['beneficiary_email'],
                                                'beneficiary_alias' => $data['beneficiary_alias'],
                                                'id_bank_account' => $data['id_bank_account'],
                                                'total_amount' => $amount
                                            ];
                                        } else {
                                            $arrTmpDisburse[$checkAccount]['total_amount'] = $arrTmpDisburse[$checkAccount]['total_amount'] + $amount;
                                        }

                                        //set to disburse outlet and disburse outlet transaction
                                        $checkOultet = array_search($data['id_outlet'], array_column($arrTmp, 'id_outlet'));

                                        if ($checkOultet === false) {
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
                                        } else {
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

                                foreach ($arrTmpDisburse as $value) {
                                    $amount = $value['total_amount'] - $feeDisburse;
                                    if ($amount > 10000) {
                                        $toSend = [
                                            'beneficiary_name' => $value['beneficiary_name'],
                                            'beneficiary_account' => $value['beneficiary_account'],
                                            'beneficiary_bank' => $value['beneficiary_bank'],
                                            'beneficiary_email' => $value['beneficiary_email'],
                                            'amount' => $amount,
                                            'notes' => 'Payment from apps ' . date('d M Y'),
                                        ];

                                        $dataToSend[] = $toSend;

                                        $dataToInsert[$value['beneficiary_account']] = [
                                            'disburse_nominal' => $amount,
                                            'total_income_outlet' => $value['total_amount'],
                                            'disburse_fee' => $feeDisburse,
                                            'id_bank_account' => $value['id_bank_account'],
                                            'beneficiary_name' => $value['beneficiary_name'],
                                            'beneficiary_bank_name' => $value['beneficiary_bank'],
                                            'beneficiary_account_number' => $value['beneficiary_account'],
                                            'beneficiary_email' => $value['beneficiary_email'],
                                            'beneficiary_alias' => $value['beneficiary_alias'],
                                            'notes' => 'Payment from apps ' . date('d M Y'),
                                            'request' => json_encode($toSend)
                                        ];
                                    }
                                }

                                foreach ($arrTmp as $val) {
                                    $dataToInsert[$val['beneficiary_account']]['disburse_outlet'][] = [
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

                                if ($dataToSend) {
                                    $chunk = array_chunk($dataToSend, 100);

                                    foreach ($chunk as $send) {
                                        $sendToIris = MyHelper::connectIris('Payouts', 'POST', 'api/v1/payouts', ['payouts' => $send]);

                                        if (isset($sendToIris['status']) && $sendToIris['status'] == 'success') {
                                            if (isset($sendToIris['response']['payouts']) && !empty($sendToIris['response']['payouts'])) {
                                                $j = 0;
                                                foreach ($sendToIris['response']['payouts'] as $val) {
                                                    $dataToInsert[$send[$j]['beneficiary_account']]['response'] = json_encode($val);
                                                    $dataToInsert[$send[$j]['beneficiary_account']]['reference_no'] = $val['reference_no'];
                                                    $dataToInsert[$send[$j]['beneficiary_account']]['disburse_status'] = $arrStatus[$val['status']];

                                                    $insertToDisburseOutlet = $dataToInsert[$send[$j]['beneficiary_account']]['disburse_outlet'];
                                                    $dataToInsert[$send[$j]['beneficiary_account']]['total_outlet'] = count($insertToDisburseOutlet);
                                                    unset($dataToInsert[$send[$j]['beneficiary_account']]['disburse_outlet']);

                                                    $insert = Disburse::create($dataToInsert[$send[$j]['beneficiary_account']]);

                                                    if ($insert) {
                                                        foreach ($insertToDisburseOutlet as $do) {
                                                            $do['id_disburse'] = $insert['id_disburse'];
                                                            $disburseOutlet = DisburseOutlet::create($do);
                                                            if ($disburseOutlet) {
                                                                DisburseOutletTransaction::whereIn('id_disburse_transaction', $do['transactions'])
                                                                    ->update(['id_disburse_outlet' => $disburseOutlet['id_disburse_outlet'], 'updated_at' => date('Y-m-d H:i:s')]);
                                                            }
                                                        }
                                                    }
                                                    $j++;
                                                }
                                            }
                                        } else {
                                            if (isset($sendToIris['response']['errors']) && !empty($sendToIris['response']['errors'])) {
                                                //save data failed to table disburse
                                                foreach ($sendToIris['response']['errors'] as $key => $err) {
                                                    $dataToInsert[$send[$key]['beneficiary_account']]['response'] = json_encode($val);
                                                    $dataToInsert[$send[$key]['beneficiary_account']]['disburse_status'] = 'Failed Create Payouts';
                                                    $dataToInsert[$send[$key]['beneficiary_account']]['error_message'] = implode(',', $err);

                                                    $insertToDisburseOutlet = $dataToInsert[$send[$key]['beneficiary_account']]['disburse_outlet'];
                                                    $dataToInsert[$send[$key]['beneficiary_account']]['total_outlet'] = count($insertToDisburseOutlet);
                                                    unset($dataToInsert[$send[$key]['beneficiary_account']]['disburse_outlet']);
                                                    $insert = Disburse::create($dataToInsert[$send[$key]['beneficiary_account']]);

                                                    if ($insert) {
                                                        $arrFailedPayouts[] = $insert['id_disburse'];
                                                        foreach ($insertToDisburseOutlet as $do) {
                                                            $do['id_disburse'] = $insert['id_disburse'];
                                                            $disburseOutlet = DisburseOutlet::create($do);
                                                            if ($disburseOutlet) {
                                                                DisburseOutletTransaction::whereIn('id_disburse_transaction', $do['transactions'])
                                                                    ->update(['id_disburse_outlet' => $disburseOutlet['id_disburse_outlet'], 'updated_at' => date('Y-m-d H:i:s')]);
                                                            }
                                                        }
                                                    }
                                                    unset($send[$key]);
                                                }

                                                //resend data that is not error
                                                $send = array_values($send);
                                                $sendAgainToIris = MyHelper::connectIris('Payouts', 'POST', 'api/v1/payouts', ['payouts' => $send]);
                                                if (isset($sendAgainToIris['status']) && $sendAgainToIris['status'] == 'success') {
                                                    if (isset($sendAgainToIris['response']['payouts']) && !empty($sendAgainToIris['response']['payouts'])) {
                                                        $k = 0;
                                                        foreach ($sendAgainToIris['response']['payouts'] as $val) {
                                                            $dataToInsert[$send[$k]['beneficiary_account']]['response'] = json_encode($val);
                                                            $dataToInsert[$send[$k]['beneficiary_account']]['reference_no'] = $val['reference_no'];
                                                            $dataToInsert[$send[$k]['beneficiary_account']]['disburse_status'] = $arrStatus[$val['status']];

                                                            $insertToDisburseOutlet = $dataToInsert[$send[$k]['beneficiary_account']]['disburse_outlet'];
                                                            $dataToInsert[$send[$k]['beneficiary_account']]['total_outlet'] = count($insertToDisburseOutlet);
                                                            unset($dataToInsert[$send[$k]['beneficiary_account']]['disburse_outlet']);
                                                            $insert = Disburse::create($dataToInsert[$send[$k]['beneficiary_account']]);

                                                            if ($insert) {
                                                                foreach ($insertToDisburseOutlet as $do) {
                                                                    $do['id_disburse'] = $insert['id_disburse'];
                                                                    $disburseOutlet = DisburseOutlet::create($do);
                                                                    if ($disburseOutlet) {
                                                                        DisburseOutletTransaction::whereIn('id_disburse_transaction', $do['transactions'])
                                                                            ->update(['id_disburse_outlet' => $disburseOutlet['id_disburse_outlet'], 'updated_at' => date('Y-m-d H:i:s')]);
                                                                    }
                                                                }
                                                            }
                                                            $k++;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                DB::commit();
                                //update last disburse
                                $getSettingDate['last_date_disburse'] = date('Y-m-d');
                                Setting::where('key', 'disburse_date')->update(['value_text' => json_encode($getSettingDate)]);
                            } catch (\Exception $e) {
                                DB::rollback();
                                \Log::error($e);
                                return 'fail';
                            }
                        }

                        //proses approve if setting approver by sistem
                        $settingApprover = Setting::where('key', 'disburse_auto_approve_setting')->first();
                        if ($settingApprover && $settingApprover['value'] == 1) {
                            $getDataToApprove = Disburse::where('disburse_status', 'Queued')
                                ->pluck('reference_no')->toArray();

                            $countData = count($getDataToApprove);
                            if ($countData > 0) {
                                $chunkApprover = array_chunk($getDataToApprove, 100);

                                $number = 0;
                                $loopNotAll = 0;
                                foreach ($chunkApprover as $sendAppr) {
                                    $number++;
                                    $sendApprover = MyHelper::connectIris('Approver', 'POST', 'api/v1/payouts/approve', ['reference_nos' => $sendAppr], 1);

                                    if (isset($sendApprover['status']) && $sendApprover['status'] == 'fail') {
                                        $loopNotAll = 1;
                                        break;
                                    } elseif (isset($sendApprover['status']) && $sendApprover['status'] == 'success') {
                                        $arrSuccess[] = $chunkApprover;
                                    }
                                }

                                /*Process send approver out one by one*/
                                if ($loopNotAll === 1) {
                                    $start = ($countData > 100 ? (($number * 100) - 1) : 0);
                                    $dataNotYetToSend = array_slice($getDataToApprove, $start, $countData);

                                    foreach ($dataNotYetToSend as $dtApprove) {
                                        $sendApprov = MyHelper::connectIris('Approver', 'POST', 'api/v1/payouts/approve', ['reference_nos' => [$dtApprove]], 1);

                                        if (isset($sendApprov['status']) && $sendApprov['status'] == 'fail') {
                                            $checkError = in_array("Partner does not have sufficient balance for the payout", $sendApprover['response']['errors']);
                                            $arrReferenceNoFailed[] = $dtApprove;
                                            if ($checkError !== false) {
                                                $getDataToApprove = Disburse::where('reference_no', $dtApprove)->update(['disburse_status' => 'Fail', 'error_code' => '009', 'error_message' => implode(',', $sendApprover['response']['errors'])]);
                                            } else {
                                                $getDataToApprove = Disburse::where('reference_no', $dtApprove)->update(['disburse_status' => 'Fail', 'error_message' => implode(',', $sendApprover['response']['errors'])]);
                                            }
                                        } elseif (isset($sendApprover['status']) && $sendApprover['status'] == 'success') {
                                            $arrSuccess[] = $dtApprove;
                                        }
                                    }
                                }
                            }
                        }

                        //send email to admin when balance is not enough
                        if ($arrReferenceNoFailed || $arrFailedPayouts) {
                            $getListOutlet = Disburse::with(['disburse_outlet']);

                            if (!empty($arrFailedPayouts)) {
                                $getListOutlet = $getListOutlet->orWhereIn('id_disburse', $arrFailedPayouts);
                            }

                            if (!empty($arrReferenceNoFailed)) {
                                $getListOutlet = $getListOutlet->orWhereIn('disburse.reference_no', $arrReferenceNoFailed);
                            }

                            $getListOutlet = $getListOutlet->get()->toArray();

                            $table = '';
                            if ($getListOutlet) {
                                $table .= '<table style="border-collapse: collapse;width: 100%;">';
                                $table .= '<thead>';
                                $table .= '<th style="border:1px solid">Reference No</th>';
                                $table .= '<th style="border:1px solid">Error Message</th>';
                                $table .= '<th style="border:1px solid">Outlet Name</th>';
                                $table .= '</thead>';
                                $table .= '<tbody>';
                                foreach ($getListOutlet as $dt) {
                                    $outlet = '<ul>';
                                    foreach ($dt['disburse_outlet'] as $o) {
                                        $outlet .= '<li>' . $o['outlet_code'] . '-' . $o['outlet_name'] . '</li>';
                                    }
                                    $outlet .= '</ul>';

                                    $table .= '<tr>';
                                    $table .= '<td style="border:1px solid">' . $dt['reference_no'] . '</td>';
                                    $table .= '<td style="border:1px solid">' . $dt['error_message'] . '</td>';
                                    $table .= '<td style="border:1px solid">' . $outlet . '</td>';
                                    $table .= '</tr>';
                                }
                                $table .= '</tbody>';
                                $table .= '</table>';
                            }
                            $this->sendForwardEmailDisburse('Failed Send Disburse', ['list_outlet' => $table]);
                        }
                    }
                }
            }

            $log->success($arrSuccess);
            return 'succes';
        } catch (\Exception $e) {
            \Log::error($e);
            $log->fail($e->getMessage());
        }
    }

    /* !!!!============= ATTENTION =============!!!! */
    /* !!!! please add new condition in "process calculation payment gateway" if you added new payment gateway !!!! */
    /* !!!! after update function please restart queue !!!! */
    public function calculationTransaction($id_transaction, $additionalDataPromoPayment = [])
    {
        $data = Transaction::where('id_transaction', $id_transaction)
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign', 'transaction_payment_subscription'])
            ->first();

        if (!empty($data)) {
            $settingGlobalFee = Setting::where('key', 'global_setting_fee')->first()->value_text;
            $settingProductPlastic = Setting::where('key', 'disburse_fee_product_plastic')->first()->value ?? 0;
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

            //variable promo payment gateway
            $idRulePromoPaymentGateway = null;
            $feePromoPaymentGateway = 0;
            $feePromoPaymentGatewayJiwaGroup = 0;
            $feePromoPaymentGatewayCentral = 0;
            $feePromoPaymentGatewayOutlet = 0;
            $chargedPromoPaymentGateway = 0;
            $chargedPromoPaymentGatewayJiwaGroup = 0;
            $chargedPromoPaymentGatewayCentral = 0;
            $chargedPromoPaymentGatewayOutlet = 0;

            $discountDelivery = abs($data['transaction_discount_delivery'] ?? 0);
            if (!empty($data['transaction_shipment_go_send'])) {
                $transactionShipment = $data['transaction_shipment_go_send'];
            }

            $charged = null;

            if (!empty($data['transaction_multiple_payment']) || !empty($data['transaction_payment_subscription'])) {
                //get data bundling product
                $getBundlingProduct = TransactionProduct::where('id_transaction', $id_transaction)
                    ->whereNotNull('id_bundling_product')
                    ->select('transaction_product_qty', 'id_transaction_bundling_product', 'id_bundling_product', 'transaction_product_bundling_discount', 'transaction_product_bundling_charged_outlet', 'transaction_product_bundling_charged_central')
                    ->get()->toArray();
                $bundlingProductTotalDiscount = 0;
                $bundlingProductFeeOutlet = 0;
                $bundlingProductFeeCentral = 0;
                foreach ($getBundlingProduct as $bp) {
                    $bundlingProductTotalDiscount = $bundlingProductTotalDiscount + ($bp['transaction_product_bundling_discount'] * $bp['transaction_product_qty']);
                    $bpChargedOutlet = (floatval($bp['transaction_product_bundling_charged_outlet']) / 100) * ($bp['transaction_product_bundling_discount'] * $bp['transaction_product_qty']);
                    $bpChargedCentral = (floatval($bp['transaction_product_bundling_charged_central']) / 100) * ($bp['transaction_product_bundling_discount'] * $bp['transaction_product_qty']);
                    $bundlingProductFeeOutlet = $bundlingProductFeeOutlet + $bpChargedOutlet;
                    $bundlingProductFeeCentral = $bundlingProductFeeCentral + $bpChargedCentral;
                }
                $subTotal = $subTotal + $bundlingProductTotalDiscount;
                $nominalFeeToCentral = $subTotal;

                // ===== Calculate Fee Subscription ====== //
                $totalChargedSubcriptionOutlet = 0;
                $totalChargedSubcriptionCentral = 0;
                $nominalSubscription = 0;
                if (!empty($data['transaction_payment_subscription'])) {
                    $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                        ->where('subscription_user_vouchers.id_subscription_user_voucher', $data['transaction_payment_subscription']['id_subscription_user_voucher'])
                        ->groupBy('subscriptions.id_subscription')->select('subscriptions.*')->first();
                    if ($getSubcription) {
                        $nominalSubscription = abs($data['transaction_payment_subscription']['subscription_nominal']);
                        $nominalFeeToCentral = $subTotal - abs($data['transaction_payment_subscription']['subscription_nominal']);
                        $feeSubcriptionCentral = $getSubcription['charged_central'];
                        $feeSubcriptionOutlet = $getSubcription['charged_outlet'];
                        if ((int) $feeSubcriptionCentral !== 100) {
                            $totalChargedSubcriptionOutlet = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionOutlet / 100));
                            $totalChargedSubcriptionCentral = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionCentral / 100));
                        } else {
                            $totalChargedSubcriptionCentral = $data['transaction_payment_subscription']['subscription_nominal'];
                        }
                    }
                }

                //check promo payment gateway
                $promoPaymentGateway = app($this->promo_payment_gateway)->getAvailablePromo($data['id_transaction'], $additionalDataPromoPayment);
                if (!empty($promoPaymentGateway)) {
                    $promoPaymentGatewayCashback = $promoPaymentGateway['cashback_customer'];
                    $idRulePromoPaymentGateway = $promoPaymentGateway['id_rule_promo_payment_gateway'];
                    $chargedPromoPaymentGateway = $promoPaymentGateway['charged_payment_gateway'];
                    $chargedPromoPaymentGatewayCentral = $promoPaymentGateway['charged_central'];
                    $chargedPromoPaymentGatewayOutlet = $promoPaymentGateway['charged_outlet'];
                    $mdrSettingOverride = $promoPaymentGateway['mdr_setting'];
                    $feePromoPaymentGatewayType = $promoPaymentGateway['charged_type'];
                    $feePromoPaymentGateway = $promoPaymentGateway['fee_payment_gateway'];
                    $feePromoPaymentGatewayJiwaGroup = $promoPaymentGateway['fee_jiwa_group'];
                    $feePromoPaymentGatewayCentral = $promoPaymentGateway['fee_central'];
                    $feePromoPaymentGatewayOutlet = $promoPaymentGateway['fee_outlet'];
                }

                $statusSplitPayment = 0;//for flag if transaction user split payment with balance (point)
                if (count($data['transaction_multiple_payment']) > 1) {
                    $statusSplitPayment = 1;
                }

                //process calculation payment gateway
                foreach ($data['transaction_multiple_payment'] as $payments) {
                    if (strtolower($payments['type']) == 'midtrans') {
                        $midtrans = TransactionPaymentMidtran::where('id_transaction', $data['id_transaction'])->first();
                        $payment = $midtrans['payment_type'];
                        $originalAmountPG = $midtrans['gross_amount'];
                        if ($statusSplitPayment == 1) {
                            $amountMDR = $grandTotal - $nominalSubscription;
                        } else {
                            $amountMDR = $midtrans['gross_amount'];
                        }

                        if (empty($payment)) {
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }

                        $keyMidtrans = array_search(strtoupper($payment), array_column($settingMDRAll, 'payment_name'));
                        if ($keyMidtrans !== false) {
                            $feePGCentral = $settingMDRAll[$keyMidtrans]['mdr_central'];
                            $feePG = $settingMDRAll[$keyMidtrans]['mdr'];
                            $feePGType = $settingMDRAll[$keyMidtrans]['percent_type'];
                            $charged = $settingMDRAll[$keyMidtrans]['charged'];
                        }
                    } elseif (strtolower($payments['type']) == 'balance') {
                        $balanceNominal = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first()->balance_nominal;

                        if ($statusSplitPayment == 0) {
                            $balanceMdr = array_search(strtoupper('FULL POINT'), array_column($settingMDRAll, 'payment_name'));
                            if ($balanceMdr !== false) {
                                $feePGCentral = $settingMDRAll[$balanceMdr]['mdr_central'];
                                $feePG = $settingMDRAll[$balanceMdr]['mdr'];
                                $feePGType = $settingMDRAll[$balanceMdr]['percent_type'];
                                $charged = $settingMDRAll[$balanceMdr]['charged'];

                                $feePointOutlet = (float)$settingMDRAll[$balanceMdr]['mdr'] + (float)$settingMDRAll[$balanceMdr]['mdr_central'];
                                $feePointCentral = 100 - $feePointOutlet;

                                if ((int)$feePointOutlet === 0) {
                                    //calculate charged point to central
                                    $nominalBalanceCentral = $balanceNominal;
                                } else {
                                    //calculate charged point to outlet
                                    $nominalBalance = $balanceNominal * (floatval($feePointOutlet) / 100);

                                    //calculate charged point to central
                                    $nominalBalanceCentral = $balanceNominal * (floatval($feePointCentral) / 100);
                                }
                            } else {
                                DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                                return true;
                            }
                        } else {
                            $feePointCentral = 100;
                            $nominalBalanceCentral = $balanceNominal;
                        }
                    } elseif (strtolower($payments['type']) == 'ipay88') {
                        $ipay88 = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first();
                        $originalAmountPG = $ipay88['amount'] / 100;
                        if ($statusSplitPayment == 1) {
                            $amountMDR = $grandTotal - $nominalSubscription;
                        } else {
                            $amountMDR = $ipay88['amount'] / 100;
                        }

                        $method =  $ipay88['payment_method'];
                        if (empty($method)) {
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }
                        $keyipay88 = array_search(strtoupper($method), array_column($settingMDRAll, 'payment_name'));
                        if ($keyipay88 !== false) {
                            $feePGCentral = $settingMDRAll[$keyipay88]['mdr_central'];
                            $feePG = $settingMDRAll[$keyipay88]['mdr'];
                            $feePGType = $settingMDRAll[$keyipay88]['percent_type'];
                            $charged = $settingMDRAll[$keyipay88]['charged'];
                        }
                    } elseif (strtolower($payments['type']) == 'ovo') {
                        $ovo = TransactionPaymentOvo::where('id_transaction', $data['id_transaction'])->first();
                        $originalAmountPG = $ovo['amount'] / 100;
                        if ($statusSplitPayment == 1) {
                            $amountMDR = $grandTotal - $nominalSubscription;
                        } else {
                            $amountMDR = $ovo['amount'] / 100;
                        }

                        $keyipayOvo = array_search('OVO', array_column($settingMDRAll, 'payment_name'));
                        if ($keyipayOvo !== false) {
                            $feePGCentral = $settingMDRAll[$keyipayOvo]['mdr_central'];
                            $feePG = $settingMDRAll[$keyipayOvo]['mdr'];
                            $feePGType = $settingMDRAll[$keyipayOvo]['percent_type'];
                            $charged = $settingMDRAll[$keyipayOvo]['charged'];
                        } else {
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }
                    } elseif (strtolower($payments['type']) == 'shopeepay') {
                        $shopeepay = TransactionPaymentShopeePay::where('id_transaction', $data['id_transaction'])->first();
                        $originalAmountPG = $shopeepay['amount'] / 100;
                        if ($statusSplitPayment == 1) {
                            $amountMDR = $grandTotal - $nominalSubscription;
                        } else {
                            $amountMDR = $shopeepay['amount'] / 100;
                        }

                        $keyshopee = array_search(strtoupper('shopeepay'), array_column($settingMDRAll, 'payment_name'));
                        if ($keyshopee !== false) {
                            $feePGCentral = $settingMDRAll[$keyshopee]['mdr_central'];
                            $feePG = $settingMDRAll[$keyshopee]['mdr'];
                            $feePGType = $settingMDRAll[$keyshopee]['percent_type'];
                            $charged = $settingMDRAll[$keyshopee]['charged'];
                        } else {
                            DisburseJob::dispatch(['id_transaction' => $id_transaction])->onConnection('disbursequeue');
                            return true;
                        }
                    }
                }

                $totalChargedPromo = 0;
                $totalChargedPromoCentral = 0;
                if (count($data['vouchers']) > 0 && (abs($data['transaction_discount']) > 0 || $discountDelivery > 0)) {
                    $getDeal = Deal::where('id_deals', $data['vouchers'][0]['id_deals'])->first();
                    $feePromoCentral = $getDeal['charged_central'];
                    $feePromoOutlet = $getDeal['charged_outlet'];
                    if ($discountDelivery > 0) {
                        if ((int) $feePromoCentral !== 100) {
                            $totalChargedPromo = ( abs($discountDelivery) * ($feePromoOutlet / 100));
                            $totalChargedPromoCentral = ( abs($discountDelivery) * ($feePromoCentral / 100));
                        } else {
                            $totalChargedPromoCentral =  abs($discountDelivery);
                        }
                    } else {
                        $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                        if ((int) $feePromoCentral !== 100) {
                            $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                            $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                        } else {
                            $totalChargedPromoCentral = abs($data['transaction_discount']);
                        }
                    }
                } elseif (!empty($data['promo_campaign']) && (abs($data['transaction_discount']) > 0 || $discountDelivery > 0)) {
                    $feePromoCentral = $data['promo_campaign']['charged_central'];
                    $feePromoOutlet = $data['promo_campaign']['charged_outlet'];
                    if ($discountDelivery > 0) {
                        if ((int) $feePromoCentral !== 100) {
                            $totalChargedPromo = (abs($discountDelivery) * ($feePromoOutlet / 100));
                            $totalChargedPromoCentral = ( abs($discountDelivery) * ($feePromoCentral / 100));
                        } else {
                            $totalChargedPromoCentral =  abs($discountDelivery);
                        }
                    } else {
                        $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                        if ((int) $feePromoCentral !== 100) {
                            $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                            $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                        } else {
                            $totalChargedPromoCentral = abs($data['transaction_discount']);
                        }
                    }
                } elseif (!empty($data['id_subscription_user_voucher']) && ($discountDelivery > 0 || !empty(abs($data['transaction_discount'])))) {
                    $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                        ->where('subscription_user_vouchers.id_subscription_user_voucher', $data['id_subscription_user_voucher'])
                        ->groupBy('subscriptions.id_subscription')->select('subscriptions.*')->first();
                    if ($getSubcription) {
                        $feeSubcriptionCentral = $getSubcription['charged_central'];
                        $feeSubcriptionOutlet = $getSubcription['charged_outlet'];

                        if ($discountDelivery > 0) {
                            if ((int) $feeSubcriptionCentral !== 100) {
                                $totalChargedSubcriptionOutlet = (abs($discountDelivery) * ($feeSubcriptionOutlet / 100));
                                $totalChargedSubcriptionCentral = (abs($discountDelivery) * ($feeSubcriptionCentral / 100));
                            } else {
                                $totalChargedSubcriptionCentral = abs($discountDelivery);
                            }
                        } else {
                            $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                            if ((int) $feeSubcriptionCentral !== 100) {
                                $totalChargedSubcriptionOutlet = (abs($data['transaction_discount']) * ($feeSubcriptionOutlet / 100));
                                $totalChargedSubcriptionCentral = (abs($data['transaction_discount']) * ($feeSubcriptionCentral / 100));
                            } else {
                                $totalChargedSubcriptionCentral = abs($data['transaction_discount']);
                            }
                        }
                    }
                }

                if ($feePGType == 'Percent') {
                    $totalFee = $amountMDR * (($feePGCentral + $feePG) / 100);//MDR
                    $totalFeeForCentral = $amountMDR * ($feePGCentral / 100);//MDR for central
                } else {
                    $totalFee = $feePGCentral + $feePG;//MDR
                    $totalFeeForCentral = $feePGCentral;//MDR for central
                }

                if (!empty($idRulePromoPaymentGateway)) {
                    switch ($mdrSettingOverride) {
                        case 'Total Amount PG - Cashback Jiwa Group':
                            $amountMDRPromoPG = $amountMDR - $feePromoPaymentGatewayJiwaGroup;
                            break;
                        case 'Total Amount PG - Total Cashback Customer':
                            $amountMDRPromoPG = $amountMDR - $promoPaymentGatewayCashback;
                            break;
                        default:
                            $amountMDRPromoPG = $amountMDR;
                            break;
                    }

                    //fee pg with promo payment gateway
                    if ($feePGType == 'Percent') {
                        $totalFeePromoPG = $amountMDRPromoPG * (($feePGCentral + $feePG) / 100);//MDR
                        $totalFeeForCentralPromoPG = $amountMDRPromoPG * ($feePGCentral / 100);//MDR for central
                    } else {
                        $totalFeePromoPG = $feePGCentral + $feePG;//MDR
                        $totalFeeForCentralPromoPG = $feePGCentral;//MDR for central
                    }

                    //fee payment gateway
                    if (isset($additionalDataPromoPayment['override_mdr_status']) && $additionalDataPromoPayment['override_mdr_status'] == 1) {
                        $totalFeeForCentralPromoPG = 0;
                        if ($additionalDataPromoPayment['override_mdr_percent_type'] == 'Percent' && !empty($additionalDataPromoPayment['mdr'])) {
                            $totalFeePromoPG = $amountMDRPromoPG * ($additionalDataPromoPayment['mdr'] / 100);//MDR
                        } elseif ($additionalDataPromoPayment['override_mdr_percent_type'] == 'Nominal') {
                            $totalFeePromoPG = $additionalDataPromoPayment['mdr'];//MDR
                        }
                    }
                }

                $percentFee = 0;
                if ($data['outlet_special_status'] == 1) {
                    $percentFee = $data['outlet_special_fee'];
                } else {
                    if ($data['status_franchise'] == 1) {
                        $percentFee = ($settingGlobalFee->fee_outlet == '' ? 0 : $settingGlobalFee->fee_outlet);
                    } else {
                        $percentFee = ($settingGlobalFee->fee_central == '' ? 0 : $settingGlobalFee->fee_central);
                    }
                }

                if (!empty($getBundlingProduct)) {
                    if ($nominalFeeToCentral == 0) {
                        $nominalFeeToCentral = $subTotal - $bundlingProductTotalDiscount;
                    } else {
                        $nominalFeeToCentral = $nominalFeeToCentral - $bundlingProductTotalDiscount;
                    }
                }

                if ($settingProductPlastic == 0) {
                    $subtotalPlastic = TransactionProduct::where('id_transaction', $id_transaction)->where('type', 'Plastic')->sum('transaction_product_subtotal');
                    $nominalFeeToCentral = $nominalFeeToCentral - $subtotalPlastic;
                }

                $feeItemForCentral = (floatval($percentFee) / 100) * $nominalFeeToCentral;
                $amount = round($subTotal - ((floatval($percentFee) / 100) * $nominalFeeToCentral) - $totalFee - $nominalBalance - $totalChargedPromo - $totalChargedSubcriptionOutlet - $bundlingProductFeeOutlet, 2);//income outlet
                $incomeCentral = round(((floatval($percentFee) / 100) * $nominalFeeToCentral) + $totalFeeForCentral, 2);//income central
                $expenseCentral = round($nominalBalanceCentral + $totalChargedPromoCentral + $totalChargedSubcriptionCentral + $bundlingProductFeeCentral, 2);//expense central
                $amountOld = 0;
                $incomeCentralOld = 0;
                $expenseCentralOld = 0;
                $totalFeeOld = 0;

                if (!empty($idRulePromoPaymentGateway)) {
                    $amountOld = $amount;
                    $incomeCentralOld = $incomeCentral;
                    $expenseCentralOld = $expenseCentral;
                    $totalFeeOld = $totalFee;

                    $amount = round($subTotal - ((floatval($percentFee) / 100) * $nominalFeeToCentral) - $totalFeePromoPG - $nominalBalance - $totalChargedPromo - $totalChargedSubcriptionOutlet - $bundlingProductFeeOutlet - $feePromoPaymentGatewayOutlet, 2);//income outlet
                    $incomeCentral = round(((floatval($percentFee) / 100) * $nominalFeeToCentral) + $totalFeeForCentralPromoPG, 2);//income central
                    $expenseCentral = round($nominalBalanceCentral + $totalChargedPromoCentral + $totalChargedSubcriptionCentral + $bundlingProductFeeCentral + $feePromoPaymentGatewayCentral, 2);//expense central
                    $totalFee = $totalFeePromoPG;
                }

                $dataInsert = [
                    'id_transaction' => $data['id_transaction'],
                    'income_outlet' => $amount,
                    'income_outlet_old' => $amountOld,
                    'income_central' => $incomeCentral,
                    'income_central_old' => $incomeCentralOld,
                    'expense_central' => $expenseCentral,
                    'expense_central_old' => $expenseCentralOld,
                    'fee_item' => $feeItemForCentral,
                    'discount' => $totalChargedPromo,
                    'discount_central' => $totalChargedPromoCentral,
                    'payment_charge' => $totalFee + $nominalBalance,
                    'payment_charge_old' => $totalFeeOld + $nominalBalance,
                    'point_use_expense' => $nominalBalance,
                    'subscription' => $totalChargedSubcriptionOutlet,
                    'subscription_central' => $totalChargedSubcriptionCentral,
                    'bundling_product_total_discount' => $bundlingProductTotalDiscount,
                    'bundling_product_fee_outlet' => $bundlingProductFeeOutlet,
                    'bundling_product_fee_central' => $bundlingProductFeeCentral,
                    'fee' => $percentFee,
                    'fee_product_plastic_status' => $settingProductPlastic,
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
                    'id_rule_promo_payment_gateway' => $idRulePromoPaymentGateway,
                    'fee_promo_payment_gateway_type' => $feePromoPaymentGatewayType ?? null,
                    'fee_promo_payment_gateway' => $feePromoPaymentGateway,
                    'fee_promo_payment_gateway_central' => $feePromoPaymentGatewayCentral,
                    'fee_promo_payment_gateway_outlet' => $feePromoPaymentGatewayOutlet,
                    'charged_promo_payment_gateway' => $chargedPromoPaymentGateway,
                    'charged_promo_payment_gateway_central' => $chargedPromoPaymentGatewayCentral,
                    'charged_promo_payment_gateway_outlet' => $chargedPromoPaymentGatewayOutlet,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $insert = DisburseOutletTransaction::updateOrCreate(['id_transaction' => $data['id_transaction']], $dataInsert);

                if ($insert && !empty(!empty($idRulePromoPaymentGateway))) {
                    $addToPromoTransaction = PromoPaymentGatewayTransaction::updateOrCreate(
                        ['id_transaction' => $data['id_transaction']],
                        [
                            'id_rule_promo_payment_gateway' => $promoPaymentGateway['id_rule_promo_payment_gateway'],
                            'payment_gateway_user' => $promoPaymentGateway['payment_gateway_user'] ?? null,
                            'id_user' => $promoPaymentGateway['id_user'] ?? null,
                            'id_transaction' => $data['id_transaction'],
                            'amount' => $originalAmountPG ?? 0,
                            'total_received_cashback' => $promoPaymentGatewayCashback
                        ]
                    );
                }
                return $insert;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function sycnFeeTransaction(Request $request)
    {
        $post = $request->json()->all();
        $date_start = $post['date_start'];
        $date_end = $post['date_end'];

        $datas = Transaction::leftJoin('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
            ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
            ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->where(function ($q) {
                $q->whereNull('disburse_outlet_transactions.id_disburse_outlet');
                $q->orWhereNull('disburse.disburse_status', '!=', 'Success');
            })
            ->where('transactions.transaction_date', '>=', $date_start)
            ->where('transactions.transaction_date', '<=', $date_end)
            ->select('transactions.*', 'disburse_outlet_transactions.id_disburse_transaction', 'disburse.disburse_status', 'outlets.*')
            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign', 'transaction_payment_subscription'])
            ->get()->toArray();

        if (!empty($datas)) {
            foreach ($datas as $data) {
                $settingGlobalFee = Setting::where('key', 'global_setting_fee')->first()->value_text;
                $settingProductPlastic = Setting::where('key', 'disburse_fee_product_plastic')->first()->value ?? 0;
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

                //variable promo payment gateway
                $idRulePromoPaymentGateway = null;
                $feePromoPaymentGateway = 0;
                $feePromoPaymentGatewayJiwaGroup = 0;
                $feePromoPaymentGatewayCentral = 0;
                $feePromoPaymentGatewayOutlet = 0;
                $chargedPromoPaymentGateway = 0;
                $chargedPromoPaymentGatewayJiwaGroup = 0;
                $chargedPromoPaymentGatewayCentral = 0;
                $chargedPromoPaymentGatewayOutlet = 0;

                $discountDelivery = abs($data['transaction_discount_delivery'] ?? 0);
                if (!empty($data['transaction_shipment_go_send'])) {
                    $transactionShipment = $data['transaction_shipment_go_send'];
                }

                $charged = null;

                if (!empty($data['transaction_multiple_payment']) || !empty($data['transaction_payment_subscription'])) {
                    //get data bundling product
                    $getBundlingProduct = TransactionProduct::where('id_transaction', $data['id_transaction'])
                        ->whereNotNull('id_bundling_product')
                        ->select('transaction_product_qty', 'id_transaction_bundling_product', 'id_bundling_product', 'transaction_product_bundling_discount', 'transaction_product_bundling_charged_outlet', 'transaction_product_bundling_charged_central')
                        ->get()->toArray();
                    $bundlingProductTotalDiscount = 0;
                    $bundlingProductFeeOutlet = 0;
                    $bundlingProductFeeCentral = 0;
                    foreach ($getBundlingProduct as $bp) {
                        $bundlingProductTotalDiscount = $bundlingProductTotalDiscount + ($bp['transaction_product_bundling_discount'] * $bp['transaction_product_qty']);
                        $bpChargedOutlet = (floatval($bp['transaction_product_bundling_charged_outlet']) / 100) * ($bp['transaction_product_bundling_discount'] * $bp['transaction_product_qty']);
                        $bpChargedCentral = (floatval($bp['transaction_product_bundling_charged_central']) / 100) * ($bp['transaction_product_bundling_discount'] * $bp['transaction_product_qty']);
                        $bundlingProductFeeOutlet = $bundlingProductFeeOutlet + $bpChargedOutlet;
                        $bundlingProductFeeCentral = $bundlingProductFeeCentral + $bpChargedCentral;
                    }

                    $subTotal = $subTotal + $bundlingProductTotalDiscount;
                    $nominalFeeToCentral = $subTotal;

                    // ===== Calculate Fee Subscription ====== //
                    $totalChargedSubcriptionOutlet = 0;
                    $totalChargedSubcriptionCentral = 0;
                    $nominalSubscription = 0;
                    if (!empty($data['transaction_payment_subscription'])) {
                        $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                            ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                            ->where('subscription_user_vouchers.id_subscription_user_voucher', $data['transaction_payment_subscription']['id_subscription_user_voucher'])
                            ->groupBy('subscriptions.id_subscription')->select('subscriptions.*')->first();
                        if ($getSubcription) {
                            $nominalSubscription = abs($data['transaction_payment_subscription']['subscription_nominal']);
                            $nominalFeeToCentral = $subTotal - abs($data['transaction_payment_subscription']['subscription_nominal']);
                            $feeSubcriptionCentral = $getSubcription['charged_central'];
                            $feeSubcriptionOutlet = $getSubcription['charged_outlet'];
                            if ((int) $feeSubcriptionCentral !== 100) {
                                $totalChargedSubcriptionOutlet = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionOutlet / 100));
                                $totalChargedSubcriptionCentral = (abs($data['transaction_payment_subscription']['subscription_nominal']) * ($feeSubcriptionCentral / 100));
                            } else {
                                $totalChargedSubcriptionCentral = $data['transaction_payment_subscription']['subscription_nominal'];
                            }
                        }
                    }

                    //check promo payment gateway
                    $promoPaymentGateway = app($this->promo_payment_gateway)->getAvailablePromo($data['id_transaction']);
                    if (!empty($promoPaymentGateway)) {
                        $promoPaymentGatewayCashback = $promoPaymentGateway['cashback_customer'];
                        $idRulePromoPaymentGateway = $promoPaymentGateway['id_rule_promo_payment_gateway'];
                        $chargedPromoPaymentGateway = $promoPaymentGateway['charged_payment_gateway'];
                        $chargedPromoPaymentGatewayCentral = $promoPaymentGateway['charged_central'];
                        $chargedPromoPaymentGatewayOutlet = $promoPaymentGateway['charged_outlet'];
                        $mdrSettingOverride = $promoPaymentGateway['mdr_setting'];
                        $feePromoPaymentGatewayType = $promoPaymentGateway['charged_type'];
                        $feePromoPaymentGateway = $promoPaymentGateway['fee_payment_gateway'];
                        $feePromoPaymentGatewayJiwaGroup = $promoPaymentGateway['fee_jiwa_group'];
                        $feePromoPaymentGatewayCentral = $promoPaymentGateway['fee_central'];
                        $feePromoPaymentGatewayOutlet = $promoPaymentGateway['fee_outlet'];
                    }

                    $statusSplitPayment = 0;//for flag if transaction user split payment with balance (point)
                    if (count($data['transaction_multiple_payment']) > 1) {
                        $statusSplitPayment = 1;
                    }

                    //process calculation payment gateway
                    foreach ($data['transaction_multiple_payment'] as $payments) {
                        if (strtolower($payments['type']) == 'midtrans') {
                            $midtrans = TransactionPaymentMidtran::where('id_transaction', $data['id_transaction'])->first();
                            $originalAmountPG = $midtrans['gross_amount'];
                            $payment = $midtrans['payment_type'];
                            if ($statusSplitPayment == 1) {
                                $amountMDR = $grandTotal - $nominalSubscription;
                            } else {
                                $amountMDR = $midtrans['gross_amount'];
                            }

                            $keyMidtrans = array_search(strtoupper($payment), array_column($settingMDRAll, 'payment_name'));
                            if ($keyMidtrans !== false) {
                                $feePGCentral = $settingMDRAll[$keyMidtrans]['mdr_central'];
                                $feePG = $settingMDRAll[$keyMidtrans]['mdr'];
                                $feePGType = $settingMDRAll[$keyMidtrans]['percent_type'];
                                $charged = $settingMDRAll[$keyMidtrans]['charged'];
                            }
                        } elseif (strtolower($payments['type']) == 'balance') {
                            $balanceNominal = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first()->balance_nominal;

                            if ($statusSplitPayment == 0) {
                                $balanceMdr = array_search(strtoupper('FULL POINT'), array_column($settingMDRAll, 'payment_name'));
                                if ($balanceMdr !== false) {
                                    $feePGCentral = $settingMDRAll[$balanceMdr]['mdr_central'];
                                    $feePG = $settingMDRAll[$balanceMdr]['mdr'];
                                    $feePGType = $settingMDRAll[$balanceMdr]['percent_type'];
                                    $charged = $settingMDRAll[$balanceMdr]['charged'];

                                    $feePointOutlet = (float)$settingMDRAll[$balanceMdr]['mdr'] + (float)$settingMDRAll[$balanceMdr]['mdr_central'];
                                    $feePointCentral = 100 - $feePointOutlet;

                                    if ((int)$feePointOutlet === 0) {
                                        //calculate charged point to central
                                        $nominalBalanceCentral = $balanceNominal;
                                    } else {
                                        //calculate charged point to outlet
                                        $nominalBalance = $balanceNominal * (floatval($feePointOutlet) / 100);

                                        //calculate charged point to central
                                        $nominalBalanceCentral = $balanceNominal * (floatval($feePointCentral) / 100);
                                    }
                                }
                            } else {
                                $feePointCentral = 100;
                                $nominalBalanceCentral = $balanceNominal;
                            }
                        } elseif (strtolower($payments['type']) == 'ipay88') {
                            $ipay88 = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first();
                            $originalAmountPG = $ipay88['amount'] / 100;
                            if ($statusSplitPayment == 1) {
                                $amountMDR = $grandTotal - $nominalSubscription;
                            } else {
                                $amountMDR = $ipay88['amount'] / 100;
                            }

                            $method =  $ipay88['payment_method'];
                            $keyipay88 = array_search(strtoupper($method), array_column($settingMDRAll, 'payment_name'));
                            if ($keyipay88 !== false) {
                                $feePGCentral = $settingMDRAll[$keyipay88]['mdr_central'];
                                $feePG = $settingMDRAll[$keyipay88]['mdr'];
                                $feePGType = $settingMDRAll[$keyipay88]['percent_type'];
                                $charged = $settingMDRAll[$keyipay88]['charged'];
                            }
                        } elseif (strtolower($payments['type']) == 'ovo') {
                            $ovo = TransactionPaymentOvo::where('id_transaction', $data['id_transaction'])->first();
                            $originalAmountPG = $ovo['amount'] / 100;
                            if ($statusSplitPayment == 1) {
                                $amountMDR = $grandTotal - $nominalSubscription;
                            } else {
                                $amountMDR = $ovo['amount'] / 100;
                            }

                            $keyipayOvo = array_search('OVO', array_column($settingMDRAll, 'payment_name'));
                            if ($keyipayOvo !== false) {
                                $feePGCentral = $settingMDRAll[$keyipayOvo]['mdr_central'];
                                $feePG = $settingMDRAll[$keyipayOvo]['mdr'];
                                $feePGType = $settingMDRAll[$keyipayOvo]['percent_type'];
                                $charged = $settingMDRAll[$keyipayOvo]['charged'];
                            }
                        } elseif (strtolower($payments['type']) == 'shopeepay') {
                            $shopeepay = TransactionPaymentShopeePay::where('id_transaction', $data['id_transaction'])->first();
                            $originalAmountPG = $shopeepay['amount'] / 100;
                            if ($statusSplitPayment == 1) {
                                $amountMDR = $grandTotal - $nominalSubscription;
                            } else {
                                $amountMDR = $shopeepay['amount'] / 100;
                            }

                            $keyshopee = array_search(strtoupper('shopeepay'), array_column($settingMDRAll, 'payment_name'));
                            if ($keyshopee !== false) {
                                $feePGCentral = $settingMDRAll[$keyshopee]['mdr_central'];
                                $feePG = $settingMDRAll[$keyshopee]['mdr'];
                                $feePGType = $settingMDRAll[$keyshopee]['percent_type'];
                                $charged = $settingMDRAll[$keyshopee]['charged'];
                            }
                        }
                    }

                    $totalChargedPromo = 0;
                    $totalChargedPromoCentral = 0;
                    if (count($data['vouchers']) > 0 && (abs($data['transaction_discount']) > 0 || $discountDelivery > 0)) {
                        $getDeal = Deal::where('id_deals', $data['vouchers'][0]['id_deals'])->first();
                        $feePromoCentral = $getDeal['charged_central'];
                        $feePromoOutlet = $getDeal['charged_outlet'];
                        if ($discountDelivery > 0) {
                            if ((int) $feePromoCentral !== 100) {
                                $totalChargedPromo = ( abs($discountDelivery) * ($feePromoOutlet / 100));
                                $totalChargedPromoCentral = ( abs($discountDelivery) * ($feePromoCentral / 100));
                            } else {
                                $totalChargedPromoCentral =  abs($discountDelivery);
                            }
                        } else {
                            $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                            if ((int) $feePromoCentral !== 100) {
                                $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                                $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                            } else {
                                $totalChargedPromoCentral = abs($data['transaction_discount']);
                            }
                        }
                    } elseif (!empty($data['promo_campaign']) && (abs($data['transaction_discount']) > 0 || $discountDelivery > 0)) {
                        $feePromoCentral = $data['promo_campaign']['charged_central'];
                        $feePromoOutlet = $data['promo_campaign']['charged_outlet'];
                        if ($discountDelivery > 0) {
                            if ((int) $feePromoCentral !== 100) {
                                $totalChargedPromo = (abs($discountDelivery) * ($feePromoOutlet / 100));
                                $totalChargedPromoCentral = ( abs($discountDelivery) * ($feePromoCentral / 100));
                            } else {
                                $totalChargedPromoCentral =  abs($discountDelivery);
                            }
                        } else {
                            $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                            if ((int) $feePromoCentral !== 100) {
                                $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                                $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                            } else {
                                $totalChargedPromoCentral = abs($data['transaction_discount']);
                            }
                        }
                    } elseif (!empty($data['id_subscription_user_voucher']) && ($discountDelivery > 0 || !empty(abs($data['transaction_discount'])))) {
                        $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                            ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                            ->where('subscription_user_vouchers.id_subscription_user_voucher', $data['id_subscription_user_voucher'])
                            ->groupBy('subscriptions.id_subscription')->select('subscriptions.*')->first();
                        if ($getSubcription) {
                            $feeSubcriptionCentral = $getSubcription['charged_central'];
                            $feeSubcriptionOutlet = $getSubcription['charged_outlet'];

                            if ($discountDelivery > 0) {
                                if ((int) $feeSubcriptionCentral !== 100) {
                                    $totalChargedSubcriptionOutlet = (abs($discountDelivery) * ($feeSubcriptionOutlet / 100));
                                    $totalChargedSubcriptionCentral = (abs($discountDelivery) * ($feeSubcriptionCentral / 100));
                                } else {
                                    $totalChargedSubcriptionCentral = abs($discountDelivery);
                                }
                            } else {
                                $nominalFeeToCentral = $subTotal - abs($data['transaction_discount']);
                                if ((int) $feeSubcriptionCentral !== 100) {
                                    $totalChargedSubcriptionOutlet = (abs($data['transaction_discount']) * ($feeSubcriptionOutlet / 100));
                                    $totalChargedSubcriptionCentral = (abs($data['transaction_discount']) * ($feeSubcriptionCentral / 100));
                                } else {
                                    $totalChargedSubcriptionCentral = abs($data['transaction_discount']);
                                }
                            }
                        }
                    }

                    //fee payment gateway
                    if ($feePGType == 'Percent') {
                        $totalFee = $amountMDR * (($feePGCentral + $feePG) / 100);//MDR
                        $totalFeeForCentral = $amountMDR * ($feePGCentral / 100);//MDR for central
                    } else {
                        $totalFee = $feePGCentral + $feePG;//MDR
                        $totalFeeForCentral = $feePGCentral;//MDR for central
                    }

                    if (!empty($idRulePromoPaymentGateway)) {
                        switch ($mdrSettingOverride) {
                            case 'Total Amount PG - Cashback Jiwa Group':
                                $amountMDRPromoPG = $amountMDR - $feePromoPaymentGatewayJiwaGroup;
                                break;
                            case 'Total Amount PG - Total Cashback Customer':
                                $amountMDRPromoPG = $amountMDR - $promoPaymentGatewayCashback;
                                break;
                            default:
                                $amountMDRPromoPG = $amountMDR;
                                break;
                        }

                        //fee pg with promo payment gateway
                        if ($feePGType == 'Percent') {
                            $totalFeePromoPG = $amountMDRPromoPG * (($feePGCentral + $feePG) / 100);//MDR
                            $totalFeeForCentralPromoPG = $amountMDRPromoPG * ($feePGCentral / 100);//MDR for central
                        } else {
                            $totalFeePromoPG = $feePGCentral + $feePG;//MDR
                            $totalFeeForCentralPromoPG = $feePGCentral;//MDR for central
                        }
                    }

                    $percentFee = 0;
                    if ($data['outlet_special_status'] == 1) {
                        $percentFee = $data['outlet_special_fee'];
                    } else {
                        if ($data['status_franchise'] == 1) {
                            $percentFee = ($settingGlobalFee->fee_outlet == '' ? 0 : $settingGlobalFee->fee_outlet);
                        } else {
                            $percentFee = ($settingGlobalFee->fee_central == '' ? 0 : $settingGlobalFee->fee_central);
                        }
                    }

                    if (!empty($getBundlingProduct)) {
                        if ($nominalFeeToCentral == 0) {
                            $nominalFeeToCentral = $subTotal - $bundlingProductTotalDiscount;
                        } else {
                            $nominalFeeToCentral = $nominalFeeToCentral - $bundlingProductTotalDiscount;
                        }
                    }

                    if ($settingProductPlastic == 0) {
                        $subtotalPlastic = TransactionProduct::where('id_transaction', $data['id_transaction'])->where('type', 'Plastic')->sum('transaction_product_subtotal');
                        $nominalFeeToCentral = $subTotal - $subtotalPlastic;
                    }

                    $feeItemForCentral = (floatval($percentFee) / 100) * $nominalFeeToCentral;
                    $amount = round($subTotal - ((floatval($percentFee) / 100) * $nominalFeeToCentral) - $totalFee - $nominalBalance - $totalChargedPromo - $totalChargedSubcriptionOutlet - $bundlingProductFeeOutlet, 2);//income outlet
                    $incomeCentral = round(((floatval($percentFee) / 100) * $nominalFeeToCentral) + $totalFeeForCentral, 2);//income central
                    $expenseCentral = round($nominalBalanceCentral + $totalChargedPromoCentral + $totalChargedSubcriptionCentral + $bundlingProductFeeCentral, 2);//expense central
                    $amountOld = 0;
                    $incomeCentralOld = 0;
                    $expenseCentralOld = 0;
                    $totalFeeOld = 0;

                    if (!empty($idRulePromoPaymentGateway)) {
                        $amountOld = $amount;
                        $incomeCentralOld = $incomeCentral;
                        $expenseCentralOld = $expenseCentral;
                        $totalFeeOld = $totalFee;

                        $amount = round($subTotal - ((floatval($percentFee) / 100) * $nominalFeeToCentral) - $totalFeePromoPG - $nominalBalance - $totalChargedPromo - $totalChargedSubcriptionOutlet - $bundlingProductFeeOutlet - $feePromoPaymentGatewayOutlet, 2);//income outlet
                        $incomeCentral = round(((floatval($percentFee) / 100) * $nominalFeeToCentral) + $totalFeeForCentralPromoPG, 2);//income central
                        $expenseCentral = round($nominalBalanceCentral + $totalChargedPromoCentral + $totalChargedSubcriptionCentral + $bundlingProductFeeCentral + $feePromoPaymentGatewayCentral, 2);//expense central
                        $totalFee = $totalFeePromoPG;
                    }

                    $dataInsert = [
                        'id_transaction' => $data['id_transaction'],
                        'income_outlet' => $amount,
                        'income_outlet_old' => $amountOld,
                        'income_central' => $incomeCentral,
                        'income_central_old' => $incomeCentralOld,
                        'expense_central' => $expenseCentral,
                        'expense_central_old' => $expenseCentralOld,
                        'fee_item' => $feeItemForCentral,
                        'discount' => $totalChargedPromo,
                        'discount_central' => $totalChargedPromoCentral,
                        'payment_charge' => $totalFee + $nominalBalance,
                        'payment_charge_old' => $totalFeeOld + $nominalBalance,
                        'point_use_expense' => $nominalBalance,
                        'subscription' => $totalChargedSubcriptionOutlet,
                        'subscription_central' => $totalChargedSubcriptionCentral,
                        'bundling_product_total_discount' => $bundlingProductTotalDiscount,
                        'bundling_product_fee_outlet' => $bundlingProductFeeOutlet,
                        'bundling_product_fee_central' => $bundlingProductFeeCentral,
                        'fee' => $percentFee,
                        'fee_product_plastic_status' => $settingProductPlastic,
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
                        'id_rule_promo_payment_gateway' => $idRulePromoPaymentGateway,
                        'fee_promo_payment_gateway_type' => $feePromoPaymentGatewayType ?? null,
                        'fee_promo_payment_gateway' => $feePromoPaymentGateway,
                        'fee_promo_payment_gateway_central' => $feePromoPaymentGatewayCentral,
                        'fee_promo_payment_gateway_outlet' => $feePromoPaymentGatewayOutlet,
                        'charged_promo_payment_gateway' => $chargedPromoPaymentGateway,
                        'charged_promo_payment_gateway_central' => $chargedPromoPaymentGatewayCentral,
                        'charged_promo_payment_gateway_outlet' => $chargedPromoPaymentGatewayOutlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $insert = DisburseOutletTransaction::updateOrCreate(['id_transaction' => $data['id_transaction']], $dataInsert);

                    if ($insert && !empty(!empty($idRulePromoPaymentGateway))) {
                        $addToPromoTransaction = PromoPaymentGatewayTransaction::updateOrCreate(
                            ['id_transaction' => $data['id_transaction']],
                            [
                                'id_rule_promo_payment_gateway' => $promoPaymentGateway['id_rule_promo_payment_gateway'],
                                'payment_gateway_user' => $promoPaymentGateway['payment_gateway_user'] ?? null,
                                'id_user' => $promoPaymentGateway['id_user'] ?? null,
                                'id_transaction' => $data['id_transaction'],
                                'amount' => $originalAmountPG ?? 0,
                                'total_received_cashback' => $promoPaymentGatewayCashback
                            ]
                        );
                    }

                    if (!empty($data['id_disburse_transaction'])) {
                        $insert = DisburseOutletTransaction::where('id_disburse_transaction', $data['id_disburse_transaction'])->update($dataInsert);
                    } else {
                        $insert = DisburseOutletTransaction::create($dataInsert);
                    }
                }
            }
        }

        return 'success';
    }

    public function getHoliday()
    {
        $instance = new \Google\Holidays();
        $holidays = $instance->withApiKey('AIzaSyAUj00RnoTm0A3rVsfb-Buy9Eqq4PAXxXw')
            ->inCountry('indonesian')
            ->withDatesOnly()
            ->list();
        return $holidays;
    }

    public function getDateForQuery($timeSetting, $publicHoliday)
    {
        $currentDate = date('Y-m-d');

        $getWorkDay = 0;
        $x = 0;
        $date = "";

        while ($getWorkDay < (int)$timeSetting) {
            $date = date('Y-m-d', strtotime('-' . $x . ' days', strtotime($currentDate)));
            // if date is not sunday, saturday, and holiday then work date ++
            if (
                date('D', strtotime($date)) !== 'Sat' && date('D', strtotime($date)) !== 'Sun'
                && array_search($date, $publicHoliday) === false
            ) {
                $getWorkDay++;
            }
            $x++;
        }

        return $date;
    }

    public function sendForwardEmailDisburse($title, $additionalContent = [])
    {

        $crm = Autocrm::where('autocrm_title', '=', $title)->with('whatsapp_content')->first();
        if (!empty($crm)) {
            if (!empty($crm['autocrm_forward_email'])) {
                $exparr = explode(';', str_replace(',', ';', $crm['autocrm_forward_email']));
                $to     = $exparr;
                $content = $crm['autocrm_forward_email_content'];

                foreach ($additionalContent as $key => $c) {
                    $content = str_replace('%' . $key . '%', $c, $content);
                }

                // get setting email
                $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
                $setting = array();
                foreach ($getSetting as $key => $value) {
                    $setting[$value['key']] = $value['value'];
                }

                $subject = $crm['autocrm_forward_email_subject'];

                $data = array(
                    'html_message' => $content,
                    'setting'      => $setting
                );

                Mail::send('emails.test', $data, function ($message) use ($to, $subject, $setting) {
                    $message->to($to)->subject($subject);
                    if (!empty($setting['email_from']) && !empty($setting['email_sender'])) {
                        $message->from($setting['email_sender'], $setting['email_from']);
                    } elseif (!empty($setting['email_sender'])) {
                        $message->from($setting['email_sender']);
                    }

                    if (!empty($setting['email_reply_to']) && !empty($setting['email_reply_to_name'])) {
                                    $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                    } elseif (!empty($setting['email_reply_to'])) {
                        $message->replyTo($setting['email_reply_to']);
                    }

                    if (!empty($setting['email_cc']) && !empty($setting['email_cc_name'])) {
                        $message->cc($setting['email_cc'], $setting['email_cc_name']);
                    }

                    if (!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])) {
                        $message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
                    }
                });
            }
        }
    }
}
