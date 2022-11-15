<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 16 Jun 2021 13:42:43 +0700.
 */

namespace App\Http\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class TransactionPickupWehelpyou
 *
 * @property int $id_transaction_pickup_wehelpyou
 * @property int $id_transaction_pickup
 * @property string $vehicle_type
 * @property bool $box
 * @property string $sender_name
 * @property string $sender_phone
 * @property string $sender_address
 * @property string $sender_latitude
 * @property string $sender_longitude
 * @property string $sender_notes
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $receiver_address
 * @property string $receiver_notes
 * @property string $receiver_latitude
 * @property string $receiver_longitude
 * @property string $item_specification_name
 * @property string $item_specification_item_description
 * @property int $item_specification_length
 * @property int $item_specification_width
 * @property int $item_specification_height
 * @property int $item_specification_weight
 * @property string $item_specification_remarks
 * @property string $tracking_driver_name
 * @property string $tracking_driver_phone
 * @property string $tracking_live_tracking_url
 * @property string $tracking_vehicle_number
 * @property string $tracking_receiver_name
 * @property string $tracking_driver_log
 * @property string $poNo
 * @property string $service
 * @property string $price
 * @property string $distance
 * @property int $order_detail_id
 * @property string $order_detail_po_no
 * @property string $order_detail_awb_no
 * @property string $order_detail_order_date
 * @property string $order_detail_delivery_type_id
 * @property string $order_detail_total_amount
 * @property string $order_detail_partner_id
 * @property string $order_detail_status_id
 * @property string $order_detail_gosend_code
 * @property string $order_detail_speedy_code
 * @property string $order_detail_lalamove_code
 * @property bool $order_detail_is_multiple
 * @property string $order_detail_createdAt
 * @property string $order_detail_updatedAt
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\TransactionPickup $transaction_pickup
 * @property \Illuminate\Database\Eloquent\Collection $transaction_pickup_wehelpyou_updates
 *
 * @package App\Http\Models
 */
class TransactionPickupWehelpyou extends Eloquent
{
    protected $primaryKey = 'id_transaction_pickup_wehelpyou';

    protected $casts = [
        'id_transaction_pickup' => 'int',
        'box' => 'bool',
        'item_specification_length' => 'int',
        'item_specification_width' => 'int',
        'item_specification_height' => 'int',
        'item_specification_weight' => 'int',
        'order_detail_id' => 'int',
        'order_detail_is_multiple' => 'bool'
    ];

    protected $fillable = [
        'id_transaction_pickup',
        'vehicle_type',
        'courier',
        'box',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_latitude',
        'sender_longitude',
        'sender_notes',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_notes',
        'receiver_latitude',
        'receiver_longitude',
        'item_specification_name',
        'item_specification_item_description',
        'item_specification_length',
        'item_specification_width',
        'item_specification_height',
        'item_specification_weight',
        'item_specification_remarks',
        'tracking_driver_name',
        'tracking_driver_phone',
        'tracking_live_tracking_url',
        'tracking_vehicle_number',
        'tracking_receiver_name',
        'tracking_driver_log',
        'poNo',
        'service',
        'price',
        'distance',
        'order_detail_id',
        'order_detail_po_no',
        'order_detail_awb_no',
        'order_detail_order_date',
        'order_detail_delivery_type_id',
        'order_detail_total_amount',
        'order_detail_partner_id',
        'order_detail_status_id',
        'order_detail_gosend_code',
        'order_detail_speedy_code',
        'order_detail_lalamove_code',
        'order_detail_is_multiple',
        'order_detail_createdAt',
        'order_detail_updatedAt',
        'sla',
        'order_detail_feature_type_id',
        'order_detail_cancel_reason_id',
        'order_detail_cancel_detail',
        'order_detail_alfatrex_code',
        'order_detail_distance',
        'latest_status',
        'latest_status_id',
        'cancel_reason',
        'tracking_photo',
        'retry_count',
        'stop_booking_at',
        'address_name',
        'short_address'
    ];

    public function transaction_pickup()
    {
        return $this->belongsTo(\App\Http\Models\TransactionPickup::class, 'id_transaction_pickup');
    }

    public function transaction_pickup_wehelpyou_updates()
    {
        return $this->hasMany(\App\Http\Models\TransactionPickupWehelpyouUpdate::class, 'id_transaction_pickup_wehelpyou');
    }
}
