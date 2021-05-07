<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankAccountOutlet;
use Modules\Disburse\Entities\DisburseOutletTransaction;
use Modules\Disburse\Entities\LogEditBankAccount;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\MDR;
use DB;
use Modules\Disburse\Entities\PromoPaymentGatewayTransaction;
use Modules\Disburse\Entities\PromoPaymentGatewayValidation;
use Modules\Disburse\Entities\PromoPaymentGatewayValidationTransaction;
use Modules\Disburse\Entities\RulePromoPaymentGateway;
use App\Http\Models\Transaction;
use Rap2hpoutre\FastExcel\FastExcel;
use File;
use Storage;
use Excel;

class ApiRulePromoPaymentGatewayController extends Controller
{
    function __construct() {
        $this->calculation ="Modules\Disburse\Http\Controllers\ApiIrisController";
    }

    public function index(Request $request){
        $post = $request->json()->all();
        $list = RulePromoPaymentGateway::orderBy('created_at', 'desc');

        if(isset($post['all_data']) && $post['all_data'] == 1){
            $list = $list->get()->toArray();
        }else{
            $list = $list->paginate(30);
        }
        return response()->json(MyHelper::checkGet($list));
    }

    public function store(Request $request){
        $post = $request->json()->all();

        $dateStart = date('Y-m-d', strtotime($post['start_date']));
        $dateEnd = date('Y-m-d', strtotime($post['end_date']));

        $checkPeriod = RulePromoPaymentGateway::whereRaw(
                "(start_date BETWEEN '".$dateStart."' AND '".$dateEnd."' 
                OR '".$dateStart."' BETWEEN start_date AND end_date)"
            )
            ->where('payment_gateway', $post['payment_gateway'])
            ->pluck('name')->toArray();

        if(!empty($checkPeriod)){
            return response()->json(['status' => 'fail', 'messages' => ['Same period with : '.implode(',', $checkPeriod)]]);
        }

        $checkCode = RulePromoPaymentGateway::where('promo_payment_gateway_code', $post['promo_payment_gateway_code'])->first();
        if(!empty($checkCode)){
            return response()->json(['status' => 'fail', 'messages' => ['ID already use']]);
        }

        $dataCreate = [
            'promo_payment_gateway_code' => $post['promo_payment_gateway_code'],
            'name' => $post['name'],
            'payment_gateway' => $post['payment_gateway'],
            'start_date' => $dateStart,
            'end_date' => $dateEnd,
            'limit_promo_total' => $post['limit_promo_total'],
            'cashback_type' => $post['cashback_type'],
            'cashback' => $post['cashback'],
            'maximum_cashback' => str_replace('.', '', $post['maximum_cashback']),
            'minimum_transaction' => str_replace('.', '', $post['minimum_transaction']),
            'charged_type' => $post['charged_type'],
            'charged_payment_gateway' => $post['charged_payment_gateway'],
            'charged_jiwa_group' => $post['charged_jiwa_group'],
            'charged_central' => $post['charged_central'],
            'charged_outlet' => $post['charged_outlet'],
            'mdr_setting' => $post['mdr_setting']
        ];

        if($post['limit_promo_additional_type'] !== 'account'){
            $post['limit_promo_additional_account_type'] = NULL;
        }

        if(!empty($post['limit_promo_additional'])){
            $dataCreate['limit_promo_additional'] = $post['limit_promo_additional'];
            $dataCreate['limit_promo_additional_type'] = $post['limit_promo_additional_type'];
            $dataCreate['limit_promo_additional_account_type'] = $post['limit_promo_additional_account_type'];
        }

        $create = RulePromoPaymentGateway::create($dataCreate);
        return response()->json(MyHelper::checkCreate($create));
    }

    public function update(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])){
            //getOld data
            $promo = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();
            if($promo['start_status'] == 1){
                return response()->json(['status' => 'fail', 'messages' => ['Can not update promo, promo already started']]);
            }

            $idAdmin = auth()->user()->id;
            $dateStart = date('Y-m-d', strtotime($post['start_date']));
            $dateEnd = date('Y-m-d', strtotime($post['end_date']));

            $checkPeriod = RulePromoPaymentGateway::whereRaw(
                    "(start_date BETWEEN '".$dateStart."' AND '".$dateEnd."' 
                    OR '".$dateStart."' BETWEEN start_date AND end_date)"
                )
                ->where('payment_gateway', $post['payment_gateway'])
                ->whereNotIn('id_rule_promo_payment_gateway', [$post['id_rule_promo_payment_gateway']])
                ->pluck('name')->toArray();

            if(!empty($checkPeriod)){
                return response()->json(['status' => 'fail', 'messages' => ['Same period with : '.implode(',', $checkPeriod)]]);
            }

            $checkCode = RulePromoPaymentGateway::where('promo_payment_gateway_code', $post['promo_payment_gateway_code'])->whereNotIn('id_rule_promo_payment_gateway', [$post['id_rule_promo_payment_gateway']])->first();
            if(!empty($checkCode)){
                return response()->json(['status' => 'fail', 'messages' => ['ID already use']]);
            }

            if(empty($post['maximum_cashback'])){
                $post['maximum_cashback'] = 0;
            }

            $dataUpdate = [
                'promo_payment_gateway_code' => $post['promo_payment_gateway_code'],
                'name' => $post['name'],
                'payment_gateway' => $post['payment_gateway'],
                'start_date' => $dateStart,
                'end_date' => $dateEnd,
                'limit_promo_total' => $post['limit_promo_total'],
                'cashback_type' => $post['cashback_type'],
                'cashback' => $post['cashback'],
                'maximum_cashback' => str_replace('.', '', $post['maximum_cashback']),
                'minimum_transaction' => str_replace('.', '', $post['minimum_transaction']),
                'charged_type' => $post['charged_type'],
                'charged_payment_gateway' => $post['charged_payment_gateway'],
                'charged_jiwa_group' => $post['charged_jiwa_group'],
                'charged_central' => $post['charged_central'],
                'charged_outlet' => $post['charged_outlet'],
                'mdr_setting' => $post['mdr_setting'],
                'last_updated_by' => $idAdmin
            ];

            $dataUpdate['limit_promo_additional'] = NULL;
            $dataUpdate['limit_promo_additional_type'] = NULL;
            $dataUpdate['limit_promo_additional_account_type'] = NULL;

            if($post['limit_promo_additional_type'] !== 'account'){
                $post['limit_promo_additional_account_type'] = NULL;
            }

            if(!empty($post['limit_promo_additional'])){
                $dataUpdate['limit_promo_additional'] = $post['limit_promo_additional'];
                $dataUpdate['limit_promo_additional_type'] = $post['limit_promo_additional_type'];
                $dataUpdate['limit_promo_additional_account_type'] = $post['limit_promo_additional_account_type'];
            }

            $update = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->update($dataUpdate);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function detail(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])){
            $detail = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if(empty($detail)){
                return response()->json(['status' => 'fail', 'messages' => ['Data not found']]);
            }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function delete(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])){
            $check = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if(!empty($check) && $check['start_status'] == 1){
                return response()->json(['status' => 'fail', 'messages' => ['Rule promo payment gateway already started.']]);
            }

            $delete = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function start(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])){
            $idAdmin = auth()->user()->id;
            $update = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])
                ->update(['start_status' => 1, 'last_updated_by' => $idAdmin]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function getAvailablePromo($id_transaction, $additionalData = []){
        $currentDate = date('Y-m-d');
        $detailTrx = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
            ->select('transactions.trasaction_type', 'transactions.id_user', 'transaction_pickups.pickup_by',
                'transaction_payment_midtrans.payment_type', 'transaction_payment_ipay88s.payment_method',
                'transaction_payment_midtrans.gross_amount', 'transaction_payment_shopee_pays.user_id_hash',
                'transaction_payment_ipay88s.user_contact', 'transaction_payment_ipay88s.amount as ipay88_amount',
                'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay',
                'transaction_payment_shopee_pays.amount as shopee_amount')
            ->whereNull('transaction_pickups.reject_at')
            ->where('transactions.transaction_payment_status', 'Completed')
            ->where('transactions.id_transaction', $id_transaction)->first();

        if(!empty($detailTrx['payment_type'])){
            $paymentGateway = $detailTrx['payment_type'];
            $userPaymentGateway = null;
            $amount = $detailTrx['gross_amount'];
        }elseif(!empty($detailTrx['id_transaction_payment_shopee_pay'])){
            $paymentGateway = 'ShopeePay';
            $userPaymentGateway = $detailTrx['user_id_hash'];
            $amount = $detailTrx['shopee_amount']/100;
        }elseif (!empty($detailTrx['payment_method'])){
            $paymentGateway = $detailTrx['payment_type'];
            $userPaymentGateway = $detailTrx['user_contact'];
            $amount = $detailTrx['ipay88_amount']/100;
        }else{
            return [];
        }

        if(isset($additionalData['id_rule_promo_payment_gateway']) && !empty($additionalData['id_rule_promo_payment_gateway'])){
            $promos = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $additionalData['id_rule_promo_payment_gateway'])->first();
            if(empty($promos)){
                return [];
            }

            $promos['cashback_customer'] = $additionalData['cashback'];
            if($promos['charged_type'] == 'Nominal'){
                $chargedPG = $promos['charged_payment_gateway'];
                $chargedJiwaGroup = $promos['charged_jiwa_group'];
                $chargedCentral = $promos['charged_central'];
                $chargedOutlet = $promos['charged_outlet'];
            }else{
                $chargedPG = $additionalData['cashback']*($promos['charged_payment_gateway']/100);
                $chargedJiwaGroup = $additionalData['cashback']*($promos['charged_jiwa_group']/100);
                $chargedCentral = $chargedJiwaGroup*($promos['charged_central']/100);
                $chargedOutlet = $chargedJiwaGroup*($promos['charged_outlet']/100);
            }
            $promos['fee_payment_gateway'] = round($chargedPG,2);
            $promos['fee_jiwa_group'] = round($chargedJiwaGroup,2);
            $promos['fee_central'] = round($chargedCentral,2);
            $promos['fee_outlet'] = round($chargedOutlet,2);

            if($promos['limit_promo_additional_account_type'] == 'Jiwa+' || is_null($userPaymentGateway)){
                $promos['id_user'] = $detailTrx['id_user'];
            }else{
                $promos['payment_gateway_user'] = $userPaymentGateway;
            }

            return $promos;
        }else{
            $promos = RulePromoPaymentGateway::where('payment_gateway', $paymentGateway)
                ->whereDate('start_date', '<=', $currentDate)->whereDate('end_date', '>=', $currentDate)
                ->where('minimum_transaction', '<=', $amount)
                ->where('start_status', 1)
                ->get()->toArray();
            //check limitation
            foreach ($promos as $data){
                if($data['cashback_type'] == 'Nominal'){
                    $cashBackCutomer = $data['cashback'];
                }else{
                    $cashBackCutomer = round($amount*($data['cashback']/100), 2);
                    if($cashBackCutomer > $data['maximum_cashback']){
                        $cashBackCutomer = $data['maximum_cashback'];
                    }
                }
                $data['cashback_customer'] = $cashBackCutomer;
                if($data['charged_type'] == 'Nominal'){
                    $chargedPG = $data['charged_payment_gateway'];
                    $chargedJiwaGroup = $data['charged_jiwa_group'];
                    $chargedCentral = $data['charged_central'];
                    $chargedOutlet = $data['charged_outlet'];
                }else{
                    $chargedPG = $cashBackCutomer*($data['charged_payment_gateway']/100);
                    $chargedJiwaGroup = $cashBackCutomer*($data['charged_jiwa_group']/100);
                    $chargedCentral = $chargedJiwaGroup*($data['charged_central']/100);
                    $chargedOutlet = $chargedJiwaGroup*($data['charged_outlet']/100);
                }
                $data['fee_payment_gateway'] = round($chargedPG,2);
                $data['fee_jiwa_group'] = round($chargedJiwaGroup,2);
                $data['fee_central'] = round($chargedCentral,2);
                $data['fee_outlet'] = round($chargedOutlet,2);

                $dataAlreadyUsePromo = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])->count();
                if($dataAlreadyUsePromo >= $data['limit_promo_total']){
                    continue;
                }
                $data['id_user'] = $detailTrx['id_user'];
                if(empty($data['limit_promo_additional'])){
                    return $data;
                }elseif ($data['limit_promo_additional_type'] == 'day'){
                    $dataAlreadyUsePromoAdditional = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->whereDate('created_at', $currentDate)->count();
                }elseif ($data['limit_promo_additional_type'] == 'week'){
                    $currentWeekNumber = date('W', strtotime($currentDate));
                    $currentYear = date('Y', strtotime($currentDate));
                    $dto = new DateTime();
                    $dto->setISODate($currentYear, $currentWeekNumber);
                    $start = $dto->format('Y-m-d');
                    $dto->modify('+6 days');
                    $end = $dto->format('Y-m-d');

                    $dataAlreadyUsePromoAdditional = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)
                        ->whereDate('created_at', '>=',$start)->whereDate('created_at', '<=',$end)->count();
                }elseif ($data['limit_promo_additional_type'] == 'month'){
                    $month = date('m', strtotime($currentDate));
                    $dataAlreadyUsePromoAdditional = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)
                        ->whereMonth('created_at', '=',$month)->count();
                }elseif ($data['limit_promo_additional_type'] == 'account'){
                    if($data['limit_promo_additional_account_type'] == 'Jiwa+' || is_null($userPaymentGateway)){
                        $dataAlreadyUsePromoAdditional = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                            ->where('status_active', 1)
                            ->where('id_user', $detailTrx['id_user'])->count();
                    }else{
                        $data['id_user'] = NULL;
                        $data['payment_gateway_user'] = $userPaymentGateway;
                        $dataAlreadyUsePromoAdditional = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                            ->where('status_active', 1)
                            ->where('payment_gateway_user', $userPaymentGateway)->count();
                    }
                }

                if(isset($dataAlreadyUsePromoAdditional) && $dataAlreadyUsePromoAdditional < $data['limit_promo_additional']){
                    return $data;
                }else{
                    continue;
                }
            }

            return [];
        }
    }

    public function reportListTransaction(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])){
            $report = PromoPaymentGatewayTransaction::join('transactions', 'promo_payment_gateway_transactions.id_transaction', 'transactions.id_transaction')
                ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                ->leftJoin('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
                ->leftJoin('users', 'users.id', 'promo_payment_gateway_transactions.id_user')
                ->where('promo_payment_gateway_transactions.id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])
                ->select('users.name as customer_name', 'users.phone as customer_phone', 'outlets.outlet_code', 'outlets.outlet_name', 'transactions.transaction_receipt_number',
                    'promo_payment_gateway_transactions.*');

            if($post['export'] == 1){
                $report = $report->get()->toArray();
            }else{
                $report = $report->paginate(50);
            }

            return response()->json(MyHelper::checkGet($report));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function validationExport(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])){
            $data = PromoPaymentGatewayTransaction::join('transactions', 'transactions.id_transaction', 'promo_payment_gateway_transactions.id_transaction')
                    ->join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_transactions.id_rule_promo_payment_gateway')
                    ->where('promo_payment_gateway_transactions.id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway']);

            if(isset($post['start_date_periode']) && !empty($post['start_date_periode'])){
                $data = $data->whereDate('promo_payment_gateway_transactions.created_at', '>=', date('Y-m-d', strtotime($post['start_date_periode'])));
            }

            if(isset($post['end_date_periode']) && !empty($post['end_date_periode'])){
                $data = $data->whereDate('promo_payment_gateway_transactions.created_at', '<=', date('Y-m-d', strtotime($post['end_date_periode'])));
            }

            $data = $data->select('promo_payment_gateway_code as ID', 'transaction_receipt_number',
                'transaction_grandtotal', 'total_received_cashback as total_cashback',
                DB::raw('(CASE WHEN status_active = 1 THEN "Get"
                            ELSE "Not" END) as "status_get_promo_getnot"'))->get()->toArray();
            return response()->json(MyHelper::checkGet($data));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function validationImport(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $rule = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if(empty($rule)){
                return response()->json(['status' => 'fail', 'messages' => ['rule promo not found']]);
            }

            $id_correct_get_promo = [];
            $id_must_get_promo = [];
            $id_not_get_promo = [];

            $result = [
                'correct_get_promo' => [],
                'must_get_promo' => [],
                'not_get_promo' => [],
                'wrong_cashback' => [],
                'invalid' => 0,
                'more_msg' => []
            ];
            $data = $post['data'][0]??[];

            foreach ($data as $key => $value) {
                if(empty($value['id'])){
                    $result['invalid']++;
                    continue;
                }

                if($value['id'] != $rule['promo_payment_gateway_code']){
                    $result['more_msg'][] = $value['id'].' tidak terdaftar pada promo '.$rule['name'];
                    continue;
                }

                $getIdTransaction = Transaction::join('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
                            ->where('transaction_receipt_number', $value['transaction_receipt_number'])->first();
                if(empty($getIdTransaction)){
                    $result['more_msg'][] = $value['transaction_receipt_number'].' tidak ditemukan';
                    continue;
                }

                if(!empty($getIdTransaction['id_disburse_outlet'])){
                    $result['more_msg'][] = $value['transaction_receipt_number'].' sudah di disburse';
                    continue;
                }

                $getPromoTrx = PromoPaymentGatewayTransaction::where('id_transaction', $getIdTransaction['id_transaction'])->first();

                // cek cashback
                if(!empty($getPromoTrx) && strtolower($value['status_get_promo_getnot']) == 'get'){
                    $chasbackTrx = number_format($getPromoTrx['total_received_cashback'],2, '.', '');
                    $valueCashback = number_format($value['total_cashback'],2, '.', '');

                    $new_cashback = 0;
                    $old_cashback = 0;
                    if($chasbackTrx != $valueCashback){
                        app($this->calculation)->calculationTransaction($getIdTransaction['id_transaction'], ['id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'], 'cashback' => $valueCashback]);
                        $result['wrong_cashback'][] = $value['transaction_receipt_number'];
                        $new_cashback = $valueCashback;
                        $old_cashback = $chasbackTrx;
                    }

                    $id_correct_get_promo[] = [
                        'id_transaction' => $getIdTransaction['id_transaction'],
                        'validation_status' => 'correct_get_promo',
                        'new_cashback' => $new_cashback,
                        'old_cashback' => $old_cashback
                    ];
                    $result['correct_get_promo'][] = $value['transaction_receipt_number'];
                    $promoUpdate['status_active'] = 1;
                    $promoUpdate['total_received_cashback'] = $valueCashback;
                }elseif(!empty($getPromoTrx) && strtolower($value['status_get_promo_getnot']) == 'not'){
                    $update = [
                        'income_outlet'=> $getIdTransaction['income_outlet'],
                        'income_outlet_old' => 0,
                        'income_central'=> $getIdTransaction['income_central'],
                        'income_central_old' => 0,
                        'expense_central'=> $getIdTransaction['expense_central'],
                        'expense_central_old' => 0,
                        'payment_charge' => $getIdTransaction['payment_charge'],
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
                    DisburseOutletTransaction::where('id_transaction', $getIdTransaction['id_transaction'])->update($update);

                    $result['not_get_promo'][] = $value['transaction_receipt_number'];
                    $id_not_get_promo[] = [
                        'id_transaction' => $getIdTransaction['id_transaction'],
                        'validation_status' => 'not_get_promo',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                    $promoUpdate['status_active'] = 0;
                }elseif(empty($getPromoTrx) && strtolower($value['status_get_promo_getnot']) == 'get'){
                    $valueCashback = number_format($value['total_cashback'],2, '.', '');
                    app($this->calculation)->calculationTransaction($getIdTransaction['id_transaction'], ['id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'], 'cashback' => $valueCashback]);
                    $result['must_get_promo'][] = $value['transaction_receipt_number'];
                    $id_must_get_promo[] = [
                        'id_transaction' => $getIdTransaction['id_transaction'],
                        'validation_status' => 'must_get_promo',
                        'new_cashback' => 0,
                        'old_cashback' => 0
                    ];
                }
            }

            if(!empty($promoUpdate)){
                PromoPaymentGatewayTransaction::where('id_promo_payment_gateway_transaction', $getPromoTrx['id_promo_payment_gateway_transaction'])->update($promoUpdate);
            }

            $arrValidationMerge = array_merge($id_correct_get_promo,$id_must_get_promo,$id_must_get_promo);
            if(!empty($arrValidationMerge)){
                if(!File::exists(public_path().'/promo_payment_gateway_validation')){
                    File::makeDirectory(public_path().'/promo_payment_gateway_validation');
                }

                $directory = 'promo_payment_gateway_validation'.'/'.mt_rand(0, 1000).''.time().''.'.xlsx';
                $store = (new FastExcel($data))->export(public_path().'/'.$directory);

                if(config('configs.STORAGE') != 'local'){
                    $contents = File::get(public_path().'/'.$directory);
                    $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
                    if($store){
                        File::delete(public_path().'/'.$directory);
                    }
                }

                $createValidation = PromoPaymentGatewayValidation::create([
                    'id_user' => auth()->user()->id,
                    'id_rule_promo_payment_gateway' => $rule['id_rule_promo_payment_gateway'],
                    'start_date_periode' => (!empty($post['start_date_periode']) ? date('Y-m-d', strtotime($post['start_date_periode'])) : NULL),
                    'end_date_periode' => (!empty($post['end_date_periode']) ? date('Y-m-d', strtotime($post['end_date_periode'])) : NULL),
                    'correct_get_promo' => count($result['correct_get_promo']),
                    'must_get_promo' => count($result['must_get_promo']),
                    'not_get_promo' => count($result['not_get_promo']),
                    'wrong_cashback' => count($result['wrong_cashback']),
                    'file' => ($store ? $directory : NULL)
                ]);

                if($createValidation){
                    $inserValidation = [];

                    foreach ($arrValidationMerge as $val){
                        $inserValidation[] = [
                            'id_promo_payment_gateway_validation' => $createValidation['id_promo_payment_gateway_validation'],
                            'id_transaction' => $val['id_transaction'],
                            'validation_status' => $val['validation_status'],
                            'new_cashback' => $val['new_cashback'],
                            'old_cashback' => $val['old_cashback']
                        ];
                    }

                    PromoPaymentGatewayValidationTransaction::insert($inserValidation);
                }
            }

            if($result['invalid']){
                $response[] = 'Invalid '.$result['invalid'].' data';
            }
            if($result['correct_get_promo']){
                $response[] = count($result['correct_get_promo']).' correct data';
            }
            if($result['must_get_promo']){
                $response[] = implode(',', $result['must_get_promo']).' must get promo';
            }
            if($result['not_get_promo']){
                $response[] = implode(',', $result['not_get_promo']).' not get promo';
            }
            if($result['wrong_cashback']){
                $response[] = implode(',', $result['wrong_cashback']).' wrong cashback';
            }
            $response = array_merge($response,$result['more_msg']);
            return response()->json(MyHelper::checkGet($response));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function validationReport(Request $request){
        $post = $request->json()->all();
        $list = PromoPaymentGatewayValidation::join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_validation.id_rule_promo_payment_gateway')
                ->leftJoin('users', 'promo_payment_gateway_validation.id_user', 'users.id')
                ->select('promo_payment_gateway_validation.*', 'users.name as admin_name', 'rule_promo_payment_gateway.*')
                ->orderBy('promo_payment_gateway_validation.created_at', 'desc');
        $list = $list->paginate(30);
        return response()->json(MyHelper::checkGet($list));
    }

    public function validationReportDetail(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_promo_payment_gateway_validation']) && !empty($post['id_promo_payment_gateway_validation'])){
            $detail = PromoPaymentGatewayValidation::join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_validation.id_rule_promo_payment_gateway')
                ->select('promo_payment_gateway_validation.*', 'rule_promo_payment_gateway.*')
                ->where('id_promo_payment_gateway_validation', $post['id_promo_payment_gateway_validation'])
                ->first();

            if($detail){
                $detail['list_detail'] = PromoPaymentGatewayValidationTransaction::join('transactions', 'transactions.id_transaction', 'promo_payment_gateway_validation_transactions.id_transaction')
                                ->where('id_promo_payment_gateway_validation', $post['id_promo_payment_gateway_validation'])
                                ->select('promo_payment_gateway_validation_transactions.*', 'transactions.transaction_receipt_number')
                                ->get()->toArray();
            }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
