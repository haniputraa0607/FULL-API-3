<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionAdvanceOrder extends Model
{
    protected $fillable = [
    	'id_transaction',
    	'id_user',
    	'order_id',
    	'id_outlet',
    	'address',
    	'receiver_name',
    	'receiver_phone',
    	'date_delivery'
    ];
}
