<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodCategory extends Model
{
    protected $primaryKey = 'id_payment_method_category';
    protected $table = "payment_method_categories";

    public $timestamps = false;

    protected $fillable = [
        'payment_method_category_name'
    ];
}
