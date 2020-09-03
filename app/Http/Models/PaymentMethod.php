<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $primaryKey = 'id_payment_method';

    protected $fillable = [
        'payment_method_name',
        'id_payment_method_category',
        'status'
    ];

    public function payment_method_category(){
        return $this->belongsTo(\App\Http\Models\PaymentMethodCategory::class, 'id_payment_method_category');
    }

    public function payment_method_outlet(){
        return $this->hasMany(\App\Http\Models\PaymentMethodOutlet::class, 'id_payment_method');
    }

    public function transaction_payment_offlines(){
		return $this->hasMany(\App\Http\Models\TransactionPaymentOffline::class, 'id_payment_method');
	}

}
