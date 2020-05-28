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
use Cassandra\Exception\ExecutionException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\Disburse;

use DB;
use Modules\Disburse\Entities\DisburseTransaction;
use Modules\Disburse\Entities\LogIRIS;
use Modules\Disburse\Entities\MDR;
use Modules\Disburse\Entities\UserFranchisee;
use Modules\IPay88\Entities\TransactionPaymentIpay88;

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
        $data = Disburse::where('reference_no', $reference_no)->update(['disburse_status' => $arrStatus[$status]]);

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
        $getData = Transaction::leftJoin('disburse_transactions', 'disburse_transactions.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
            ->where('transaction_payment_status', '=', 'Completed')
            ->whereNull('disburse_transactions.id_disburse')
            ->select('transactions.id_outlet', 'transactions.id_transaction', 'transactions.transaction_subtotal',
                'transactions.transaction_grandtotal', 'transactions.transaction_discount', 'transactions.id_promo_campaign_promo_code',
                'bank_name.bank_code', 'outlets.status_franchise', 'outlets.outlet_special_status', 'outlets.outlet_special_fee',
                'outlets.beneficiary_name', 'outlets.beneficiary_account', 'outlets.beneficiary_email', 'outlets.beneficiary_alias')
            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign'])
            ->orderBy('transactions.created_at', 'desc')->get()->toArray();
        $settingGlobalFee = Setting::where('key', 'global_setting_fee')->first()->value_text;
        $settingGlobalFee = json_decode($settingGlobalFee);
        $settingGlobalFeePoint = Setting::where('key', 'global_setting_point_charged')->first()->value_text;
        $settingGlobalFeePoint = json_decode($settingGlobalFeePoint);
        $settingMDRAll = MDR::get()->toArray();

        $arrStatus = [
            'queued' => 'Queued',
            'processed' => 'Processed',
            'completed' => 'Success',
            'failed' => 'Fail',
            'rejected' => 'Rejected'
        ];

        if(!empty($getData)){
            DB::beginTransaction();

            try{
                $arrTmp = [];
                foreach ($getData as $data){

                    if(!is_null($data['beneficiary_account'])){
                        $subTotal = $data['transaction_subtotal'];
                        $grandTotal = $data['transaction_grandtotal'];
                        $feePGCentral = 0;
                        $feePG = 0;
                        $feePGType = 'Percent';
                        $feePointCentral = 0;
                        $feePointOutlet = 0;
                        $feePromoCentral = 0;
                        $feePromoOutlet = 0;
                        $balanceNominal = 0;
                        $nominalBalance = 0;
                        $nominalBalanceCentral = 0;
                        $totalFeeForCentral = 0;
                        $amount = 0;
                        $charged = NULL;

                        if(empty($data['transaction_multiple_payment'])){
                            continue;
                        }
                        foreach ($data['transaction_multiple_payment'] as $payments){

                            if(strtolower($payments['type']) == 'midtrans'){
                                $midtrans = TransactionPaymentMidtran::where('id_transaction', $data['id_transaction'])->first();
                                if(!is_null($midtrans['payment_type'])){
                                    $payment = $midtrans['payment_type'];
                                }else{
                                    $payment = $midtrans['bank'];
                                }
                                $keyMidtrans = array_search(strtoupper($payment), array_column($settingMDRAll, 'payment_name'));
                                if($keyMidtrans !== false){
                                    if(!is_null($settingMDRAll[$keyMidtrans]['days_to_sent'])){
                                        $explode = explode(',', $settingMDRAll[$keyMidtrans]['days_to_sent']);
                                        $checkDay = array_search(date('l'), $explode);
                                        if($checkDay < 0 || $checkDay === false){
                                            continue 2;
                                        }
                                    }
                                    $feePGCentral = $settingMDRAll[$keyMidtrans]['mdr_central'];
                                    $feePG = $settingMDRAll[$keyMidtrans]['mdr'];
                                    $feePGType = $settingMDRAll[$keyMidtrans]['percent_type'];
                                    $charged = $settingMDRAll[$keyMidtrans]['charged'];
                                }else{
                                    continue 2;
                                }

                            }elseif (strtolower($payments['type']) == 'balance'){
                                $balanceNominal = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first()->balance_nominal;
                                $feePointCentral = ($settingGlobalFeePoint->central == '' ? 0 : $settingGlobalFeePoint->central);
                                $feePointOutlet = ($settingGlobalFeePoint->outlet == '' ? 0 : $settingGlobalFeePoint->outlet);

                                if((int)$feePointCentral !== 100){
                                    //calculate charged point to outlet
                                    $nominalBalance = $balanceNominal * (floatval($feePointOutlet) / 100);

                                    //calculate charged point to central
                                    $nominalBalanceCentral = $balanceNominal * (floatval($feePointCentral) / 100);
                                }else{
                                    //calculate charged point to central
                                    $nominalBalanceCentral = $balanceNominal;
                                }
                            }elseif(strtolower($payments['type']) == 'ipay88'){
                                $ipay88 = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first()->payment_method;
                                $keyipay88 = array_search(strtoupper($ipay88), array_column($settingMDRAll, 'payment_name'));
                                if($keyipay88 !== false){
                                    if(!is_null($settingMDRAll[$keyipay88]['days_to_sent'])){
                                        $explode = explode(',', $settingMDRAll[$keyipay88]['days_to_sent']);
                                        $checkDay = array_search(date('l'), $explode);
                                        if($checkDay < 0 || $checkDay === false){
                                            continue 2;
                                        }
                                    }

                                    $feePGCentral = $settingMDRAll[$keyipay88]['mdr_central'];
                                    $feePG = $settingMDRAll[$keyipay88]['mdr'];
                                    $feePGType = $settingMDRAll[$keyipay88]['percent_type'];
                                    $charged = $settingMDRAll[$keyipay88]['charged'];
                                }else{
                                    continue 2;
                                }
                            }elseif (strtolower($payments['type']) == 'ovo'){
                                $keyipayOvo = array_search('OVO', array_column($settingMDRAll, 'payment_name'));
                                if($keyipayOvo !== false){
                                    if(!is_null($settingMDRAll[$keyipayOvo]['days_to_sent'])){
                                        $explode = explode(',', $settingMDRAll[$keyipayOvo]['days_to_sent']);
                                        $checkDay = array_search(date('l'), $explode);
                                        if($checkDay < 0 || $checkDay === false){
                                            continue 2;
                                        }
                                    }

                                    $feePGCentral = $settingMDRAll[$keyipayOvo]['mdr_central'];
                                    $feePG = $settingMDRAll[$keyipayOvo]['mdr'];
                                    $feePGType = $settingMDRAll[$keyipayOvo]['percent_type'];
                                    $charged = $settingMDRAll[$keyipayOvo]['charged'];
                                }else{
                                    continue 2;
                                }
                            }
                        }

                        $totalChargedPromo = 0;
                        $totalChargedPromoCentral = 0;
                        if(!empty($data['vouchers'])){
                            $getDeal = Deal::where('id_deals', $data['vouchers'][0]['id_deals'])->first();
                            $feePromoCentral = $getDeal['charged_central'];
                            $feePromoOutlet = $getDeal['charged_outlet'];
                            if((int) $feePromoCentral !== 100){
                                $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                                $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                            }else{
                                $totalChargedPromoCentral = $data['transaction_discount'];
                            }
                        }elseif (!empty($data['promo_campaign'])){
                            $feePromoCentral = $data['promo_campaign']['charged_central'];
                            $feePromoOutlet = $data['promo_campaign']['charged_outlet'];
                            if((int) $feePromoCentral !== 100){
                                $totalChargedPromo = (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                                $totalChargedPromoCentral = (abs($data['transaction_discount']) * ($feePromoCentral / 100));
                            }else{
                                $totalChargedPromoCentral = $data['transaction_discount'];
                            }
                        }

                        if($feePGType == 'Percent'){
                            $totalFee = $grandTotal * (($feePGCentral + $feePG) / 100);
                            $totalFeeForCentral = $grandTotal * ($feePGCentral/100);
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

                        $amount = $subTotal - ((floatval($percentFee) / 100) * $subTotal) - $totalFee - $nominalBalance - $totalChargedPromo;
                        $incomeCentral = ((floatval($percentFee) / 100) * $subTotal) + $totalFeeForCentral - $nominalBalanceCentral - $totalChargedPromoCentral;

                        $checkOultet = array_search($data['id_outlet'], array_column($arrTmp, 'id_outlet'));

                        if($checkOultet === false){
                            $arrTmp[] = [
                                'id_outlet' => $data['id_outlet'],
                                'beneficiary_name' => $data['beneficiary_name'],
                                'beneficiary_account' => $data['beneficiary_account'],
                                'beneficiary_bank_name' => $data['bank_code'],
                                'beneficiary_email' => $data['beneficiary_email'],
                                'beneficiary_alias' => $data['beneficiary_alias'],
                                'bank_code' => $data['bank_code'],
                                'total_amount' => $amount,
                                'total_income_central' => $incomeCentral,
                                'transactions' => [
                                    [
                                        'id_transaction' => $data['id_transaction'],
                                        'income_outlet'=> $amount,
                                        'income_central'=> $incomeCentral,
                                        'fee' => $percentFee,
                                        'mdr' => $feePG,
                                        'mdr_central' => $feePGCentral,
                                        'mdr_charged' => $charged,
                                        'mdr_type' => $feePGType,
                                        'charged_point_central' => $feePointCentral,
                                        'charged_point_outlet' => $feePointOutlet,
                                        'charged_promo_central' => $feePromoCentral,
                                        'charged_promo_outlet' => $feePromoCentral
                                    ]
                                ]
                            ];
                        }else{
                            $arrTmp[$checkOultet]['total_amount'] = $arrTmp[$checkOultet]['total_amount'] + $amount;
                            $arrTmp[$checkOultet]['total_income_central'] = $arrTmp[$checkOultet]['total_income_central'] + $incomeCentral;
                            $arrTmp[$checkOultet]['transactions'][] = [
                                'id_transaction' => $data['id_transaction'],
                                'income_outlet'=> $amount,
                                'income_central'=> $incomeCentral,
                                'fee' => $percentFee,
                                'mdr' => $feePG,
                                'mdr_central' => $feePGCentral,
                                'mdr_charged' => $charged,
                                'mdr_type' => $feePGType,
                                'charged_point_central' => $feePointCentral,
                                'charged_point_outlet' => $feePointOutlet,
                                'charged_promo_central' => $feePromoCentral,
                                'charged_promo_outlet' => $feePromoCentral
                            ];
                        }

                        $arrTmpIdTrx[] = $data['id_transaction'];
                    }
                }

                $dataToSend = [];
                $dataToInsert = [];

                foreach ($arrTmp as $val){
                    $toSend= [
                        'beneficiary_name' => $val['beneficiary_name'],
                        'beneficiary_account' => $val['beneficiary_account'],
                        'beneficiary_bank' => $val['bank_code'],
                        'beneficiary_email' => $val['beneficiary_email'],
                        'amount' => $val['total_amount'],
                        'notes' => 'Payment from apps '.date('d M Y')
                    ];

                    $dataToSend[] = $toSend;

                    $dataToInsert[] = [
                        'id_outlet' => $val['id_outlet'],
                        'disburse_nominal' => $val['total_amount'],
                        'total_income_central' => $val['total_income_central'],
                        'beneficiary_name' => $val['bank_code'],
                        'beneficiary_bank_name' => $val['beneficiary_name'],
                        'beneficiary_account_number' => $val['beneficiary_account'],
                        'beneficiary_email' => $val['beneficiary_email'],
                        'beneficiary_alias' => $val['beneficiary_alias'],
                        'notes' => 'Payment from apps '.date('d M Y'),
                        'request' => json_encode($toSend),
                        'transactions' => $val['transactions']
                    ];
                }


                $sendToIris = MyHelper::connectIris('Payouts', 'POST','api/v1/payouts', ['payouts' => $dataToSend]);

                if(isset($sendToIris['status']) && $sendToIris['status'] == 'success'){
                    if(isset($sendToIris['response']['payouts']) && !empty($sendToIris['response']['payouts'])){
                        $j = 0;
                        foreach ($sendToIris['response']['payouts'] as $val){
                            $dataToInsert[$j]['response'] = json_encode($val);
                            $dataToInsert[$j]['reference_no'] = $val['reference_no'];
                            $dataToInsert[$j]['disburse_status'] = $arrStatus[$val['status']];

                            $insertToDisburseTransaction = $dataToInsert[$j]['transactions'];
                            unset($dataToInsert[$j]['transactions']);

                            $insert = Disburse::create($dataToInsert[$j]);

                            if($insert){
                                $count = count($insertToDisburseTransaction);
                                for($k=0;$k<$count;$k++){
                                    $insertToDisburseTransaction[$k]['id_disburse'] = $insert['id_disburse'];
                                    $insertToDisburseTransaction[$k]['created_at'] = date('Y-m-d H:i:s');
                                    $insertToDisburseTransaction[$k]['updated_at'] = date('Y-m-d H:i:s');
                                }
                                DisburseTransaction::insert($insertToDisburseTransaction);
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
        $dataRetry = Disburse::join('outlets', 'outlets.id_outlet', 'disburse.id_outlet')
            ->leftJoin('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
            ->where('disburse_status', 'Retry From Failed')
            ->select('outlets.beneficiary_name', 'outlets.beneficiary_account', 'bank_name.bank_code as beneficiary_bank', 'outlets.beneficiary_email',
                'disburse_nominal as amount', 'notes', 'reference_no as ref')
            ->get()->toArray();

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

        //proses approve if setting approver by sistem
        $settingApprover = Setting::where('key', 'disburse_auto_approve_setting')->first();
        if($settingApprover && $settingApprover['value'] == 1){
            $getDataToApprove = Disburse::where('disburse_status', 'Queued')
                ->pluck('reference_no');
            if(!empty($getDataToApprove)){
                $sendApprover = MyHelper::connectIris('Approver', 'POST','api/v1/payouts/approve', ['reference_nos' => $getDataToApprove], 1);
            }
        }

        return 'succes';
    }
}
