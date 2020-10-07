<?php

namespace Modules\ProductVariant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductVariantGroupSpecialPrice extends Model
{
    protected $fillable = [
    	'id_outlet',
    	'id_product_variant',
    	'product_variant_group_price'
    ];

    protected $casts = [
    	'product_variant_group_price' => 'double'
    ];
}
