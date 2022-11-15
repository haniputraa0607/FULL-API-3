<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionConsultationRecomendation
 *
 * @property int $id_transaction_product
 * @property int $id_transaction
 * @property int $id_product
 * @property int $transaction_product_qty
 * @property int $transaction_product_price
 * @property int $transaction_product_subtotal
 * @property string $transaction_product_note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\Product $product
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionConsultationReschedule extends model
{
    protected $primaryKey = 'id_transaction_consultation_reschedule';

    protected $fillable = [
        'id_transaction',
        'id_transaction_consultation',
        'id_user',
        'id_doctor',
        'schedule_start_time',
        'schedule_end_time',
        'status',
        'id_user_responder'
    ];
}
