<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionShipment
 * 
 * @property int $id_transaction_shipment
 * @property int $id_transaction
 * @property string $depart_name
 * @property string $depart_phone
 * @property string $depart_address
 * @property int $depart_id_city
 * @property string $destination_name
 * @property string $destination_phone
 * @property string $destination_address
 * @property int $destination_id_city
 * @property string $destination_description
 * @property int $shipment_total_weight
 * @property string $shipment_courier
 * @property string $shipment_courier_service
 * @property string $shipment_courier_etd
 * 
 * @property \App\Http\Models\City $city
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionShipment extends Model
{
	protected $primaryKey = 'id_transaction_shipment';
	public $timestamps = false;

	protected $casts = [
		'id_transaction' => 'int',
		'depart_id_city' => 'int',
		'destination_id_city' => 'int',
		'shipment_total_weight' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'order_id',
		'depart_name',
		'depart_phone',
		'depart_address',
		'depart_id_city',
		'destination_name',
		'destination_phone',
		'destination_address',
		'destination_id_city',
		'destination_description',
		'shipment_total_weight',
		'shipment_courier',
		'shipment_courier_service',
		'shipment_courier_etd',
		'short_link',
		'receive_at',
		'id_admin_outlet_receive',
		'send_at',
		'id_admin_outlet_send'
	];

	public function city()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'destination_id_city');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

	public function admin_receive() 
	{
		return $this->belongsTo(UserOutlet::class, 'id_admin_outlet_receive', 'id_user_outlet');
	}

	public function admin_taken() 
	{
		return $this->belongsTo(UserOutlet::class, 'id_admin_outlet_send', 'id_user_outlet');
	}
}
