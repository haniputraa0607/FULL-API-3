<?php

namespace Modules\ProductVariant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductVariantGroupDetail extends Model
{
    protected $table = 'product_variant_group_details';
    protected $primaryKey = 'id_product_variant_group_detail';

    protected $fillable = [
    	'id_outlet',
    	'id_product_variant_group',
    	'product_variant_group_stock_status',
    	'product_variant_group_status',
    	'product_variant_group_visibility'
    ];
}
