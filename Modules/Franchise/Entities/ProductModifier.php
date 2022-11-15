<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifier extends Model
{
    protected $connection = 'mysql3';
    protected $hidden = ['pivot'];

    protected $primaryKey = 'id_product_modifier';

    protected $casts = [
        'id_product' => 'int',
        'product_variant_price' => 'double'
    ];

    protected $fillable = [
        'id_product_modifier_group',
        'id_product',
        'id_product_modifier_group',
        'modifier_type',
        'product_modifier_visibility',
        'type',
        'code',
        'text',
        'text_detail_trx',
        'product_modifier_visibility',
        'product_modifier_order',
        'created_at',
        'updated_at'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_modifier_products', 'id_product_modifier', 'id_product');
    }
    public function brands()
    {
        return $this->belongsToMany(\Modules\Brand\Entities\Brand::class, 'product_modifier_brands', 'id_product_modifier', 'id_brand');
    }
    public function product_categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'product_modifier_product_categories', 'id_product_modifier', 'id_product_category');
    }
    public function product_modifier_prices()
    {
        return $this->hasMany(ProductModifierPrice::class, 'id_product_modifier', 'id_product_modifier');
    }
}
