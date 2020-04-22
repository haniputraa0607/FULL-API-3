<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPickupGoSend extends Model
{
    protected $primaryKey = 'id_transaction_pickup_go_send';

	protected $casts = [
		'id_transaction_pickup' => 'int'
	];

	protected $fillable = [
		'id_transaction_pickup',
		'origin_name',
		'origin_phone',
		'origin_address',
		'origin_note',
		'origin_latitude',
		'origin_longitude',
		'destination_name',
		'destination_phone',
		'destination_address',
		'destination_note',
		'destination_latitude',
		'destination_longitude',
		'go_send_id',
		'go_order_no',
		'latest_status',
		'driver_id',
		'driver_name',
		'driver_phone',
		'driver_photo',
		'vehicle_number',
		'created_at',
		'updated_at'
	];

	public function transaction_pickup()
	{
		return $this->belongsTo(\App\Http\Models\TransactionPickup::class, 'id_transaction_pickup');
	}
}
