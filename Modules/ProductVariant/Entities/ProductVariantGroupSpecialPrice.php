<?php

namespace Modules\ProductVariant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductVariantGroupSpecialPrice extends Model
{
    protected $table = 'product_variant_group_special_prices';
    protected $primaryKey = 'id_product_variant_group_special_price';

    protected $fillable = [
    	'id_outlet',
    	'id_product_variant_group',
    	'product_variant_group_price'
    ];

    protected $casts = [
    	'product_variant_group_price' => 'double'
    ];
}
