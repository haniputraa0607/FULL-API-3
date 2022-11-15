<?php

namespace Modules\Franchise\Http\Controllers;

use Modules\Franchise\Entities\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\BankName;
use Modules\Franchise\Entities\Disburse;
use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiReportDisburseController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summary(Request $request)
    {
        $post = $request->json()->all();
        if (!empty($post['id_outlet'])) {
            $query1 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->where('disburse.disburse_status', 'Success');
            $query2 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->where(function ($q) {
                    $q->whereNull('disburse_status')
                        ->orWhereIn('disburse_status', ['Queued', 'Hold']);
                })->where(function ($q) {
                    $q->where('transactions.transaction_flag_invalid', 'Valid')
                        ->orWhereNull('transactions.transaction_flag_invalid');
                });
            $query4 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->whereIn('disburse_status', ['Retry From Failed', 'Retry From Failed Payouts', 'Fail', 'Failed Create Payouts'])
                ->where(function ($q) {
                    $q->where('transactions.transaction_flag_invalid', 'Valid')
                        ->orWhereNull('transactions.transaction_flag_invalid');
                });

            if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
                $dateStart = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
                $dateEnd = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));
                $query1 = $query1->whereDate('disburse.created_at', '>=', $dateStart)->whereDate('disburse.created_at', '<=', $dateEnd);
                $query2 = $query2->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
                $query4 = $query4->whereDate('disburse.created_at', '>=', $dateStart)->whereDate('disburse.created_at', '<=', $dateEnd);
            } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
                $currentDate = date('Y-m-d');
                $query1 = $query1->whereDate('disburse.created_at', $currentDate);
                $query2 = $query2->whereDate('transactions.transaction_date', $currentDate);
                $query4 = $query4->whereDate('disburse.created_at', $currentDate);
            }
            $success = $query1->selectRaw('SUM(disburse_outlet_transactions.fee_item) AS total_fee_item, SUM(disburse_outlet_transactions.payment_charge) AS total_mdr_charged,
                    SUM(disburse_outlet_transactions.income_outlet) AS total_income')->first();
            $unprocessed = $query2->selectRaw('SUM(disburse_outlet_transactions.fee_item) AS total_fee_item, SUM(disburse_outlet_transactions.payment_charge) AS total_mdr_charged,
                    SUM(disburse_outlet_transactions.income_outlet) AS total_income')->first();
            $fail = $query4->selectRaw('SUM(disburse_outlet_transactions.fee_item) AS total_fee_item, SUM(disburse_outlet_transactions.payment_charge) AS total_mdr_charged,
                    SUM(disburse_outlet_transactions.income_outlet) AS total_income')->first();

            $resSucc = $success['total_income'] ?? 0;
            $resUn = $unprocessed['total_income'] ?? 0;
            $resFail = $fail['total_income'] ?? 0;

            $resMDRSucc = $success['total_mdr_charged'] ?? 0;
            $resMDRUn = $unprocessed['total_mdr_charged'] ?? 0;
            $resMDRFail = $fail['total_mdr_charged'] ?? 0;

            $resFeeSucc = $success['total_fee_item'] ?? 0;
            $resFeeUn = $unprocessed['total_fee_item'] ?? 0;
            $resFeeFail = $fail['total_fee_item'] ?? 0;

            $result = [
                [
                    'title' => 'Total Pendapatan',
                    'amount' => number_format($resSucc + $resUn + $resFail, 2, ",", "."),
                    'tooltip' => 'Jumlah pendapatan yang akan diterima oleh outlet'
                ],
                [
                    'title' => 'Settlement Berhasil',
                    'amount' => number_format($resSucc, 2, ",", "."),
                    'tooltip' => 'Jumlah pendapatan outlet yang sudah sukses diberikan'
                ],
                [
                    'title' => 'Settlement Berikutnya',
                    'amount' => number_format($resUn, 2, ",", "."),
                    'tooltip' => 'Jumlah pendapatan outlet yang belum diberikan'
                ],
                [
                    'title' => 'Settlement Gagal',
                    'amount' => number_format($resFail, 2, ",", "."),
                    'tooltip' => 'Jumlah pendapatan outlet yang gagal diberikan'
                ],
                [
                    'title' => 'Total Biaya Jasa',
                    'amount' => number_format($resFeeUn + $resFeeFail + $resFeeSucc, 2, ",", "."),
                    'tooltip' => 'Jumlah potongan fee yang dibayarkan outlet ke Jiwa Group'
                ],
                [
                    'title' => 'Total MDR',
                    'amount' => number_format($resMDRUn + $resMDRSucc + $resMDRFail, 2, ",", "."),
                    'tooltip' => 'Jumlah potongan fee untuk payment gateway'
                ]
            ];

            return response()->json(MyHelper::checkGet($result));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function listTransaction(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['id_outlet'])) {
            $data = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->leftJoin('users', 'users.id', 'transactions.id_user')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->select('disburse_outlet_transactions.income_outlet', 'transactions.transaction_receipt_number', 'transaction_pickups.order_id', 'transactions.id_transaction', 'transactions.transaction_date', 'users.name');

            if ($post['status'] == 'unprocessed') {
                $data->where(function ($q) {
                    $q->whereNull('disburse_status')
                        ->orWhereIn('disburse_status', ['Queued', 'Hold']);
                })->where(function ($q) {
                    $q->where('transactions.transaction_flag_invalid', 'Valid')
                        ->orWhereNull('transactions.transaction_flag_invalid');
                });
            } elseif ($post['status'] == 'invalid') {
                $data->whereIn('transactions.transaction_flag_invalid', ['Pending Invalid', 'Invalid']);
            }

            if (
                isset($post['date_start']) && !empty($post['date_start']) &&
                isset($post['date_end']) && !empty($post['date_end'])
            ) {
                $start_date = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
                $end_date = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));

                $data->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            if (isset($post['conditions']) && !empty($post['conditions'])) {
                $rule = $post['rule'] ?? 'and';

                if ($rule == 'and') {
                    foreach ($post['conditions'] as $condition) {
                        if (!empty($condition['subject'])) {
                            if ($condition['operator'] == '=') {
                                $data->where($condition['subject'], $condition['parameter']);
                            } else {
                                $data->where($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            }
                        }
                    }
                } else {
                    $data->where(function ($q) use ($post) {
                        foreach ($post['conditions'] as $condition) {
                            if (!empty($condition['subject'])) {
                                if ($condition['operator'] == '=') {
                                    $q->orWhere($condition['subject'], $condition['parameter']);
                                } else {
                                    $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                                }
                            }
                        }
                    });
                }
            }
            $order = $post['order'] ?? 'transaction_date';
            $orderType = $post['order_type'] ?? 'desc';

            $data = $data->orderBy($order, $orderType)->paginate(30);
            return response()->json(MyHelper::checkGet($data));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function listDisburse(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['id_outlet'])) {
            $data = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->leftJoin('bank_name', 'bank_name.bank_code', 'disburse.beneficiary_bank_name')
                ->join('outlets', 'outlets.id_outlet', 'disburse_outlet.id_outlet')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->groupBy('disburse_outlet.id_disburse_outlet');

            if ($post['status'] == 'success') {
                $data->where('disburse.disburse_status', 'Success');
            } elseif ($post['status'] == 'fail') {
                $data->where(function ($q) {
                    $q->where('transactions.transaction_flag_invalid', 'Valid')
                        ->orWhereNull('transactions.transaction_flag_invalid');
                })
                ->whereIn('disburse_status', ['Fail', 'Failed Create Payouts', 'Retry From Failed', 'Retry From Failed Payouts']);
            }

            if (
                isset($post['date_start']) && !empty($post['date_start']) &&
                isset($post['date_end']) && !empty($post['date_end'])
            ) {
                $start_date = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
                $end_date = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));

                $data->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
            }

            if (isset($post['conditions']) && !empty($post['conditions'])) {
                $rule = $post['rule'] ?? 'and';

                if ($rule == 'and') {
                    foreach ($post['conditions'] as $condition) {
                        if (!empty($condition['subject'])) {
                            if ($condition['subject'] == 'beneficiary_bank_name' || $condition['subject'] == 'disburse_status') {
                                if ($condition['operator'] == 'Fail') {
                                    $data->whereIn('disburse_status', ['Fail', 'Failed Create Payouts']);
                                } else {
                                    $data->where('disburse.' . $condition['subject'], $condition['operator']);
                                }
                            } else {
                                if ($condition['operator'] == '=') {
                                    $data->where('disburse.' . $condition['subject'], $condition['parameter']);
                                } else {
                                    $data->where('disburse.' . $condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                                }
                            }
                        }
                    }
                } else {
                    $data->where(function ($q) use ($post) {
                        foreach ($post['conditions'] as $condition) {
                            if (!empty($condition['subject'])) {
                                if ($condition['subject'] == 'beneficiary_bank_name' || $condition['subject'] == 'disburse_status') {
                                    if ($condition['operator'] == 'Fail') {
                                        $q->whereIn('disburse_status', ['Fail', 'Failed Create Payouts']);
                                    } else {
                                        $q->where('disburse.' . $condition['subject'], $condition['operator']);
                                    }
                                } else {
                                    if ($condition['operator'] == '=') {
                                        $q->orWhere('disburse.' . $condition['subject'], $condition['parameter']);
                                    } else {
                                        $q->orWhere('disburse.' . $condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                                    }
                                }
                            }
                        }
                    });
                }
            }
            $order = $post['order'] ?? 'created_at';
            $orderType = $post['order_type'] ?? 'desc';

            if (isset($post['export']) && $post['export'] == 1) {
                if ($post['status'] == 'success') {
                    $data = $data->selectRaw('disburse_status as "Status", bank_name.bank_name as "Nama Bank", CONCAT(" ",disburse.beneficiary_account_number) as "No Rekening", disburse.beneficiary_name as "Nama Penerima", DATE_FORMAT(disburse.created_at, "%d %M %Y %H:%i") as "Tanggal Disburse", CONCAT(outlets.outlet_code, " - ", outlets.outlet_name) as "Outlet", SUM(disburse_outlet_transactions.income_outlet) as "Nominal",
                        total_fee_item as "Total Biaya Jasa", SUM(disburse_outlet_transactions.payment_charge) as "Total MDR"')
                        ->get()->toArray();
                } else {
                    $data = $data->selectRaw('disburse_status as "Status", bank_name.bank_name as "Nama Bank", CONCAT(" ",disburse.beneficiary_account_number) as "No Rekening", disburse.beneficiary_name as "Nama Penerima", DATE_FORMAT(disburse.created_at, "%d %M %Y %H:%i") as "Tanggal Disburse", CONCAT(outlets.outlet_code, " - ", outlets.outlet_name) as "Outlet", SUM(disburse_outlet_transactions.income_outlet) as "Nominal",
                        total_fee_item as "Total Biaya Jasa", SUM(disburse_outlet_transactions.payment_charge) as "Total MDR"')
                        ->get()->toArray();
                }
            } else {
                $data = $data->select('disburse.disburse_status', 'disburse.created_at as disburse_date', 'disburse.reference_no', 'disburse_outlet.*', 'disburse.beneficiary_name', 'disburse.beneficiary_account_number', 'bank_name.bank_name')->orderBy('disburse.' . $order, $orderType)->paginate(30);
            }

            return response()->json(MyHelper::checkGet($data));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function detailDisburse(Request $request)
    {
        $post = $request->json()->all();

        $disburse = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->join('outlets', 'outlets.id_outlet', 'disburse_outlet.id_outlet')
            ->leftJoin('bank_name', 'bank_name.bank_code', 'disburse.beneficiary_bank_name')
            ->where('disburse_outlet.id_disburse_outlet', $post['id_disburse_outlet'])
            ->select(
                'disburse_outlet.id_disburse_outlet',
                'outlets.outlet_name',
                'outlets.outlet_code',
                'disburse.id_disburse',
                'disburse_outlet.disburse_nominal',
                'disburse.disburse_status',
                'disburse.beneficiary_account_number',
                'disburse.beneficiary_name',
                'disburse.created_at',
                'disburse.updated_at',
                'bank_name.bank_code',
                'bank_name.bank_name',
                'disburse.error_message'
            )->first();
        $data = Transaction::join('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_balances', 'transaction_payment_balances.id_transaction', 'transactions.id_transaction')
            ->where('disburse_outlet_transactions.id_disburse_outlet', $post['id_disburse_outlet'])
            ->select('disburse_outlet_transactions.*', 'transactions.*', 'transaction_payment_balances.balance_nominal');

        $config = [];
        if (isset($post['export']) && $post['export'] == 1) {
            $data = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                ->join('disburse_outlet_transactions as dot', 'dot.id_transaction', 'transactions.id_transaction')
                ->leftJoin('transaction_payment_balances', 'transaction_payment_balances.id_transaction', 'transactions.id_transaction')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
                ->where('dot.id_disburse_outlet', $post['id_disburse_outlet'])
                ->with(['transaction_payment_subscription' => function ($q) {
                    $q->join('subscription_user_vouchers', 'subscription_user_vouchers.id_subscription_user_voucher', 'transaction_payment_subscriptions.id_subscription_user_voucher')
                        ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->leftJoin('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription');
                }, 'vouchers.deal', 'promo_campaign', 'subscription_user_voucher.subscription_user.subscription'])
                ->select(
                    'transactions.id_subscription_user_voucher',
                    'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay',
                    'payment_type',
                    'payment_method',
                    'dot.*',
                    'outlets.outlet_name',
                    'outlets.outlet_code',
                    'transactions.transaction_receipt_number',
                    'transactions.transaction_date',
                    'transactions.transaction_shipment_go_send',
                    'transactions.transaction_shipment',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_discount_delivery',
                    'transactions.transaction_discount',
                    'transactions.transaction_subtotal',
                    'transactions.id_promo_campaign_promo_code'
                )
                ->get()->toArray();
        } else {
            $data = $data->paginate(25);
        }

        $result = [
            'status' => 'success',
            'result' => [
                'data_disburse' => $disburse,
                'list_trx' => $data,
                'config' => $config
            ]
        ];
        return response()->json($result);
    }

    public function listBank()
    {
        $bank = BankName::select('id_bank_name', 'bank_code', 'bank_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($bank));
    }
}
