<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionProduct
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
class TransactionConsultasion extends \App\Http\Models\Template\TransactionService
{
	protected $primaryKey = 'id_transaction_consultasion';

	protected $casts = [
		'id_transaction' => 'int',
		'id_doctor' => 'int',
		'id_customer' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'id_doctor',
		'id_user',
		'consultasion_type',
		'schedule_date',
		'schedule_start_time',
		'schedule_end_time',
		'consultasion_start_time',
		'consultasion_end_time',
		'consultasion_session_price',
		'consultasion_status'
	];

	public function scopeOnlySoon($query)
    {
        return $query->where('consultasion_status', "soon");
    }

	public function scopeOnlyDone($query)
    {
        return $query->where('consultasion_status', "done");
    }
	
}
