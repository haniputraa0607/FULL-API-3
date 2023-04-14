<?php

namespace Modules\Xendit\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Transaction;
use Modules\Transaction\Entities\TransactionGroup;

class TransactionPaymentXendit extends Model
{
    public $primaryKey  = 'id_transaction_payment_xendit';
    protected $fillable = [
        'id_transaction',
        'id_transaction_group',
        'xendit_id',
        'payment_id',
        'external_id',
        'business_id',
        'phone',
        'type',
        'amount',
        'expiration_date',
        'failure_code',
        'status',
        'checkout_url',
        'account_number',
    ];
    public $items = null;

    public static $prettyText = [
        'DANA' => 'DANA',
        'OVO' => 'OVO',
        'LINKAJA' => 'LinkAja',
    ];

    public function transaction_group()
    {
        return $this->belongsTo(TransactionGroup::class, 'id_transaction_group');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction');
    }

    public function pay(&$errors = [])
    {
        if ($this->checkout_url || $this->business_id) {
            return true;
        }

        $transactionType = Transaction::where('id_transaction_group', $this->id_transaction_group)->first()['trasaction_type'] ?? 'trx';
        $transactionType = ($transactionType == 'Delivery' ? 'trx' : $transactionType);

        if (\Cache::has('xendit_confirm_' . $this->id_transaction_group)) {
            $create = \Cache::get('xendit_confirm_' . $this->id_transaction_group);
        } else {
            $xenditController = app('Modules\Xendit\Http\Controllers\XenditController');
            $create = $xenditController->create($this->type, $this->external_id, $this->amount, [
                'phone' => $this->phone,
                'items' => $this->items,
                'type' => $transactionType
            ], $errors);
        }

        if ($create) {
            \Cache::put('xendit_confirm_' . $this->id_transaction_group, $create, now()->addMinutes(10));

            $this->xendit_id = $create['id'] ?? null;
            $this->business_id = $create['business_id'] ?? null;
            $this->checkout_url = $create['invoice_url'] ?? null;
            $this->external_id = $create['external_id'] ?? $this->external_id;
            $this->status = $create['status'] ?? null;
            $result = true;
        } else {
            $result = false;
        }
        $save = $this->save();
        return $result;
    }
    public function payVA(&$errors = [])
    {
        if ($this->account_number) {
            return true;
        }

        $transactionType = Transaction::where('id_transaction_group', $this->id_transaction_group)->first()['trasaction_type'] ?? 'trx';
        $transactionType = ($transactionType == 'Delivery' ? 'trx' : $transactionType);

        if (\Cache::has('xendit_confirm_' . $this->id_transaction_group)) {
            $create = \Cache::get('xendit_confirm_' . $this->id_transaction_group);
        } else {
            $xenditController = app('Modules\Xendit\Http\Controllers\XenditController');
            $create = $xenditController->createVA($this->type, $this->external_id, $this->amount, [
                'phone' => $this->phone,
                'items' => $this->items,
                'type' => $transactionType
            ], $errors);
        }

        if ($create) {
            \Cache::put('xendit_confirm_' . $this->id_transaction_group, $create, now()->addMinutes(10));

            $this->xendit_id = $create['id'] ?? null;
            $this->business_id = $create['business_id'] ?? null;
            $this->account_number = $create['account_number'] ?? null;
            $this->external_id = $create['external_id'] ?? $this->external_id;
            $this->status = $create['status'] ?? null;
            $this->expiration_date = $create['expiration_date'] ? date('Y-m-d H:i:s', strtotime($create['expiration_date'])) : null;
            $result = true;
        } else {
            $result = false;
        }
        $save = $this->save();
        return $result;
    }
}
