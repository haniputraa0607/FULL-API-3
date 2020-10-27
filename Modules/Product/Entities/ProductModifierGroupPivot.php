<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifierGroupPivot extends Model
{
    protected $fillable = [
    	'id_product_modifier_group',
    	'id_product',
    	'id_product_variant',
    ];
}
