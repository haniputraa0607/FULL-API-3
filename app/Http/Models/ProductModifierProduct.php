<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModifierProduct extends Model
{
	public $timestamps = false;
    protected $fillable = [
    	'id_product',
    	'id_product_modifier'
    ];
}
