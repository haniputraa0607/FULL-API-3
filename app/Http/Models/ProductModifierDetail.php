<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModifierDetail extends Model
{
	protected $primaryKey = 'id_product_modifier_detail';
    protected $fillable = [
    	'id_product_modifier_detail',
    	'id_outlet',
    	'product_modifier_visibility',
    	'product_modifier_status',
    	'product_modifier_stock_status',
    ];
}
