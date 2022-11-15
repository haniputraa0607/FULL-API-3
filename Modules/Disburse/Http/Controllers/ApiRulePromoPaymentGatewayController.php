<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\TransactionPaymentMidtran;
use App\Jobs\ValidationPromoPaymentGatewayJob;
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
use Modules\Disburse\Entities\RulePromoPaymentGatewayBrand;
use Modules\Franchise\Entities\TransactionProduct;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Rap2hpoutre\FastExcel\FastExcel;
use File;
use Storage;
use Excel;
use DateTime;

class ApiRulePromoPaymentGatewayController extends Controller
{
    public function __construct()
    {
        $this->calculation = "Modules\Disburse\Http\Controllers\ApiIrisController";
    }

    public function index(Request $request)
    {
        $post = $request->json()->all();
        $list = RulePromoPaymentGateway::orderBy('created_at', 'desc');

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'promo_payment_gateway_code' || $row['subject'] == 'name') {
                            if ($row['operator'] == '=') {
                                $list->where($row['subject'], $row['parameter']);
                            } else {
                                $list->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        } else {
                            $list->where($row['subject'], $row['operator']);
                        }
                    }
                }
            } else {
                $list->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'promo_payment_gateway_code' || $row['subject'] == 'name') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere($row['subject'], $row['parameter']);
                                } else {
                                    $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                                }
                            } else {
                                $subquery->orWhere($row['subject'], $row['operator']);
                            }
                        }
                    }
                });
            }
        }

        if (isset($post['all_data']) && $post['all_data'] == 1) {
            $list = $list->where('validation_status', 0)->get()->toArray();
        } else {
            $list = $list->paginate(30);
        }
        return response()->json(MyHelper::checkGet($list));
    }

    public function store(Request $request)
    {
        $post = $request->json()->all();

        $dateStart = date('Y-m-d', strtotime($post['start_date']));
        $dateEnd = date('Y-m-d', strtotime($post['end_date']));

        $checkPeriod = RulePromoPaymentGateway::whereRaw(
            "(start_date BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "' 
                OR '" . $dateStart . "' BETWEEN start_date AND end_date)"
        )
            ->where('payment_gateway', $post['payment_gateway'])
            ->pluck('name')->toArray();

        if (!empty($checkPeriod)) {
            return response()->json(['status' => 'fail', 'messages' => ['Same period with : ' . implode(',', $checkPeriod)]]);
        }

        $checkCode = RulePromoPaymentGateway::where('promo_payment_gateway_code', $post['promo_payment_gateway_code'])->first();
        if (!empty($checkCode)) {
            return response()->json(['status' => 'fail', 'messages' => ['ID already use']]);
        }

        if (empty($post['brands'])) {
            $post['operator_brand'] = null;
        }

        $dataCreate = [
            'promo_payment_gateway_code' => $post['promo_payment_gateway_code'],
            'name' => $post['name'],
            'payment_gateway' => $post['payment_gateway'],
            'operator_brand' => $post['operator_brand'],
            'start_date' => $dateStart,
            'end_date' => $dateEnd,
            'maximum_total_cashback' => str_replace('.', '', $post['maximum_total_cashback']),
            'limit_promo_total' => $post['limit_promo_total'] ?? null,
            'limit_per_user_per_day' => $post['limit_per_user_per_day'] ?? null,
            'limit_promo_additional_day' => $post['limit_promo_additional_day'] ?? null,
            'limit_promo_additional_week' => $post['limit_promo_additional_week'] ?? null,
            'limit_promo_additional_month' => $post['limit_promo_additional_month'] ?? null,
            'limit_promo_additional_account' => $post['limit_promo_additional_account'] ?? null,
            'limit_promo_additional_account_type' => $post['limit_promo_additional_account_type'] ?? null,
            'cashback_type' => $post['cashback_type'],
            'cashback' => ($post['cashback_type'] == 'Nominal' ? str_replace('.', '', $post['cashback']) : $post['cashback']),
            'maximum_cashback' => str_replace('.', '', $post['maximum_cashback'] ?? 0),
            'minimum_transaction' => str_replace('.', '', $post['minimum_transaction']),
            'charged_type' => $post['charged_type'],
            'charged_payment_gateway' => $post['charged_payment_gateway'],
            'charged_jiwa_group' => $post['charged_jiwa_group'],
            'charged_central' => $post['charged_central'],
            'charged_outlet' => $post['charged_outlet'],
            'mdr_setting' => $post['mdr_setting']
        ];

        if (empty($post['limit_promo_additional_account'])) {
            $dataCreate['limit_promo_additional_account_type'] = null;
        }

        $create = RulePromoPaymentGateway::create($dataCreate);

        if ($create && !empty($post['brands'])) {
            $insertBrand = [];
            foreach ($post['brands'] as $id_brand) {
                $insertBrand[] = [
                    'id_rule_promo_payment_gateway' => $create['id_rule_promo_payment_gateway'],
                    'id_brand' => $id_brand,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                 ];
            }

            RulePromoPaymentGatewayBrand::insert($insertBrand);
        }
        return response()->json(MyHelper::checkCreate($create));
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            //getOld data
            $promo = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();
            if ($promo['start_status'] == 1) {
                return response()->json(['status' => 'fail', 'messages' => ['Can not update promo, promo already started']]);
            }

            $idAdmin = auth()->user()->id;
            $dateStart = date('Y-m-d', strtotime($post['start_date']));
            $dateEnd = date('Y-m-d', strtotime($post['end_date']));

            $checkPeriod = RulePromoPaymentGateway::whereRaw(
                "(start_date BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "' 
                    OR '" . $dateStart . "' BETWEEN start_date AND end_date)"
            )
                ->where('payment_gateway', $post['payment_gateway'])
                ->whereNotIn('id_rule_promo_payment_gateway', [$post['id_rule_promo_payment_gateway']])
                ->pluck('name')->toArray();

            if (!empty($checkPeriod)) {
                return response()->json(['status' => 'fail', 'messages' => ['Same period with : ' . implode(',', $checkPeriod)]]);
            }

            $checkCode = RulePromoPaymentGateway::where('promo_payment_gateway_code', $post['promo_payment_gateway_code'])->whereNotIn('id_rule_promo_payment_gateway', [$post['id_rule_promo_payment_gateway']])->first();
            if (!empty($checkCode)) {
                return response()->json(['status' => 'fail', 'messages' => ['ID already use']]);
            }

            if (empty($post['maximum_cashback'])) {
                $post['maximum_cashback'] = 0;
            }

            if (empty($post['brands'])) {
                $post['operator_brand'] = null;
            }

            $dataUpdate = [
                'promo_payment_gateway_code' => $post['promo_payment_gateway_code'],
                'name' => $post['name'],
                'payment_gateway' => $post['payment_gateway'],
                'operator_brand' => $post['operator_brand'],
                'start_date' => $dateStart,
                'end_date' => $dateEnd,
                'maximum_total_cashback' => str_replace('.', '', $post['maximum_total_cashback']),
                'limit_promo_total' => $post['limit_promo_total'],
                'limit_per_user_per_day' => $post['limit_per_user_per_day'] ?? null,
                'limit_promo_additional_day' => $post['limit_promo_additional_day'] ?? null,
                'limit_promo_additional_week' => $post['limit_promo_additional_week'] ?? null,
                'limit_promo_additional_month' => $post['limit_promo_additional_month'] ?? null,
                'limit_promo_additional_account' => $post['limit_promo_additional_account'] ?? null,
                'limit_promo_additional_account_type' => $post['limit_promo_additional_account_type'] ?? null,
                'cashback_type' => $post['cashback_type'],
                'cashback' => ($post['cashback_type'] == 'Nominal' ? str_replace('.', '', $post['cashback']) : $post['cashback']),
                'maximum_cashback' => str_replace('.', '', $post['maximum_cashback'] ?? 0),
                'minimum_transaction' => str_replace('.', '', $post['minimum_transaction']),
                'charged_type' => $post['charged_type'],
                'charged_payment_gateway' => $post['charged_payment_gateway'],
                'charged_jiwa_group' => $post['charged_jiwa_group'],
                'charged_central' => $post['charged_central'],
                'charged_outlet' => $post['charged_outlet'],
                'mdr_setting' => $post['mdr_setting'],
                'last_updated_by' => $idAdmin
            ];

            if (empty($post['limit_promo_additional_account'])) {
                $dataUpdate['limit_promo_additional_account_type'] = null;
            }

            $update = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->update($dataUpdate);
            RulePromoPaymentGatewayBrand::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->delete();
            if ($update && !empty($post['brands'])) {
                $insertBrand = [];
                foreach ($post['brands'] as $id_brand) {
                    $insertBrand[] = [
                        'id_rule_promo_payment_gateway' => $post['id_rule_promo_payment_gateway'],
                        'id_brand' => $id_brand,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                RulePromoPaymentGatewayBrand::insert($insertBrand);
            }
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $detail = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if (empty($detail)) {
                return response()->json(['status' => 'fail', 'messages' => ['Data not found']]);
            }
            $detail['current_brand'] = RulePromoPaymentGatewayBrand::join('brands', 'brands.id_brand', 'rule_promo_payment_gateway_brand.id_brand')
                                        ->where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])
                                        ->select('rule_promo_payment_gateway_brand.*', 'name_brand')
                                        ->get()->toArray();
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function delete(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $check = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if (!empty($check) && $check['start_status'] == 1) {
                return response()->json(['status' => 'fail', 'messages' => ['Rule promo payment gateway already started.']]);
            }

            $delete = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function start(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $idAdmin = auth()->user()->id;
            $update = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])
                ->update(['start_status' => 1, 'last_updated_by' => $idAdmin]);
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function markAsValid(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $idAdmin = auth()->user()->id;
            $check = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if ($check['validation_status'] == 1) {
                return response()->json(['status' => 'fail', 'messages' => ['This promo completed validation']]);
            }

            if ($check['end_date'] >= date('Y-m-d')) {
                return response()->json(['status' => 'fail', 'messages' => ['Can not validation this promo because this promo on going']]);
            }

            $update = DisburseOutletTransaction::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->update(['status_validation_promo_payment_gateway' => 1]);

            if ($update) {
                $update = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])
                    ->update(['validation_status' => 1, 'last_updated_by' => $idAdmin]);
            }

            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function getAvailablePromo($id_transaction, $additionalData = [])
    {
        $currentDate = date('Y-m-d');
        $detailTrx = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
            ->select(
                'transactions.trasaction_type',
                'transactions.id_user',
                'transaction_pickups.pickup_by',
                'transaction_payment_midtrans.payment_type',
                'transaction_payment_ipay88s.payment_method',
                'transaction_payment_midtrans.gross_amount',
                'transaction_payment_shopee_pays.user_id_hash',
                'transaction_payment_ipay88s.user_contact',
                'transaction_payment_ipay88s.amount as ipay88_amount',
                'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay',
                'transaction_payment_shopee_pays.amount as shopee_amount'
            )
            ->where('transactions.transaction_payment_status', 'Completed')
            ->where('transactions.id_transaction', $id_transaction)->first();

        if (!empty($detailTrx['payment_type'])) {
            $paymentGateway = $detailTrx['payment_type'];
            $userPaymentGateway = null;
            $amount = $detailTrx['gross_amount'];
        } elseif (!empty($detailTrx['id_transaction_payment_shopee_pay'])) {
            $paymentGateway = 'ShopeePay';
            $userPaymentGateway = $detailTrx['user_id_hash'];
            $amount = $detailTrx['shopee_amount'] / 100;
        } elseif (!empty($detailTrx['payment_method'])) {
            $paymentGateway = $detailTrx['payment_method'];
            $userPaymentGateway = $detailTrx['user_contact'];
            $amount = $detailTrx['ipay88_amount'] / 100;
        } else {
            return [];
        }

        if (isset($additionalData['id_rule_promo_payment_gateway']) && !empty($additionalData['id_rule_promo_payment_gateway'])) {
            $promos = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $additionalData['id_rule_promo_payment_gateway'])->first();
            if (empty($promos)) {
                return [];
            }

            if (isset($additionalData['cashback'])) {
                $promos['cashback_customer'] = $additionalData['cashback'];
            } else {
                if ($promos['cashback_type'] == 'Nominal') {
                    $cashBackCutomer = $promos['cashback'];
                } else {
                    $cashBackCutomer = round($amount * ($promos['cashback'] / 100), 2);
                    if ($cashBackCutomer > $promos['maximum_cashback']) {
                        $cashBackCutomer = $promos['maximum_cashback'];
                    }
                }
                $promos['cashback_customer'] = $cashBackCutomer;
            }

            if ($promos['charged_type'] == 'Nominal') {
                $chargedPG = $promos['charged_payment_gateway'];
                $chargedJiwaGroup = $promos['charged_jiwa_group'];
                $chargedCentral = $promos['charged_central'];
                $chargedOutlet = $promos['charged_outlet'];
            } else {
                $chargedPG = $promos['cashback_customer'] * ($promos['charged_payment_gateway'] / 100);
                $chargedJiwaGroup = $promos['cashback_customer'] * ($promos['charged_jiwa_group'] / 100);
                $chargedCentral = $chargedJiwaGroup * ($promos['charged_central'] / 100);
                $chargedOutlet = $chargedJiwaGroup * ($promos['charged_outlet'] / 100);
            }
            $promos['fee_payment_gateway'] = round($chargedPG, 2);
            $promos['fee_jiwa_group'] = round($chargedJiwaGroup, 2);
            $promos['fee_central'] = round($chargedCentral, 2);
            $promos['fee_outlet'] = round($chargedOutlet, 2);

            if ($promos['limit_promo_additional_account_type'] == 'Jiwa+' || is_null($userPaymentGateway)) {
                $promos['id_user'] = $detailTrx['id_user'];
            } else {
                $promos['payment_gateway_user'] = $userPaymentGateway;
            }

            return $promos;
        } else {
            $promos = RulePromoPaymentGateway::where('payment_gateway', $paymentGateway)
                ->whereDate('start_date', '<=', $currentDate)->whereDate('end_date', '>=', $currentDate)
                ->where('minimum_transaction', '<=', $amount)
                ->where('start_status', 1)
                ->get()->toArray();
            //check limitation
            foreach ($promos as $data) {
                $getRuleBrand = RulePromoPaymentGatewayBrand::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])->pluck('id_brand')->toArray();
                if (!empty($getRuleBrand)) {
                    $productBrand = TransactionProduct::where('id_transaction', $id_transaction)
                                    ->whereIn('id_brand', $getRuleBrand)
                                    ->groupBy('id_brand')
                                    ->pluck('id_brand')->toArray();
                    if ($data['operator_brand'] == 'or' && empty($productBrand)) {
                        continue;
                    } elseif ($data['operator_brand'] == 'and' && count($getRuleBrand) != count($productBrand)) {
                        continue;
                    }
                }

                if ($data['cashback_type'] == 'Nominal') {
                    $cashBackCutomer = $data['cashback'];
                } else {
                    $cashBackCutomer = round($amount * ($data['cashback'] / 100), 2);
                    if ($cashBackCutomer > $data['maximum_cashback']) {
                        $cashBackCutomer = $data['maximum_cashback'];
                    }
                }
                $data['cashback_customer'] = $cashBackCutomer;
                if ($data['charged_type'] == 'Nominal') {
                    $chargedPG = $data['charged_payment_gateway'];
                    $chargedJiwaGroup = $data['charged_jiwa_group'];
                    $chargedCentral = $data['charged_central'];
                    $chargedOutlet = $data['charged_outlet'];
                } else {
                    $chargedPG = $cashBackCutomer * ($data['charged_payment_gateway'] / 100);
                    $chargedJiwaGroup = $cashBackCutomer * ($data['charged_jiwa_group'] / 100);
                    $chargedCentral = $chargedJiwaGroup * ($data['charged_central'] / 100);
                    $chargedOutlet = $chargedJiwaGroup * ($data['charged_outlet'] / 100);
                }
                $data['fee_payment_gateway'] = round($chargedPG, 2);
                $data['fee_jiwa_group'] = round($chargedJiwaGroup, 2);
                $data['fee_central'] = round($chargedCentral, 2);
                $data['fee_outlet'] = round($chargedOutlet, 2);

                //check maximum cashback
                if ($data['maximum_total_cashback'] > 0) {
                    $currentTotalCashback = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)->sum('total_received_cashback');
                    $currentTotalCashback = $currentTotalCashback + $cashBackCutomer;
                    if ($currentTotalCashback > $data['maximum_total_cashback']) {
                        continue;
                    }
                }

                $dataAlreadyUsePromo = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])->where('status_active', 1)->count();
                if ($dataAlreadyUsePromo >= $data['limit_promo_total'] && $data['limit_promo_total'] != 0) {
                    continue;
                }
                $data['id_user'] = $detailTrx['id_user'];

                if (!empty($data['limit_per_user_per_day'])) {
                    //check limit per user per day
                    $getCountUserPerday = Transaction::join('promo_payment_gateway_transactions', 'promo_payment_gateway_transactions.id_transaction', 'transactions.id_transaction')
                        ->whereDate('promo_payment_gateway_transactions.created_at', date('Y-m-d'))->where('transactions.id_user', $detailTrx['id_user'])
                        ->where('promo_payment_gateway_transactions.id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)
                        ->count();

                    if ($getCountUserPerday >= $data['limit_per_user_per_day']) {
                        continue;
                    }
                }

                if (!empty($data['limit_promo_additional_day'])) {
                    $dataAlreadyUsePromoAdditionalDay = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)
                        ->whereDate('created_at', $currentDate)->count();

                    if ($dataAlreadyUsePromoAdditionalDay >= $data['limit_promo_additional_day']) {
                        continue;
                    }
                }

                if (!empty($data['limit_promo_additional_week'])) {
                    $currentWeekNumber = date('W', strtotime($currentDate));
                    $currentYear = date('Y', strtotime($currentDate));
                    $dto = new DateTime();
                    $dto->setISODate($currentYear, $currentWeekNumber);
                    $start = $dto->format('Y-m-d');
                    $dto->modify('+6 days');
                    $end = $dto->format('Y-m-d');

                    $dataAlreadyUsePromoAdditionalWeek = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)
                        ->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->count();

                    if ($dataAlreadyUsePromoAdditionalWeek >= $data['limit_promo_additional_week']) {
                        continue;
                    }
                }

                if (!empty($data['limit_promo_additional_month'])) {
                    $month = date('m', strtotime($currentDate));
                    $dataAlreadyUsePromoAdditionalMonth = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                        ->where('status_active', 1)
                        ->whereMonth('created_at', '=', $month)->count();

                    if ($dataAlreadyUsePromoAdditionalMonth >= $data['limit_promo_additional_month']) {
                        continue;
                    }
                }

                if (!empty($data['limit_promo_additional_account'])) {
                    if ($data['limit_promo_additional_account_type'] == 'Jiwa+' || is_null($userPaymentGateway)) {
                        $dataAlreadyUsePromoAdditionalAccount = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                            ->where('status_active', 1)
                            ->where('id_user', $detailTrx['id_user'])->count();
                    } else {
                        $data['id_user'] = null;
                        $data['payment_gateway_user'] = $userPaymentGateway;
                        $dataAlreadyUsePromoAdditionalAccount = PromoPaymentGatewayTransaction::where('id_rule_promo_payment_gateway', $data['id_rule_promo_payment_gateway'])
                            ->where('status_active', 1)
                            ->where('payment_gateway_user', $userPaymentGateway)->count();
                    }

                    if ($dataAlreadyUsePromoAdditionalAccount >= $data['limit_promo_additional_account']) {
                        continue;
                    }
                }

                return $data;
            }

            return [];
        }
    }

    public function reportListTransaction(Request $request)
    {
        $post = $request->json()->all();

        $report = PromoPaymentGatewayTransaction::join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_transactions.id_rule_promo_payment_gateway')
            ->join('transactions', 'promo_payment_gateway_transactions.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
            ->leftJoin('users', 'users.id', 'transactions.id_user')
            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
            ->where('status_active', 1)
            ->orderBy('transaction_pickups.reject_at', 'asc')
            ->select(
                'transaction_pickups.reject_at',
                'users.name as customer_name',
                'users.phone as customer_phone',
                'transactions.transaction_receipt_number',
                'transactions.transaction_date',
                'promo_payment_gateway_transactions.*',
                'rule_promo_payment_gateway.name',
                'transaction_payment_ipay88s.user_contact',
                'transaction_payment_shopee_pays.user_id_hash'
            );

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $report = $report->where('promo_payment_gateway_transactions.id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway']);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'id_rule_promo_payment_gateway') {
                            $report->where('rule_promo_payment_gateway.id_rule_promo_payment_gateway', $row['operator']);
                        } else {
                            if ($row['operator'] == '=') {
                                $report->where($row['subject'], $row['parameter']);
                            } else {
                                $report->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                }
            } else {
                $report->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'id_rule_promo_payment_gateway') {
                                $subquery->orWhere('promo_payment_gateway_validation.id_rule_promo_payment_gateway', $row['operator']);
                            } else {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere($row['subject'], $row['parameter']);
                                } else {
                                    $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                                }
                            }
                        }
                    }
                });
            }
        }

        if (isset($post['export']) && $post['export'] == 1) {
            $report = $report->get()->toArray();
        } else {
            $report = $report->paginate(50);
        }

        return response()->json(MyHelper::checkGet($report));
    }

    public function summaryListTransaction(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $summary = PromoPaymentGatewayTransaction::where('promo_payment_gateway_transactions.id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])
                ->where('status_active', 1)
                ->selectRaw('SUM(amount) as total_amount, SUM(total_received_cashback) as total_cashback, COUNT(id_transaction) as total_transaction')
                ->first();

            return response()->json(MyHelper::checkGet($summary));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function validationExport(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $data = PromoPaymentGatewayTransaction::join('transactions', 'transactions.id_transaction', 'promo_payment_gateway_transactions.id_transaction')
                    ->join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_transactions.id_rule_promo_payment_gateway')
                    ->where('promo_payment_gateway_transactions.id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway']);

            if (isset($post['start_date_periode']) && !empty($post['start_date_periode'])) {
                $data = $data->whereDate('promo_payment_gateway_transactions.created_at', '>=', date('Y-m-d', strtotime($post['start_date_periode'])));
            }

            if (isset($post['end_date_periode']) && !empty($post['end_date_periode'])) {
                $data = $data->whereDate('promo_payment_gateway_transactions.created_at', '<=', date('Y-m-d', strtotime($post['end_date_periode'])));
            }

            $data = $data->select(
                'promo_payment_gateway_code as ID',
                'transaction_receipt_number',
                'transaction_grandtotal',
                'total_received_cashback as total_cashback',
                DB::raw('(CASE WHEN status_active = 1 THEN "Get"
                            ELSE "Not" END) as "status_get_promo_getnot"')
            )->get()->toArray();
            return response()->json(MyHelper::checkGet($data));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function validationImport(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_rule_promo_payment_gateway']) && !empty($post['id_rule_promo_payment_gateway'])) {
            $data = $post['data'][0] ?? [];

            if (empty($data)) {
                return response()->json(['status' => 'fail', 'messages' => ['data can not be empty']]);
            }

            //check status
            $checStatus = RulePromoPaymentGateway::where('id_rule_promo_payment_gateway', $post['id_rule_promo_payment_gateway'])->first();

            if ($checStatus['validation_status'] == 1) {
                return response()->json(['status' => 'fail', 'messages' => ['This promo completed validation']]);
            }

            if (!File::exists(public_path() . '/promo_payment_gateway_validation')) {
                File::makeDirectory(public_path() . '/promo_payment_gateway_validation');
            }

            $directory = 'promo_payment_gateway_validation' . '/' . mt_rand(0, 1000) . '' . time() . '' . '.xlsx';
            $store = (new FastExcel($data))->export(public_path() . '/' . $directory);

            if (config('configs.STORAGE') != 'local') {
                $contents = File::get(public_path() . '/' . $directory);
                $store = Storage::disk(config('configs.STORAGE'))->put($directory, $contents, 'public');
                if ($store) {
                    File::delete(public_path() . '/' . $directory);
                }
            }

            if (empty($post['override_mdr_status'])) {
                $post['override_mdr_percent_type'] = null;
            }
            $createValidation = PromoPaymentGatewayValidation::create([
                'id_user' => auth()->user()->id,
                'id_rule_promo_payment_gateway' => $post['id_rule_promo_payment_gateway'],
                'reference_by' => $post['reference_by'],
                'validation_cashback_type' => $post['validation_cashback_type'],
                'validation_payment_type' => 'Check',
                'override_mdr_status' => $post['override_mdr_status'],
                'override_mdr_percent_type' => $post['override_mdr_percent_type'],
                'start_date_periode' => (!empty($post['start_date_periode']) ? date('Y-m-d', strtotime($post['start_date_periode'])) : null),
                'end_date_periode' => (!empty($post['end_date_periode']) ? date('Y-m-d', strtotime($post['end_date_periode'])) : null),
                'file' => ($store ? $directory : null),
                'processing_status' => 'In Progress'
            ]);

            if ($createValidation) {
                ValidationPromoPaymentGatewayJob::dispatch([
                    'data' => $data,
                    'id_promo_payment_gateway_validation' => $createValidation['id_promo_payment_gateway_validation'],
                    'id_rule_promo_payment_gateway' => $post['id_rule_promo_payment_gateway'],
                    'reference_by' => $post['reference_by'],
                    'validation_cashback_type' => $post['validation_cashback_type'],
                    'validation_payment_type' => 'Check',
                    'override_mdr_status' => $post['override_mdr_status'],
                    'override_mdr_percent_type' => $post['override_mdr_percent_type'],
                    'start_date_periode' => $post['start_date_periode'],
                    'end_date_periode' => $post['end_date_periode'],
                ])->onConnection('validationpromopgqueue');
            }
            return response()->json(MyHelper::checkCreate($createValidation));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function validationReport(Request $request)
    {
        $post = $request->json()->all();
        $list = PromoPaymentGatewayValidation::join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_validation.id_rule_promo_payment_gateway')
                ->leftJoin('users', 'promo_payment_gateway_validation.id_user', 'users.id')
                ->select('promo_payment_gateway_validation.*', 'users.name as admin_name', 'rule_promo_payment_gateway.*', 'promo_payment_gateway_validation.created_at as date_validation')
                ->orderBy('promo_payment_gateway_validation.created_at', 'desc');

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'name') {
                            if ($row['operator'] == '=') {
                                $list->where('users.name', $row['parameter']);
                            } else {
                                $list->where('users.name', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'id_rule_promo_payment_gateway') {
                            $list->where('promo_payment_gateway_validation.id_rule_promo_payment_gateway', $row['operator']);
                        }

                        if (
                            $row['subject'] == 'correct_get_promo' || $row['subject'] == 'not_get_promo' ||
                            $row['subject'] == 'must_get_promo' || $row['subject'] == 'wrong_cashback'
                        ) {
                            $list->where($row['subject'], $row['operator'], $row['parameter']);
                        }
                    }
                }
            } else {
                $list->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'name') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('users.name', $row['parameter']);
                                } else {
                                    $subquery->orWhere('users.name', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'id_rule_promo_payment_gateway') {
                                $subquery->orWhere('promo_payment_gateway_validation.id_rule_promo_payment_gateway', $row['operator']);
                            }

                            if (
                                $row['subject'] == 'correct_get_promo' || $row['subject'] == 'not_get_promo' ||
                                $row['subject'] == 'must_get_promo' || $row['subject'] == 'wrong_cashback'
                            ) {
                                $subquery->orWhere($row['subject'], $row['operator'], $row['parameter']);
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(30);
        return response()->json(MyHelper::checkGet($list));
    }

    public function validationReportDetail(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_promo_payment_gateway_validation']) && !empty($post['id_promo_payment_gateway_validation'])) {
            $detail = PromoPaymentGatewayValidation::join('rule_promo_payment_gateway', 'rule_promo_payment_gateway.id_rule_promo_payment_gateway', 'promo_payment_gateway_validation.id_rule_promo_payment_gateway')
                ->leftJoin('users', 'users.id', 'promo_payment_gateway_validation.id_user')
                ->select('promo_payment_gateway_validation.*', 'rule_promo_payment_gateway.*', 'users.name as admin_name', 'promo_payment_gateway_validation.created_at as date_validation')
                ->where('id_promo_payment_gateway_validation', $post['id_promo_payment_gateway_validation'])
                ->first();

            if ($detail) {
                $detail['file'] = config('url.storage_url_api') . $detail['file'];
                $detail['list_detail'] = PromoPaymentGatewayValidationTransaction::leftJoin('transactions', 'transactions.id_transaction', 'promo_payment_gateway_validation_transactions.id_transaction')
                                ->where('id_promo_payment_gateway_validation', $post['id_promo_payment_gateway_validation'])
                                ->select('promo_payment_gateway_validation_transactions.*', 'transactions.transaction_receipt_number')
                                ->get()->toArray();
            }
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
