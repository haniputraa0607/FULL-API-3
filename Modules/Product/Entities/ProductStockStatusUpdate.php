<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductStockStatusUpdate extends Model
{
    protected $fillable = [
    	'id_product',
    	'date_time',
    	'id_outlet',
    	'id_product',
    	'id_user',
    	'user_type',
    	'new_status'
    ];
}
