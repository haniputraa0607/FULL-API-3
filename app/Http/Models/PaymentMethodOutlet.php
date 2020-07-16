<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodOutlet extends Model
{
    protected $primaryKey = 'id_payment_method_outlet';

    protected $fillable = [
        'id_payment_method',
        'id_outlet',
        'status'
    ];

    public function payment_method(){
        return $this->belongsTo(\App\Http\Models\PaymentMethod::class, 'id_payment_method');
    }

    public function outlet(){
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
    }
}
