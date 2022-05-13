<?php

namespace Modules\Transaction\Entities;

use App\Http\Models\Configs;
use App\Http\Models\LogBalance;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransactionShipmentTrackingUpdate extends Model
{
    protected $table = 'transaction_shipment_tracking_updates';

    protected $primaryKey = 'id_transaction_shipment_tracking_update';

    protected $fillable   = [
        'id_transaction',
        'shipment_order_id',
        'tracking_description',
        'tracking_location',
        'tracking_date_time'
    ];

}
