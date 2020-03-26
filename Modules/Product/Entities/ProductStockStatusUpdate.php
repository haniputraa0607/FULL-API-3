<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductStockStatusUpdate extends Model
{
    protected $fillable = [
    	'id_product',
    	'id_outlet',
    	'id_user',
    	'id_outlet_app_otp',
    	'user_type',
    	'date_time',
    	'new_status'
    ];
}
