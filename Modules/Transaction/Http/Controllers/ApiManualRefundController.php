<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Transaction;
use App\Lib\MyHelper;
use Modules\Transaction\Entities\ManualRefund;

class ApiManualRefundController extends Controller
{
    public function listFailedVoidPayment(Request $request)
    {
        $result = Transaction::select('transactions.id_transaction_group', 'transactions.id_transaction', 'transaction_date', 'transaction_receipt_number', 'users.name', 'users.phone', 'transaction_multiple_payments.type as trasaction_payment_type', 'transaction_grandtotal', 'need_manual_void', 'outlets.outlet_name', 'outlet_code', 'manual_refunds.refund_date', 'manual_refunds.note', 'manual_refunds.images', 'validator.name as validator_name', 'validator.phone as validator_phone', 'failed_void_reason', 'trasaction_type', 'manual_refunds.created_at as confirm_at', \DB::raw('refund_requirement as manual_refund_nominal'))
            ->join('users', 'users.id', 'transactions.id_user')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('transaction_multiple_payments', function ($query) {
                $query->on('transaction_multiple_payments.id_transaction_group', 'transactions.id_transaction_group')
                    ->where('type', '<>', 'Balance');
            })
            ->leftJoin('transaction_payment_balances', 'transaction_payment_balances.id_transaction_group', 'transactions.id_transaction_group')
            ->leftJoin('manual_refunds', 'manual_refunds.id_transaction', 'transactions.id_transaction')
            ->leftJoin('users as validator', 'validator.id', 'manual_refunds.created_by')
            ->where('need_manual_void', '<>', '0')
            ->with('transaction_payment_midtrans', 'transaction_payment_ipay88', 'transaction_payment_xendit');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'transaction_date',
                'transaction_receipt_number',
                'name',
                'phone',
                'trasaction_payment_type',
                'transaction_grandtotal',
                'manual_refund_nominal',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }
        $result->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15);
            $result->each(function ($item) {
                $item->images = array_map(function ($item) {
                    return config('url.storage_url_api') . $item;
                }, json_decode($item->images) ?? []);
            });
            $result = $result->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
            $result['recordsFiltered'] = $result['total'];
        } else {
            $result = $result->get();
        }
        return MyHelper::checkGet($result);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function ($model2) use ($model, $where, $new_rule) {
            $inner = ['transaction_receipt_number', 'order_id'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['name', 'phone'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('users.' . $col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['id_outlet'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('transactions.' . $col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }

    public function confirmManualRefund(Request $request)
    {
        $transaction = Transaction::where('id_transaction', $request->id_transaction)->first();
        if (!$transaction) {
            return [
                'status' => 'success',
                'messages' => [
                    'Transaction not found'
                ]
            ];
        }
        $toSave = [
            'id_transaction' => $transaction->id_transaction,
            'refund_date' => $request->refund_date,
            'note' => $request->note,
            'images' => [],
            'created_by' => $request->user()->id
        ];
        if ($request->images) {
            foreach ($request->images as $image64) {
                $upload = MyHelper::uploadPhoto($image64, 'img/manual_refund/');
                if ($upload['status'] == "success") {
                    $toSave['images'][] = $upload['path'];
                } else {
                    $result = [
                        'status'    => 'fail',
                        'messages'    => ['fail upload image']
                    ];
                    return response()->json($result);
                }
            }
        }

        $toSave['images'] = json_encode($toSave['images']);

        $manual = ManualRefund::updateOrCreate(['id_transaction' => $transaction->id_transaction], $toSave);
        if ($manual) {
            $transaction->update(['need_manual_void' => 2]);
        }
        return MyHelper::checkCreate($manual);
    }
}
