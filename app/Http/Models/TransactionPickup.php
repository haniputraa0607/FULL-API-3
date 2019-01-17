<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPickup extends Model
{
    protected $primaryKey = 'id_transaction_pickup';

	protected $casts = [
		'id_transaction' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'order_id',
		'short_link',
		'pickup_by',
		'pickup_type',
		'pickup_at',
		'receive_at',
		'ready_at',
		'taken_at',
		'reject_at',
		'reject_reason',
		'id_admin_outlet_receive',
		'id_admin_outlet_taken',
		'created_at',
		'updated_at'
	];

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

	public function transaction_pickup_go_send()
	{
		return $this->hasOne(\App\Http\Models\TransactionPickupGoSend::class, 'id_transaction_pickup');
	}
	
	public function admin_receive() 
	{
		return $this->belongsTo(UserOutlet::class, 'id_admin_outlet_receive', 'id_user_outlet');
	}

	public function admin_taken() 
	{
		return $this->belongsTo(UserOutlet::class, 'id_admin_outlet_taken', 'id_user_outlet');
	}
}
