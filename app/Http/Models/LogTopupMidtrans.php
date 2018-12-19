<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogTopupMidtrans extends Model
{
    protected $table = 'log_topup_midtrans';
    
    protected $primaryKey = 'id_log_topup_midtrans';

    protected $casts = [
    	'id_log_topup' => 'int'
    ];

    protected $fillable = [
    	'id_log_topup',
    	'masked_card',
    	'approval_code',
    	'bank',
    	'eci',
    	'transaction_time',
    	'gross_amount',
    	'order_id',
    	'payment_type',
    	'signature_key',
    	'status_code',
    	'vt_transaction_id',
    	'transaction_status',
    	'fraud_status',
    	'status_message'
    ];

    public function transaction()
    {
    	return $this->belongsTo(LogTopup::class, 'id_log_topup');
    }
}
