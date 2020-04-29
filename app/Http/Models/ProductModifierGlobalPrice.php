<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModifierGlobalPrice extends Model
{
	protected $primaryKey = 'id_product_modifier_global_price';
    protected $fillable = [
    	'id_product_modifier',
    	'id_outlet',
    	'product_modifier_price'
    ];
}
