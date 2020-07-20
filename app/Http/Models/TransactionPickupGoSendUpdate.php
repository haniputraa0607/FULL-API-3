<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPickupGoSendUpdate extends Model
{
    public $primaryKey = 'id_transaction_pickup_go_send_update';
    protected $fillable = [
    	'id_transaction',
    	'id_transaction_pickup_go_send',
    	'go_send_order_no',
    	'status',
    	'description'
    ];
}
