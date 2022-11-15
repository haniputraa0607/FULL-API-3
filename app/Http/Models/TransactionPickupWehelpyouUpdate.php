<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 16 Jun 2021 13:43:11 +0700.
 */

namespace App\Http\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class TransactionPickupWehelpyouUpdate
 *
 * @property int $id_transaction_pickup_wehelpyou_update
 * @property int $id_transaction
 * @property int $id_transaction_pickup_wehelpyou
 * @property string $poNo
 * @property string $status
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\TransactionPickupWehelpyou $transaction_pickup_wehelpyou
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Http\Models
 */
class TransactionPickupWehelpyouUpdate extends Eloquent
{
    protected $primaryKey = 'id_transaction_pickup_wehelpyou_update';

    protected $casts = [
        'id_transaction' => 'int',
        'id_transaction_pickup_wehelpyou' => 'int'
    ];

    protected $fillable = [
        'id_transaction',
        'id_transaction_pickup_wehelpyou',
        'poNo',
        'status',
        'description',
        'status_id',
        'date'
    ];

    public function transaction_pickup_wehelpyou()
    {
        return $this->belongsTo(\App\Http\Models\TransactionPickupWehelpyou::class, 'id_transaction_pickup_wehelpyou');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
    }
}
