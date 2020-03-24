<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductStockStatusUpdate extends Model
{
    protected $fillable = [
    	'id_product',
    	'id_user',
    	'new_status'
    ];
}
