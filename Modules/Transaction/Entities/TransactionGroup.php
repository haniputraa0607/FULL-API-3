<?php

namespace Modules\Transaction\Entities;

use App\Http\Models\Configs;
use App\Http\Models\LogBalance;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Jobs\FraudJob;
use Illuminate\Database\Eloquent\Model;

class TransactionGroup extends Model
{
    protected $table = 'transaction_groups';

    protected $primaryKey = 'id_transaction_group';

    protected $fillable   = [
        'id_user',
        'transaction_receipt_number',
        'transaction_subtotal',
        'transaction_shipment',
        'transaction_service',
        'transaction_tax',
        'transaction_grandtotal',
        'transaction_discount',
        'transaction_payment_status',
        'transaction_payment_type',
        'transaction_void_date',
        'transaction_group_date',
        'transaction_completed_at'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'id_transaction_group');
    }

    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
    }

    public function triggerPaymentCompleted($data = [])
    {
        // check complete allowed
        if ($this->transaction_payment_status != 'Pending') {
            return $this->transaction_payment_status == 'Completed';
        }

        \DB::beginTransaction();
        $this->update([
            'transaction_payment_status' => 'Completed',
            'transaction_completed_at' => date('Y-m-d H:i:s')
        ]);

        $getTransactions = Transaction::where('id_transaction_group', $this->id_transaction_group)->get();
        foreach ($getTransactions as $transaction){
            $transaction->triggerPaymentCompleted();
        }

        // check fraud
        if ($this->user) {
            $this->user->update([
                'count_transaction_day' => $this->user->count_transaction_day + 1,
                'count_transaction_week' => $this->user->count_transaction_week + 1,
            ]);

            $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->value('is_active');

            if($config_fraud_use_queue == 1){
                FraudJob::dispatch($this->user, $this, 'transaction')->onConnection('fraudqueue');
            }else {
                app('\Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraudTrxOnline($this->user, $this);
            }
        }


        \DB::commit();
        return true;
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCancelled($data = [])
    {
        \DB::beginTransaction();
        // check complete allowed
        if ($this->transaction_payment_status != 'Pending') {
            return $this->transaction_payment_status == 'Completed';
        }

        // update transaction payment cancelled
        $this->update([
            'transaction_payment_status' => 'Cancelled',
            'transaction_void_date' => date('Y-m-d H:i:s')
        ]);

        //reversal balance
        $logBalance = LogBalance::where('id_reference', $this->id_transaction_group)->whereIn('source', ['Online Transaction', 'Transaction'])->where('balance', '<', 0)->get();
        foreach($logBalance as $logB){
            $reversal = app('\Modules\Balance\Http\Controllers\BalanceController')->addLogBalance( $this->id_user, abs($logB['balance']), $this->id_transaction_group, 'Reversal', $this->transaction_grandtotal);
            if (!$reversal) {
                \DB::rollBack();
                return false;
            }
        }

        $getTransactions = Transaction::where('id_transaction_group', $this->id_transaction_group)->get();
        foreach ($getTransactions as $transaction){
            $transaction->triggerPaymentCancelled();
        }

        \DB::commit();
        return true;
    }

}
