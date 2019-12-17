<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModifierProductCategory extends Model
{
	public $timestamps = false;
    protected $fillable = [
    	'id_product_category',
    	'id_product_modifier'
    ];
}
