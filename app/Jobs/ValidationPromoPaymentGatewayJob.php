<?php

namespace App\Jobs;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionPaymentMidtran;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Disburse\Entities\DisburseOutletTransaction;
use Modules\Disburse\Entities\PromoPaymentGatewayValidation;
use Modules\Disburse\Entities\PromoPaymentGatewayValidationTransaction;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Disburse\Entities\RulePromoPaymentGateway;
use Modules\Disburse\Entities\PromoPaymentGatewayTransaction;

class ValidationPromoPaymentGatewayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$disburse;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data   = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $datas = $this->data;

        $rule = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $datas['id_rule_promo_payment_gateway'])->first();

        if(empty($rule)){
            PromoPaymentGatewayValidation::where('id_promo_payment_gateway_validation', $datas['id_promo_payment_gateway_validation'])->update(['processing_status' => 'Fail']);
            return false;
        }

        $id_correct_get_promo = [];
        $id_must_get_promo = [];
        $id_not_get_promo = [];
        $id_invalid_data = [];

        $result = [
            'correct_get_promo' => [],
            'must_get_promo' => [],
            'not_get_promo' => [],
            'wrong_cashback' => [],
            'invalid_data' => []
        ];
        $data = $datas['data'];

        $allPromoTrx = PromoPaymentGatewayTransaction::join('transactions', 'transactions.id_transaction', 'promo_payment_gateway_transactions.id_transaction')
            ->whereDate('transaction_date', '>=', date('Y-m-d', strtotime($datas['start_date_periode'])))
            ->whereDate('transaction_date', '<=', date('Y-m-d', strtotime($datas['end_date_periode'])))
            ->where('id_rule_promo_payment_gateway', $datas['id_rule_promo_payment_gateway'])->pluck('promo_payment_gateway_transactions.id_transaction')->toArray();

        foreach ($data as $key => $value) {
            $idTransaction = NULL;
            if($datas['reference_by'] == 'transaction_receipt_number'){
                $idTransaction = Transaction::where('transaction_receipt_number', $value['id_reference'])->first()->id_transaction??null;
            }else{
                if(strtolower($rule['payment_gateway']) == 'shopeepay'){
                    $idTransaction = TransactionPaymentShopeePay::where('payment_reference_id', $value['id_reference'])->first()->id_transaction??null;
                }elseif(strtolower($rule['payment_gateway']) == 'ovo'){
                    $idTransaction = TransactionPaymentIpay88::where('payment_id', $value['id_reference'])->first()->id_transaction??null;
                }elseif(strtolower($rule['payment_gateway']) == 'gopay'){
                    $idTransaction = TransactionPaymentMidtran::where('vt_transaction_id', $value['id_reference'])->first()->id_transaction??null;
                }elseif(strtolower($rule['payment_gateway']) == 'credit card'){
                    $idTransaction = TransactionPaymentMidtran::where('vt_transaction_id', $value['id_reference'])->first()->id_transaction??null;
                    if(empty($idTransaction)){
                        $idTransaction = TransactionPaymentIpay88::where('payment_id', $value['id_reference'])->first()->id_transaction??null;
                    }
                }
            }

            if(empty($idTransaction)){
                $id_invalid_data[] = [
                    'reference_id' => $value['id_reference'],
                    'validation_status' => 'invalid_data',
                    'new_cashback' => 0,
                    'old_cashback' => 0
                ];
                continue;
            }

            $disburseTrx = DisburseOutletTransaction::where('id_transaction', $idTransaction)->first();
            if(!empty($disburseTrx['id_disburse_outlet'])){
                continue;
            }

            $checkExistPromo = array_search($idTransaction, $allPromoTrx);

            if($datas['validation_payment_type'] == 'Check' && $datas['reference_by'] == 'transaction_receipt_number'){
                $checkPayment = Transaction::where('transactions.id_transaction', $idTransaction)
                    ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                    ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                    ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
                    ->select('transaction_payment_midtrans.payment_type', 'transaction_payment_ipay88s.payment_method',
                        'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay')
                    ->first();
                if(strtolower($rule['payment_gateway']) == 'shopeepay' && empty($checkPayment['id_transaction_payment_shopee_pay'])){
                    $id_invalid_data[] = [
                        'id_transaction' => $idTransaction,
                        'validation_status' => 'invalid_data',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                    unset($allPromoTrx[$checkExistPromo]);
                    continue;
                }elseif(strtolower($rule['payment_gateway']) == 'ovo' && (empty($checkPayment['payment_method']) || strtolower($checkPayment['payment_method']) != 'ovo')){
                    $id_invalid_data[] = [
                        'id_transaction' => $idTransaction,
                        'validation_status' => 'invalid_data',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                    unset($allPromoTrx[$checkExistPromo]);
                    continue;
                }elseif(strtolower($rule['payment_gateway']) == 'gopay' && (empty($checkPayment['payment_type']) || strtolower($checkPayment['payment_type']) != 'gopay')){
                    $id_invalid_data[] = [
                        'id_transaction' => $idTransaction,
                        'validation_status' => 'invalid_data',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                    unset($allPromoTrx[$checkExistPromo]);
                    continue;
                }elseif(strtolower($rule['payment_gateway']) == 'credit card' && (empty($checkPayment['payment_method']) || strtolower($checkPayment['payment_method']) != 'credit card')){
                    $id_invalid_data[] = [
                        'id_transaction' => $idTransaction,
                        'validation_status' => 'invalid_data',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                    unset($allPromoTrx[$checkExistPromo]);
                }elseif(strtolower($rule['payment_gateway']) == 'credit card' && (empty($checkPayment['payment_type']) || strtolower($checkPayment['payment_type']) != 'credit card')){
                    $id_invalid_data[] = [
                        'id_transaction' => $idTransaction,
                        'validation_status' => 'invalid_data',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                    unset($allPromoTrx[$checkExistPromo]);
                    continue;
                }
            }

            if($checkExistPromo === false){
                if($datas['validation_cashback_type'] == 'Check Cashback'){
                    $valueCashback = number_format($value['cashback'],2, '.', '');
                    app('Modules\Disburse\Http\Controllers\ApiIrisController')->calculationTransaction($idTransaction, [
                        'id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'],
                        'cashback' => $valueCashback,
                        'override_mdr_status' => $datas['override_mdr_status'],
                        'override_mdr_percent_type' => $datas['override_mdr_percent_type'],
                        'mdr' => $value['mdr']??NULL]);
                }else{
                    app('Modules\Disburse\Http\Controllers\ApiIrisController')->calculationTransaction($idTransaction, [
                        'id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'],
                        'override_mdr_status' => $datas['override_mdr_status'],
                        'override_mdr_percent_type' => $datas['override_mdr_percent_type'],
                        'mdr' => $value['mdr']??NULL
                        ]);
                }

                $result['must_get_promo'][] = $value['id_reference'];
                $getPromoTrxAlreadyInsert = PromoPaymentGatewayTransaction::where('id_transaction', $idTransaction)->first();
                $chasbackTrx = $getPromoTrxAlreadyInsert['total_received_cashback']??0;
                $id_invalid_data[] = [
                    'id_transaction' => $idTransaction,
                    'validation_status' => 'must_get_promo',
                    'new_cashback' => 0,
                    'old_cashback' => $chasbackTrx
                ];
            }else{
                $getPromoTrx = PromoPaymentGatewayTransaction::where('id_transaction', $idTransaction)->first();
                $new_cashback = 0;
                $old_cashback = $getPromoTrx['total_received_cashback']??0;

                if($datas['validation_cashback_type'] == 'Check Cashback'){
                    $chasbackTrx = number_format($getPromoTrx['total_received_cashback'],2, '.', '');
                    $valueCashback = number_format($value['cashback'],2, '.', '');
                    if($chasbackTrx != $valueCashback){
                        app('Modules\Disburse\Http\Controllers\ApiIrisController')->calculationTransaction($idTransaction, [
                            'id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'],
                            'cashback' => $valueCashback,
                            'override_mdr_status' => $datas['override_mdr_status'],
                            'override_mdr_percent_type' => $datas['override_mdr_percent_type'],
                            'mdr' => $value['mdr']??NULL]);
                        $result['wrong_cashback'][] = $value['id_reference'];
                        $new_cashback = $valueCashback;
                        $old_cashback = $chasbackTrx;
                        $promoUpdate['total_received_cashback'] = $valueCashback;
                    }
                }else{
                    app('Modules\Disburse\Http\Controllers\ApiIrisController')->calculationTransaction($idTransaction, [
                        'id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'],
                        'override_mdr_status' => $datas['override_mdr_status'],
                        'override_mdr_percent_type' => $datas['override_mdr_percent_type'],
                        'mdr' => $value['mdr']??NULL
                    ]);
                }

                $id_correct_get_promo[] = [
                    'id_transaction' => $idTransaction,
                    'validation_status' => 'correct_get_promo',
                    'new_cashback' => $new_cashback,
                    'old_cashback' => $old_cashback
                ];
                $result['correct_get_promo'][] = $value['id_reference'];
                $promoUpdate['status_active'] = 1;
                unset($allPromoTrx[$checkExistPromo]);
            }

            if(!empty($promoUpdate)){
                PromoPaymentGatewayTransaction::where('id_promo_payment_gateway_transaction', $getPromoTrx['id_promo_payment_gateway_transaction'])->update($promoUpdate);
            }
        }

        $notGetPromo = array_values($allPromoTrx);
        if(!empty($notGetPromo)){
            foreach ($notGetPromo as $dt){
                $disburseTrx = DisburseOutletTransaction::where('id_transaction', $dt)->first();
                if(!empty($disburseTrx['id_disburse_outlet'])){
                    continue;
                }

                $update = [
                    'income_outlet'=> $disburseTrx['income_outlet'],
                    'income_outlet_old' => 0,
                    'income_central'=> $disburseTrx['income_central'],
                    'income_central_old' => 0,
                    'expense_central'=> $disburseTrx['expense_central'],
                    'expense_central_old' => 0,
                    'payment_charge' => $disburseTrx['payment_charge'],
                    'payment_charge_old' => 0,
                    'id_rule_promo_payment_gateway' => null,
                    'fee_promo_payment_gateway_type' => NULL,
                    'fee_promo_payment_gateway' => 0,
                    'fee_promo_payment_gateway_central' => 0,
                    'fee_promo_payment_gateway_outlet' => 0,
                    'charged_promo_payment_gateway' => 0,
                    'charged_promo_payment_gateway_central' => 0,
                    'charged_promo_payment_gateway_outlet' => 0,
                ];
                DisburseOutletTransaction::where('id_transaction', $disburseTrx['id_transaction'])->update($update);

                $result['not_get_promo'][] = $dt;
                $id_not_get_promo[] = [
                    'id_transaction' => $dt,
                    'validation_status' => 'not_get_promo',
                    'new_cashback' => 0,
                    'old_cashback' => 0
                ];
                $promoUpdate['status_active'] = 0;

                if(!empty($promoUpdate)){
                    PromoPaymentGatewayTransaction::where('id_transaction', $dt)->update($promoUpdate);
                }
            }
        }

        $arrValidationMerge = array_merge($id_correct_get_promo,$id_not_get_promo,$id_must_get_promo,$id_invalid_data);
        if(!empty($arrValidationMerge)){
            $inserValidation = [];

            foreach ($arrValidationMerge as $val){
                $inserValidation[] = [
                    'id_promo_payment_gateway_validation' => $datas['id_promo_payment_gateway_validation'],
                    'id_transaction' => $val['id_transaction']??NULL,
                    'reference_id' => $val['reference_id']??NULL,
                    'validation_status' => $val['validation_status'],
                    'new_cashback' => $val['new_cashback'],
                    'old_cashback' => $val['old_cashback']
                ];
            }

            PromoPaymentGatewayValidationTransaction::insert($inserValidation);
        }

        PromoPaymentGatewayValidation::where('id_promo_payment_gateway_validation', $datas['id_promo_payment_gateway_validation'])
            ->update([
                'processing_status' => 'Success',
                'correct_get_promo' => count($result['correct_get_promo']),
                'must_get_promo' => count($result['must_get_promo']),
                'not_get_promo' => count($result['not_get_promo']),
                'wrong_cashback' => count($result['wrong_cashback']),
                'invalid_data' => count($id_invalid_data)
            ]);
    }
}
