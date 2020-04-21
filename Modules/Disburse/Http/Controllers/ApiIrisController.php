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
            'rejected' => 'Rejected'
        ];
        $data = Disburse::where('reference_no', $reference_no)->update(['disburse_status' => $arrStatus[$status]]);

        if($data){
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail',
                'messages' => ['Failed Update status']]);
        }
    }

    public function disburse(){
        $getData = Transaction::leftJoin('disburse_transactions', 'disburse_transactions.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
            ->where('trasaction_type', '!=', 'Offline')
            ->where('transaction_payment_status', '=', 'Completed')
            ->whereNull('disburse_transactions.id_disburse')
            ->select('transactions.id_outlet', 'transactions.id_transaction', 'transactions.transaction_subtotal',
                'transactions.transaction_grandtotal', 'transactions.transaction_discount', 'transactions.id_promo_campaign_promo_code',
                'bank_name.bank_code', 'outlets.status_franchise',
                'outlets.beneficiary_name', 'outlets.beneficiary_account', 'outlets.beneficiary_email', 'outlets.beneficiary_alias')
            ->with(['transaction_multiple_payment', 'vouchers', 'promo_campaign'])
            ->orderBy('transactions.created_at', 'desc')->get()->toArray();
        $settingGlobalFee = Setting::where('key', 'global_setting_fee')->first()->value_text;
        $settingGlobalFee = json_decode($settingGlobalFee);
        $settingGlobalFeePoint = Setting::where('key', 'global_setting_point_charged')->first()->value_text;
        $settingGlobalFeePoint = json_decode($settingGlobalFeePoint);
        $settingMDRAll = MDR::get()->toArray();

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
                        $amount = 0;
                        $charged = NULL;

                        foreach ($data['transaction_multiple_payment'] as $payments){

                            if(strtolower($payments['type']) == 'midtrans'){
                                $midtrans = TransactionPaymentMidtran::where('id_transaction', $data['id_transaction'])->first()->bank;
                                $keyMidtrans = array_search($midtrans, array_column($settingMDRAll, 'payment_name'));
                                if($keyMidtrans !== false){
                                    $feePGCentral = $settingMDRAll[$keyMidtrans]['mdr_central'];
                                    $feePG = $settingMDRAll[$keyMidtrans]['mdr'];
                                    $feePGType = $settingMDRAll[$keyMidtrans]['percent_type'];
                                    $charged = $settingMDRAll[$keyMidtrans]['charged'];
                                }

                            }elseif (strtolower($payments['type']) == 'balance'){
                                $balanceNominal = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first()->balance_nominal;
                                $feePointCentral = ($settingGlobalFeePoint->central == '' ? 0 : $settingGlobalFeePoint->central);
                                $feePointOutlet = ($settingGlobalFeePoint->outlet == '' ? 0 : $settingGlobalFeePoint->outlet);

                                if((int)$feePointCentral !== 100){
                                    $nominalBalance = $balanceNominal * (floatval($feePointOutlet) / 100);
                                }
                            }elseif(strtolower($payments['type']) == 'ipay88'){
                                $ipay88 = TransactionPaymentIpay88::where('id_transaction', $data['id_transaction'])->first()->payment_method;
                                $keyipay88 = array_search($ipay88, array_column($settingMDRAll, 'payment_name'));
                                if($keyipay88 !== false){
                                    $feePGCentral = $settingMDRAll[$keyipay88]['mdr_central'];
                                    $feePG = $settingMDRAll[$keyipay88]['mdr'];
                                    $feePGType = $settingMDRAll[$keyipay88]['percent_type'];
                                    $charged = $settingMDRAll[$keyipay88]['charged'];
                                }
                            }elseif (strtolower($payments['type']) == 'ovo'){
                                $keyipayOvo = array_search('Ovo', array_column($settingMDRAll, 'payment_name'));
                                if($keyipayOvo !== false){
                                    $feePGCentral = $settingMDRAll[$keyipayOvo]['mdr_central'];
                                    $feePG = $settingMDRAll[$keyipayOvo]['mdr'];
                                    $feePGType = $settingMDRAll[$keyipayOvo]['percent_type'];
                                    $charged = $settingMDRAll[$keyipayOvo]['charged'];
                                }
                            }
                        }

                        $totalChargedPromo = 0;
                        if(!empty($data['vouchers'])){
                            $getDeal = Deal::where('id_deals', $data['vouchers'][0]['id_deals'])->first();
                            $feePromoCentral = $getDeal['charged_central'];
                            $feePromoOutlet = $getDeal['charged_outlet'];
                            if((int) $feePromoCentral !== 100){
                                $totalChargedPromo = $totalChargedPromo + (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                            }
                        }elseif (!empty($data['promo_campaign'])){
                            $feePromoCentral = $data['promo_campaign']['charged_central'];
                            $feePromoOutlet = $data['promo_campaign']['charged_outlet'];
                            if((int) $feePromoCentral !== 100){
                                $totalChargedPromo = $totalChargedPromo + (abs($data['transaction_discount']) * ($feePromoOutlet / 100));
                            }
                        }
                        if($charged == 'Customer'){
                            $totalFee = 0;
                        }elseif($feePGType == 'Percent'){
                            $totalFee = $grandTotal * (($feePGCentral + $feePG) / 100);
                        }else{
                            $totalFee = $feePGCentral + $feePG;
                        }

                        $percentFee = 0;
                        if($data['status_franchise'] == 1){
                            $percentFee = ($settingGlobalFee->fee_outlet == '' ? 0 : $settingGlobalFee->fee_outlet);
                        }else{
                            $percentFee = ($settingGlobalFee->fee_central == '' ? 0 : $settingGlobalFee->fee_central);
                        }

                        $amount = $subTotal - ((floatval($percentFee) / 100) * $subTotal) - $totalFee - $nominalBalance - $totalChargedPromo;

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
                                'transactions' => [
                                    [
                                        'id_transaction' => $data['id_transaction'],
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
                            $arrTmp[$checkOultet]['transactions'][] = [
                                'id_transaction' => $data['id_transaction'],
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
                        'beneficiary_bank_name' => $val['beneficiary_name'],
                        'beneficiary_account_number' => $val['beneficiary_account'],
                        'beneficiary_email' => $val['beneficiary_email'],
                        'beneficiary_alias' => $val['beneficiary_alias'],
                        'notes' => 'Payment from apps '.date('d M Y'),
                        'request' => json_encode($toSend),
                        'transactions' => $val['transactions']
                    ];
                }

                $arrStatus = [
                    'queued' => 'Queued',
                    'processed' => 'Processed',
                    'completed' => 'Success',
                    'failed' => 'Fail',
                    'rejected' => 'Rejected'
                ];
                $sendToIris = MyHelper::connectIris('POST','api/v1/payouts', ['payouts' => $dataToSend]);

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

                return 'success';
            }catch (\Exception $e){
                DB::rollback();
                return 'fail';
            }
        }
    }
}
