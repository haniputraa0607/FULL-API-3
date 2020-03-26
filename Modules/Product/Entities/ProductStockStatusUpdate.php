<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductStockStatusUpdate extends Model
{
    protected $fillable = [
    	'id_product',
    	'id_outlet',
    	'id_user',
    	'user_type',
    	'date_time',
    	'new_status'
    ];
}
