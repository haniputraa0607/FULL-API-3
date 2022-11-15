<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 25 Nov 2020 09:17:07 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionDiscountBillProduct
 *
 * @property int $id_deals_discount_bill_product
 * @property int $id_deals
 * @property int $id_product
 * @property int $id_product_category
 * @property int $id_brand
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Modules\Promotion\Entities\DealsPromotionTemplate $deals_promotion_template
 * @property \Modules\Promotion\Entities\ProductCategory $product_category
 * @property \Modules\Promotion\Entities\Product $product
 *
 * @package Modules\Promotion\Entities
 */
class DealsPromotionDiscountBillProduct extends Eloquent
{
    protected $primaryKey = 'id_deals_discount_bill_product';

    protected $casts = [
        'id_deals' => 'int',
        'id_product' => 'int',
        'id_product_category' => 'int',
        'id_brand' => 'int'
    ];

    protected $fillable = [
        'id_deals',
        'id_product',
        'id_product_category',
        'id_brand',
        'id_product_variant_group'
    ];

    public function deals_promotion_template()
    {
        return $this->belongsTo(\Modules\Promotion\Entities\DealsPromotionTemplate::class, 'id_deals');
    }

    public function product_category()
    {
        return $this->belongsTo(\App\Http\Models\ProductCategory::class, 'id_product_category');
    }

    public function product()
    {
        return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
    }

    public function brand()
    {
        return $this->belongsTo(\Modules\Brand\Entities\Brand::class, 'id_brand');
    }

    public function product_variant_pivot()
    {
        return $this->hasMany(\Modules\ProductVariant\Entities\ProductVariantPivot::class, 'id_product_variant_group', 'id_product_variant_group');
    }
}
