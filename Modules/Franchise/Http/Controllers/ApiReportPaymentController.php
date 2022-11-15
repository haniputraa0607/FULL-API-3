<?php

namespace Modules\Franchise\Http\Controllers;

use Modules\Franchise\Entities\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultetConnection3;
use Modules\Franchise\Entities\DailyReportPayment;
use DB;
use DateTime;

class ApiReportPaymentController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function payments()
    {
        $listPayment = DailyReportPayment::where('refund_with_point', 0)
            ->groupBy('trx_payment')
            ->pluck('trx_payment')->toArray();
        return response()->json(MyHelper::checkGet($listPayment));
    }

    public function summaryPaymentMethod(Request $request)
    {
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultetConnection3::where('id_user_franchise', auth()->user()->id_user_franchise)->first()['id_outlet'] ?? null;

        if ($id_oultet) {
            $listPayment = DailyReportPayment::where('refund_with_point', 0)
                ->groupBy('trx_payment')
                ->select('trx_payment')
                ->get()->toArray();

            if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
                $dateStart = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
                $dateEnd = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));
                $payments = DailyReportPayment::where('trx_date', '>=', $dateStart)
                    ->where('trx_date', '<=', $dateEnd)
                    ->where('id_outlet', $id_oultet)
                    ->where('refund_with_point', 0)
                    ->groupBy('payment_type')->groupBy('trx_payment')
                    ->select(DB::raw('SUM(trx_payment_nominal) as total_amount'), 'trx_payment')->get()->toArray();
            } elseif ((isset($post['filter_type']) && $post['filter_type'] == 'today') || empty($post)) {
                $currentDate = date('Y-m-d');
                $getData = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->select(
                        'transactions.transaction_grandtotal',
                        'transactions.transaction_receipt_number',
                        'transaction_pickups.order_id',
                        'transactions.id_transaction',
                        'transactions.transaction_date',
                        'payment_type',
                        'payment_method',
                        'transaction_payment_midtrans.gross_amount',
                        'transaction_payment_ipay88s.id_transaction_payment_ipay88',
                        'transaction_payment_ipay88s.amount',
                        'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay',
                        'transaction_payment_shopee_pays.amount as shopee_amount',
                        'transaction_payment_subscriptions.subscription_nominal',
                        'balance_nominal'
                    )
                    ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                    ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                    ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
                    ->leftJoin('transaction_payment_subscriptions', 'transactions.id_transaction', '=', 'transaction_payment_subscriptions.id_transaction')
                    ->leftJoin('transaction_payment_balances', 'transactions.id_transaction', '=', 'transaction_payment_balances.id_transaction')
                    ->where('transactions.id_outlet', $id_oultet)
                    ->whereDate('transactions.transaction_date', $currentDate)
                    ->where('transactions.transaction_payment_status', 'Completed')
                    ->whereNull('reject_at')->get()->toArray();

                $payments = [];
                foreach ($getData as $val) {
                    $payment = '';
                    $paymentAmount = 0;
                    if (!empty($val['payment_type'])) {
                        $payment = $val['payment_type'];
                        $paymentAmount = $val['gross_amount'];
                    } elseif (!empty($val['id_transaction_payment_ipay88'])) {
                        $payment = $val['payment_method'];
                        $paymentAmount = $val['amount'] / 100;
                    } elseif (!empty($val['id_transaction_payment_shopee_pay'])) {
                        $payment = 'ShopeePay';
                        $paymentAmount = $val['shopee_amount'] / 100;
                    }

                    if (!empty($payment)) {
                        $check = array_search($payment, array_column($payments, 'trx_payment'));
                        if ($check === false) {
                            $payments[] = [
                                'trx_payment' => $payment,
                                'total_amount' => $paymentAmount
                            ];
                        } else {
                            $payments[$check]['total_amount'] = $payments[$check]['total_amount'] + $paymentAmount;
                        }
                    }


                    if (!empty($val['subscription_nominal'])) {
                        $check = array_search('Subscription', array_column($payments, 'trx_payment'));
                        if ($check === false) {
                            $payments[] = [
                                'trx_payment' => 'Subscription',
                                'total_amount' => $val['subscription_nominal']
                            ];
                        } else {
                            $payments[$check]['total_amount'] = $payments[$check]['total_amount'] + $val['subscription_nominal'];
                        }
                    }

                    if (!empty($val['balance_nominal'])) {
                        $check = array_search('Jiwa Poin', array_column($payments, 'trx_payment'));
                        if ($check === false) {
                            $payments[] = [
                                'trx_payment' => 'Jiwa Poin',
                                'total_amount' => $val['balance_nominal']
                            ];
                        } else {
                            $payments[$check]['total_amount'] = $payments[$check]['total_amount'] + $val['balance_nominal'];
                        }
                    }
                }
            }

            //merge data
            foreach ($listPayment as $val) {
                $payment = $val['trx_payment'];

                $check = array_search($payment, array_column($payments, 'trx_payment'));
                if ($check === false) {
                    $payments[] = [
                        'trx_payment' => $payment,
                        'total_amount' => 0
                    ];
                }
            }

            usort($payments, function ($a, $b) {
                return $a['trx_payment'] <=> $b['trx_payment'];
            });
            return response()->json(MyHelper::checkGet($payments));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function summaryDetailPaymentMethod(Request $request)
    {
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultetConnection3::where('id_user_franchise', auth()->user()->id_user_franchise)->first()['id_outlet'] ?? null;

        if ($id_oultet) {
            $list = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->join('users', 'users.id', '=', 'transactions.id_user')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', '=', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
                ->leftJoin('transaction_payment_subscriptions', 'transactions.id_transaction', '=', 'transaction_payment_subscriptions.id_transaction')
                ->leftJoin('transaction_payment_balances', 'transactions.id_transaction', '=', 'transaction_payment_balances.id_transaction')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                ->where('transactions.id_outlet', $id_oultet)
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at');

            if (strtolower($post['trx_payment']) == 'shopeepay') {
                $list = $list->select(
                    'disburse_outlet_transactions.payment_charge',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_receipt_number',
                    'transaction_pickups.order_id',
                    'transactions.id_transaction',
                    'transactions.transaction_date',
                    'users.name',
                    DB::raw('(transaction_payment_shopee_pays.amount/100) as amount'),
                    'transaction_payment_balances.balance_nominal as balance_amount',
                    'transaction_payment_subscriptions.subscription_nominal',
                    '"ShopeePay" as payment_name'
                )->whereNotNull('transaction_payment_shopee_pays.id_transaction_payment_shopee_pay');
            } elseif (strtolower($post['trx_payment']) == 'subscription') {
                $list = $list->select(
                    'disburse_outlet_transactions.payment_charge',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_receipt_number',
                    'transaction_pickups.order_id',
                    'transactions.id_transaction',
                    'transactions.transaction_date',
                    'users.name',
                    'transaction_payment_subscriptions.subscription_nominal',
                    'transaction_payment_balances.balance_nominal as balance_amount',
                    DB::raw('(CASE 
                        WHEN transaction_payment_midtrans.gross_amount IS NOT NULL THEN transaction_payment_midtrans.gross_amount
                        WHEN transaction_payment_ipay88s.amount IS NOT NULL THEN transaction_payment_ipay88s.amount/100
                        WHEN transaction_payment_shopee_pays.amount IS NOT NULL THEN transaction_payment_shopee_pays.amount/100
                        ELSE NULL END) as amount'),
                    DB::raw('(CASE 
                        WHEN transaction_payment_midtrans.gross_amount IS NOT NULL THEN transaction_payment_midtrans.payment_type
                        WHEN transaction_payment_ipay88s.amount IS NOT NULL THEN transaction_payment_ipay88s.payment_method
                        WHEN transaction_payment_shopee_pays.amount IS NOT NULL THEN "ShopeePay"
                        ELSE NULL END) as payment_name')
                )
                    ->whereNotNull('transaction_payment_subscriptions.id_transaction_payment_subscription');
            } elseif (strtolower($post['trx_payment']) == 'jiwa poin') {
                $list = $list->select(
                    'disburse_outlet_transactions.payment_charge',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_receipt_number',
                    'transaction_pickups.order_id',
                    'transactions.id_transaction',
                    'transactions.transaction_date',
                    'users.name',
                    'transaction_payment_balances.balance_nominal as balance_amount',
                    'transaction_payment_subscriptions.subscription_nominal',
                    DB::raw('(CASE 
                        WHEN transaction_payment_midtrans.gross_amount IS NOT NULL THEN transaction_payment_midtrans.gross_amount
                        WHEN transaction_payment_ipay88s.amount IS NOT NULL THEN transaction_payment_ipay88s.amount/100
                        WHEN transaction_payment_shopee_pays.amount IS NOT NULL THEN transaction_payment_shopee_pays.amount/100
                        ELSE NULL END) as amount'),
                    DB::raw('(CASE 
                        WHEN transaction_payment_midtrans.gross_amount IS NOT NULL THEN transaction_payment_midtrans.payment_type
                        WHEN transaction_payment_ipay88s.amount IS NOT NULL THEN transaction_payment_ipay88s.payment_method
                        WHEN transaction_payment_shopee_pays.amount IS NOT NULL THEN "ShopeePay"
                        ELSE NULL END) as payment_name')
                )->whereNotNull('transaction_payment_balances.id_transaction_payment_balance')
                    ->whereNotNull('transaction_payment_balances.id_transaction_payment_balance');
            } else {
                $list = $list->select(
                    'disburse_outlet_transactions.payment_charge',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_receipt_number',
                    'transaction_pickups.order_id',
                    'transactions.id_transaction',
                    'transactions.transaction_date',
                    'users.name',
                    'transaction_payment_balances.balance_nominal as balance_amount',
                    'transaction_payment_subscriptions.subscription_nominal',
                    DB::raw('(CASE WHEN transaction_payment_midtrans.gross_amount IS NULL THEN transaction_payment_ipay88s.amount/100
                        ELSE transaction_payment_midtrans.gross_amount END) as amount'),
                    DB::raw('(CASE WHEN transaction_payment_midtrans.gross_amount IS NOT NULL THEN transaction_payment_midtrans.payment_type
                        ELSE transaction_payment_ipay88s.payment_method END) as payment_name')
                )
                    ->where(function ($q) use ($post) {
                        $q->where('transaction_payment_midtrans.payment_type', $post['trx_payment'])->orWhere('transaction_payment_ipay88s.payment_method', $post['trx_payment']);
                    })
                    ->where(function ($q) use ($post) {
                        $q->whereNotNull('transaction_payment_midtrans.id_transaction_payment')->orWhereNotNull('transaction_payment_ipay88s.id_transaction_payment_ipay88');
                    });
            }

            if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
                $dateStart = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
                $dateEnd = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));
                $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
            } else {
                $currentDate = date('Y-m-d');
                $list = $list->whereDate('transactions.transaction_date', $currentDate);
            }

            if (isset($post['conditions']) && !empty($post['conditions'])) {
                $rule = $post['rule'] ?? 'and';

                if ($rule == 'and') {
                    foreach ($post['conditions'] as $condition) {
                        if (!empty($condition['subject'])) {
                            if ($condition['operator'] == '=') {
                                $list->where($condition['subject'], $condition['parameter']);
                            } else {
                                $list->where($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            }
                        }
                    }
                } else {
                    $list->where(function ($q) use ($post) {
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
            if ($post['export'] == 1) {
                $list = $list->orderBy($order, $orderType)->get()->toArray();
            } else {
                $list = $list->orderBy($order, $orderType)->paginate(30)->toArray();
                $data = $list['data'];
                if ($order == 'amount' && $orderType == 'asc') {
                    usort($data, function ($a, $b) {
                        return $a['amount'] <=> $b['amount'];
                    });
                } elseif ($order == 'amount' && $orderType == 'desc') {
                    usort($data, function ($a, $b) {
                        return $a['amount'] < $b['amount'];
                    });
                }
                $list['data'] = $data;
            }
            return response()->json(MyHelper::checkGet($list));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function summaryChart(Request $request)
    {
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultetConnection3::where('id_user_franchise', auth()->user()->id_user_franchise)->first()['id_outlet'] ?? null;

        if ($id_oultet && !empty($post['date_start']) && !empty($post['date_start'])) {
            $dateStart = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
            $dateEnd = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));
            $payments = DailyReportPayment::where('refund_with_point', 0)
                ->groupBy('trx_payment')
                ->select('trx_payment')
                ->orderBy('trx_payment', 'asc')
                ->get()->toArray();
            $data = [];
            $date = [];
            foreach ($payments as $payment) {
                $begin = new DateTime($dateStart);
                $end   = new DateTime($dateEnd);

                $get = DailyReportPayment::where('refund_with_point', 0)
                        ->where('trx_payment', $payment['trx_payment'])
                        ->where('trx_date', '>=', $dateStart)
                        ->where('trx_date', '<=', $dateEnd)
                        ->where('id_outlet', $id_oultet)
                        ->select('trx_payment', 'trx_date', 'trx_payment_nominal as amount')
                        ->get()->toArray();

                $tmp = [];
                for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                    $date[] = $i->format("Y-m-d");
                    $check = array_search($i->format("Y-m-d") . " 00:00:00", array_column($get, 'trx_date'));
                    if ($check !== false) {
                        $tmp[] = (int)$get[$check]['amount'];
                    } else {
                        $tmp[] = 0;
                    }
                }
                $data[] = [
                    'name' => $payment['trx_payment'],
                    'data' => $tmp
                ];
            }

            $result = [
                'series' => $data,
                'date' => array_unique($date)
            ];
            return response()->json(MyHelper::checkGet($result));
        }
        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function listPayment(Request $request)
    {
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultetConnection3::where('id_user_franchise', auth()->user()->id_user_franchise)->first()['id_outlet'] ?? null;

        if ($id_oultet) {
            $list = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->join('users', 'users.id', '=', 'transactions.id_user')
                ->select(
                    'disburse_outlet_transactions.payment_charge',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_receipt_number',
                    'transaction_pickups.order_id',
                    'transactions.id_transaction',
                    'transactions.transaction_date',
                    'users.name',
                    'payment_type',
                    'payment_method',
                    'transaction_payment_midtrans.gross_amount',
                    'transaction_payment_ipay88s.amount',
                    'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay',
                    'transaction_payment_shopee_pays.amount as shopee_amount',
                    'transaction_payment_subscriptions.subscription_nominal',
                    'balance_nominal'
                )
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', '=', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
                ->leftJoin('transaction_payment_subscriptions', 'transactions.id_transaction', '=', 'transaction_payment_subscriptions.id_transaction')
                ->leftJoin('transaction_payment_balances', 'transactions.id_transaction', '=', 'transaction_payment_balances.id_transaction')
                ->where('transactions.id_outlet', $id_oultet)
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at');

            if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
                $dateStart = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_start'])));
                $dateEnd = date('Y-m-d', strtotime(str_replace("/", "-", $post['date_end'])));
                $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
            } else {
                $currentDate = date('Y-m-d');
                $list = $list->whereDate('transactions.transaction_date', $currentDate);
            }

            if (isset($post['conditions']) && !empty($post['conditions'])) {
                $rule = $post['rule'] ?? 'and';

                if ($rule == 'and') {
                    foreach ($post['conditions'] as $condition) {
                        if (!empty($condition['subject'])) {
                            if ($condition['subject'] == 'payment') {
                                if (strtolower($condition['operator']) == 'shopeepay') {
                                    $list->whereNotNull('id_transaction_payment_shopee_pay');
                                } elseif (strtolower($condition['operator']) == 'jiwa poin') {
                                    $list->whereNotNull('balance_nominal');
                                } elseif (strtolower($condition['operator']) == 'subscription') {
                                    $list->whereNotNull('subscription_nominal');
                                } else {
                                    $list->where(function ($q) use ($condition) {
                                        $q->where('payment_type', $condition['operator'])->orWhere('payment_method', $condition['operator']);
                                    });
                                }
                            } else {
                                if ($condition['operator'] == '=') {
                                    $list->where($condition['subject'], $condition['parameter']);
                                } else {
                                    $list->where($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                                }
                            }
                        }
                    }
                } else {
                    $list->where(function ($q) use ($post) {
                        foreach ($post['conditions'] as $condition) {
                            if (!empty($condition['subject'])) {
                                if ($condition['subject'] == 'payment') {
                                    if (strtolower($condition['operator']) == 'shopeepay') {
                                        $q->whereNotNull('id_transaction_payment_shopee_pay');
                                    } elseif (strtolower($condition['operator']) == 'jiwa poin') {
                                        $q->whereNotNull('balance_nominal');
                                    } elseif (strtolower($condition['operator']) == 'subscription') {
                                        $q->whereNotNull('subscription_nominal');
                                    } else {
                                        $q->where(function ($q) use ($condition) {
                                            $q->where('payment_type', $condition['operator'])->orWhere('payment_method', $condition['operator']);
                                        });
                                    }
                                } else {
                                    if ($condition['operator'] == '=') {
                                        $q->orWhere($condition['subject'], $condition['parameter']);
                                    } else {
                                        $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                                    }
                                }
                            }
                        }
                    });
                }
            }
            $order = $post['order'] ?? 'transaction_date';
            $orderType = $post['order_type'] ?? 'desc';
            if ($post['export'] == 1) {
                $list = $list->orderBy($order, $orderType)->get()->toArray();
            } else {
                $list = $list->orderBy($order, $orderType)->paginate(30);
            }
            return response()->json(MyHelper::checkGet($list));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }
}
