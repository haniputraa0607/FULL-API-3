<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModifierBrand extends Model
{
	public $timestamps = false;
    protected $fillable = [
    	'id_brand',
    	'id_product_modifier'
    ];
}
