<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\StockLog;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\Outlet;
use App\Http\Models\TransactionPaymentMidtran;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Http\Requests\RuleUpdate;
use Modules\Transaction\Http\Requests\TransactionNew;
use App\Lib\MyHelper;
use App\Lib\GoSend;
use App\Lib\Midtrans;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Validator;
use Hash;
use DB;
use Mail;
use Image;

class ApiTransactionRefund extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->xendit         = 'Modules\Xendit\Http\Controllers\XenditController';
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->balance = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function refundPayment($trx)
    {
        $order = TransactionGroup::where('id_transaction_group', $trx['id_transaction_group'])
            ->leftJoin('users', 'transaction_groups.id_user', 'users.id')
            ->first();

        $user = User::find($order['id_user']);
        $shared = \App\Lib\TemporaryDataManager::create('reject_order');
        $refund_failed_process_balance = MyHelper::setting('refund_failed_process_balance');
        $rejectBalance = false;
        $point = 0;
        $transaction = Transaction::where('id_transaction', $trx['id_transaction'])->first();

        $multiple = TransactionMultiplePayment::where('id_transaction_group', $trx['id_transaction_group'])->get()->toArray();
        $nominalBalanceEachTrx = TransactionPaymentBalance::where('id_transaction', $trx['id_transaction'])->first()['balance_nominal'] ?? 0;

        foreach ($multiple as $pay) {
            if ($pay['type'] == 'Balance') {
                $payBalance = TransactionPaymentBalance::find($pay['id_payment']);
                if (!empty($trx['refund_partial'])) {
                    $nominal = $nominalBalanceEachTrx;
                } else {
                    $nominal = $payBalance['balance_nominal'];
                }

                if ($payBalance) {
                    $refund = app($this->balance)->addLogBalance($order['id_user'], $nominal, $trx['id_transaction'], 'Rejected Order Point', $nominal);
                    if ($refund == false) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['Insert Cashback Failed'],
                        ];
                    }
                    $rejectBalance = true;
                }
            } elseif (strtolower($pay['type']) == 'cash') {
                $payCash = TransactionPaymentCash::find($pay['id_payment']);
                if ($payCash) {
                    $order->update([
                        'refund_requirement' => $nominal,
                        'reject_type' => 'refund',
                        'need_manual_void' => 1
                    ]);
                }
            } elseif (strtolower($pay['type']) == 'xendit') {
                $point = 0;
                $payXendit = TransactionPaymentXendit::find($pay['id_payment']);
                if ($payXendit) {
                    $doRefundPayment = MyHelper::setting('refund_xendit');
                    if (!empty($trx['refund_partial'])) {
                        $amountXendit = $trx['refund_partial'] - $nominalBalanceEachTrx;
                    } else {
                        $amountXendit = $payXendit['amount'];
                    }

                    if ($doRefundPayment) {
                        $ewallets = ["OVO","DANA","LINKAJA","SHOPEEPAY","SAKUKU"];
                        if (in_array(strtoupper($payXendit['type']), $ewallets)) {
                            if (!empty($trx['refundnominal'])) {
                                $refund = app($this->xendit)->refund($payXendit['id_transaction_group'], 'trx', [
                                    'amount' => $amountXendit,
                                    'reason' => 'Rejected transaction from merchant'
                                ], $errors);
                            } else {
                                $refund = app($this->xendit)->refund($payXendit['id_transaction_group'], 'trx', [], $errors);
                            }

                            $transaction->update([
                                'reject_type'   => 'refund',
                            ]);

                            if (!$refund) {
                                $transaction->update(['failed_void_reason' => implode(', ', $errors ?: [])]);

                                if ($refund_failed_process_balance) {
                                    $doRefundPayment = false;
                                } else {
                                    $transaction->update(['need_manual_void' => 1, 'refund_requirement' => $amountXendit]);
                                    $order2 = clone $transaction;
                                    $order2->payment_method = 'Xendit';
                                    $order2->manual_refund = $amountXendit;
                                    $order2->payment_reference_number = $payXendit['xendit_id'];
                                    if ($shared['reject_batch'] ?? false) {
                                        $shared['void_failed'][] = $order2;
                                    } else {
                                        $variables = [
                                            'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                        ];
                                        app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                    }
                                }
                            }
                        } else {
                            $transaction->update([
                                'refund_requirement' => $amountXendit,
                                'reject_type' => 'refund',
                                'need_manual_void' => 1
                            ]);
                        }
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        $transaction->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance($transaction['id_user'], $amountXendit, $transaction['id_transaction'], 'Rejected Order Xendit', $amountXendit);
                        if ($refund == false) {
                            return [
                                'status'   => 'fail',
                                'messages' => ['Insert Cashback Failed'],
                            ];
                        }
                        $rejectBalance = true;
                    }
                }
            } else {
                $point = 0;
                $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                if ($payMidtrans) {
                    $doRefundPayment = MyHelper::setting('refund_midtrans');
                    if (!empty($trx['refund_partial'])) {
                        $amountMidtrans = $trx['refund_partial'] - $nominalBalanceEachTrx;
                    } else {
                        $amountMidtrans = $payMidtrans['gross_amount'];
                    }

                    if ($doRefundPayment) {
                        if (!empty($trx['refundnominal'])) {
                            $refund = Midtrans::refundPartial($payMidtrans['vt_transaction_id'], ['reason' => $order['reject_reason'] ?? '', 'amount' => $amountMidtrans]);
                        } else {
                            $refund = Midtrans::refund($payMidtrans['vt_transaction_id'], ['reason' => $order['reject_reason'] ?? '']);
                        }

                        $transaction->update([
                            'reject_type'   => 'refund',
                        ]);
                        if ($refund['status'] != 'success') {
                            $transaction->update(['failed_void_reason' => implode(', ', $refund['messages'] ?? [])]);
                            if ($refund_failed_process_balance) {
                                $doRefundPayment = false;
                            } else {
                                $transaction->update(['need_manual_void' => 1, 'refund_requirement' => $amountMidtrans]);
                                $order2 = clone $transaction;
                                $order2->payment_method = 'Midtrans';
                                $order2->payment_detail = $payMidtrans['payment_type'];
                                $order2->manual_refund = $payMidtrans['gross_amount'];
                                $order2->payment_reference_number = $payMidtrans['vt_transaction_id'];
                                if ($shared['reject_batch'] ?? false) {
                                    $shared['void_failed'][] = $order2;
                                } else {
                                    $variables = [
                                        'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                    ];
                                    app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                }
                            }
                        }
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        $transaction->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance($transaction['id_user'], $amountMidtrans, $transaction['id_transaction'], 'Rejected Order Midtrans', $amountMidtrans);
                        if ($refund == false) {
                            return [
                                'status'    => 'fail',
                                'messages'  => ['Insert Cashback Failed']
                            ];
                        }
                        $rejectBalance = true;
                    }
                }
            }
        }

        //send notif point refund
        if ($rejectBalance == true) {
            $outlet = Outlet::find($transaction['id_outlet']);
            $send = app($this->autocrm)->SendAutoCRM(
                'Rejected Order Point Refund',
                $user['phone'],
                [
                    "outlet_name"      => $outlet['outlet_name'],
                    "transaction_date" => $order['transaction_date'],
                    'id_transaction'   => $trx['id_transaction'] ?? null,
                    'receipt_number'   => $trx['receipt_number'] ?? $order['transaction_receipt_number'],
                    'received_point'   => (string) $point,
                    'order_id'         => ''
                ]
            );
            if ($send != true) {
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed Send notification to customer'],
                ];
            }
        }

        return [
            'status' => 'success',
            'reject_balance' => $rejectBalance,
            'received_point' => (string) $point
        ];
    }
}
